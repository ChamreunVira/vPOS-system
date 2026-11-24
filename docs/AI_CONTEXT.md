# AI Context & OCR Feature Documentation

This document describes the OCR-based product form autofill feature, its architectural design, heuristic text extraction logic, and AWS EC2 (Ubuntu Linux) production deployment requirements.

---

## 1. Feature Overview

The OCR (Optical Character Recognition) feature allows store managers and cashiers to upload or capture a photo of a product packaging on the **Product Create/Edit** form. The system extracts recognized text lines using Tesseract OCR, identifies the most probable brand or product name, and automatically suggests or populates the **Product Name** input field.

### Key UX Principles
- **Non-blocking & additive:** If OCR is unavailable or fails to detect text, the user is never blocked from entering or saving product details manually.
- **Respect manual input:** If a user has already typed a product name, OCR will not overwrite it; instead, it offers a 1-click *"Use this name"* suggestion chip.
- **Candidate choices:** Detected alternative text lines (such as flavor or product variants) are presented as interactive pills that users can click to apply.

---

## 2. Architecture & Data Flow

```
User selects image in Product Form (Blade)
          │
          ▼
`@image-selected` event dispatched (Alpine.js)
          │
          ▼
Async `POST /admin/products/recognize-image` with CSRF
          │
          ▼
`ProductOcrController@recognize`
(Validates file, image MIME, size <= 10MB)
          │
          ▼
`ProductImageOcrService@recognize`
(Runs `/usr/bin/tesseract` via Symfony Process)
          │
          ▼
Candidate Extraction & Heuristic Scoring
(Filters noise, barcodes, nutrition facts, URLs, measurements)
          │
          ▼
Returns JSON: `{ success: true, data: { product_name: "...", extracted_text: [...] } }`
          │
          ▼
Alpine.js autofills or displays suggestion chips on form
```

### Components
1. **Service:** `app/Services/Ocr/ProductImageOcrService.php`
   - Executes the Tesseract binary safely via `Symfony\Component\Process\Process` using an argument array (preventing shell injection).
   - Sanitizes and extracts candidate lines.
   - Filters out nutritional labels, ingredients, barcodes, websites, emails, and isolated measurements.
   - Scores lines based on vertical positioning, word count, length, and Title Case / ALL CAPS capitalization.
2. **Controller:** `app/Http/Controllers/ProductOcrController.php`
   - Validates the uploaded image file (`jpeg,png,jpg,webp`, max 10MB).
   - Protects with `auth` and `verified` middleware.
   - Returns structured JSON response.
3. **Route:** `POST /admin/products/recognize-image` (`products.recognize-image`).
4. **Configuration:** `config/services.php` under `'tesseract'`.
5. **Views:**
   - `resources/views/components/form/image-upload.blade.php`: Dispatches `@image-selected`.
   - `resources/views/products/form.blade.php`: Handles OCR loading, autofill, suggestion chips, and candidate pills.

---

## 3. Product Name Extraction Heuristics

Tesseract outputs raw multi-line text from product packaging. The heuristic engine filters out unwanted metadata:

### Filtered Out / Deprioritized:
- **Nutritional / Legal keywords:** `Nutrition Facts`, `Serving Size`, `Calories`, `Total Fat`, `Sodium`, `Protein`, `Ingredients:`, `Contains:`, `Manufactured by`, `Best Before`, `Expiry Date`, `Batch No`, etc.
- **Barcode & Numeric Lines:** Purely numeric lines, price strings, timestamps, lot numbers.
- **Standalone Measurements:** Lines that contain only volume or weight (e.g. `500ml`, `1.5 L`, `330 ml`, `100g`, `12 fl oz`).
- **Web & Contact Info:** URLs (`www.`, `http`, `.com`), emails (`@`), social tags.
- **Short Noise:** Strings with fewer than 2 letters/digits or dominated by non-alphanumeric noise symbols.

### Scoring Criteria:
- **Position Bonus:** Lines near the top of the label receive higher base scores (brand names almost universally appear at the top).
- **Length & Word Count:** Ideal product names are 4–35 characters and 1–4 words (e.g. *"Coca-Cola"*, *"Angkor Beer Can"*). Long paragraphs receive penalties.
- **Capitalization:** Title Case (e.g. *"Coca-Cola"*, *"Original Taste"*) and clean ALL CAPS (*"PEPSI"*) receive bonuses.

---

## 4. AWS EC2 (Ubuntu Linux) Deployment Guide

### A. Install Tesseract OCR on Ubuntu 22.04 / 24.04 LTS
Run the following commands on your EC2 instance:

```bash
# Update package repositories
sudo apt update

# Install Tesseract OCR and English training data
sudo apt install -y tesseract-ocr tesseract-ocr-eng

# (Optional) Install Khmer language pack if recognizing local labels
sudo apt install -y tesseract-ocr-khm

# Verify installation and path
which tesseract
# Output should be: /usr/bin/tesseract

tesseract --version
tesseract --list-langs
```

### B. PHP Extension Requirements
Ensure the following standard PHP extensions are installed:
```bash
sudo apt install -y php8.5-gd php8.5-curl php8.5-mbstring
```

### C. Environment Configuration (`.env`)
Add the following optional overrides to your `.env` file on EC2 if you use a non-default path or custom language:

```env
TESSERACT_PATH=/usr/bin/tesseract
TESSERACT_TIMEOUT=15
TESSERACT_LANGUAGE=eng
```

### D. Temporary File Permissions
Tesseract requires read access to uploaded images stored in PHP's temporary directory. Ensure the web server user (`www-data` or `nginx`) has write/read permissions to `/tmp` and Laravel's storage:

```bash
# Verify permissions
sudo chmod 1777 /tmp
sudo chown -R www-data:www-data storage bootstrap/cache
```

### E. Production Considerations
1. **PHP Upload Limits:** Ensure `upload_max_filesize = 10M` and `post_max_size = 12M` in your `/etc/php/8.5/fpm/php.ini` to accommodate high-resolution smartphone photos.
2. **Process Timeout:** Default timeout is set to 15 seconds in `config/services.php`. Large 4K photos may take 1–3 seconds to process on a `t3.medium` or `c6i.large` instance.
3. **Memory Limits:** Ensure `memory_limit = 256M` in `php.ini` so image buffer processing runs without memory exhaustion.

---

## 5. Testing & Verification

Run the dedicated test suite:
```bash
php artisan test --filter=ProductOcrTest
```

Run Pint code style formatter:
```bash
vendor/bin/pint --format agent
```

---

## 6. Flexible Mobile Barcode Scanner Feature

The Mobile Barcode Scanner allows an Android or iOS smartphone to act as a wireless handheld barcode scanner for the desktop POS register. Products scanned from the phone are automatically inserted into the desktop POS cart in real-time.

### A. Architectural Overview

```
                      ┌───────────────────────────────────────────────┐
                      │              Desktop POS Terminal             │
                      │  - "Mobile scanner" modal (QR / 6-char code)  │
                      │  - HTTP Polling every 1.5s (after_id cursor)  │
                      │  - Automatically calls `addProductByCode()`   │
                      └──────────────────────▲────────────────────────┘
                                             │
                       HTTP Polling / Events │ GET /admin/pos/scanner/events
                                             │
                      ┌──────────────────────▼────────────────────────┐
                      │          Laravel Backend (vPOS)               │
                      │  - PosScannerService                          │
                      │  - PosScanSession (SHA-256 tokens & hashes)   │
                      │  - PosScanEvent (sequential event log)        │
                      └──────────────────────▲────────────────────────┘
                                             │
                          POST Barcode Scan  │ POST /pos/scanner/scan
                          (X-Device-Token)   │
                                             │
                      ┌──────────────────────┴────────────────────────┐
                      │          Mobile Scanner (/pos/scanner)        │
                      │  - Html5Qrcode (ZXing) 640x480 @ 6 FPS       │
                      │  - Audio beep + haptic pulse feedback         │
                      │  - Flashlight / Torch toggle                  │
                      │  - Duplicate scan debounce (1.8s)             │
                      │  - Offline retry queue                        │
                      └───────────────────────────────────────────────┘
```

### B. Connection Methods

1. **Method A: QR Code Pairing**
   - Cashier clicks "Mobile scanner" in POS header.
   - Desktop displays a pairing QR code embedding `https://<domain>/pos/scanner?token=<64-char-token>`.
   - Cashier scans the QR code with phone camera.
   - Mobile sends `POST /pos/scanner/pair/qr`.
   - Backend validates in a DB transaction with `lockForUpdate()`, verifies expiration (2 minutes), ensures not already paired, generates a 64-char `device_token`, hashes it (`device_token_hash`), clears the single-use pairing token, and extends the session to 8 hours.

2. **Method B: Direct 6-Character Pairing Code**
   - Desktop displays a short, human-readable code (e.g. `K7X9PQ`, generated from an unambiguous alphabet excluding `0, O, 1, I`).
   - Mobile opens `/pos/scanner` directly (or via bookmark/home screen shortcut).
   - Cashier types the 6-character code (case-insensitive) and taps "Connect to POS".
   - Backend validates code hash with `lockForUpdate()`, rate-limited to 5 attempts per minute to mitigate brute forcing.

### C. Database Schema

- **`pos_scan_sessions`:**
  - `id`: unsignedBigInteger primary key
  - `user_id`: foreignId constrained to `users` on delete cascade
  - `pairing_token_hash`: string(64), nullable, indexed
  - `device_token_hash`: string(64), nullable, indexed
  - `pairing_code_hash`: string(64), nullable, indexed
  - `paired_at`: timestamp, nullable
  - `expires_at`: timestamp, indexed
  - `created_at`, `updated_at`: timestamps

- **`pos_scan_events`:**
  - `id`: unsignedBigInteger primary key (sequential cursor)
  - `pos_scan_session_id`: foreignId constrained to `pos_scan_sessions` on delete cascade
  - `barcode`: string(100)
  - `processed_at`: timestamp, nullable
  - `created_at`: timestamp
  - Composite indexes: `[pos_scan_session_id, id]`, `[pos_scan_session_id, processed_at]`

### D. Endpoints

#### Desktop Register Endpoints (`admin/pos/scanner/...`, middleware: `['auth', 'verified']`)
- `POST /admin/pos/scanner/session`: Create or retrieve active session with pairing credentials.
- `GET /admin/pos/scanner/session/status`: Get connection status of current session.
- `POST /admin/pos/scanner/session/regenerate`: Refresh pairing token and code with fresh 2-minute expiration.
- `POST /admin/pos/scanner/session/disconnect`: Unlink paired mobile device.
- `GET /admin/pos/scanner/events?after_id={cursor}`: Fetch scan events after incremental cursor.

#### Mobile Device Endpoints (`pos/scanner/...`)
- `GET /pos/scanner`: Mobile scanner view (Blade + Alpine.js).
- `POST /pos/scanner/pair/qr`: Validate QR token, issue device token.
- `POST /pos/scanner/pair/code`: Validate manual code (`throttle:5,1`), issue device token.
- `GET /pos/scanner/status`: Check device status (middleware: `scanner.device`).
- `POST /pos/scanner/scan`: Submit barcode (`throttle:120,1`, middleware: `scanner.device`).
- `POST /pos/scanner/disconnect`: Unpair device (middleware: `scanner.device`).

### E. AWS EC2 & Nginx Deployment Considerations

1. **HTTPS is Mandatory for Mobile Camera Access:**
   - Both Android Chrome and iOS Safari strictly require HTTPS (`getUserMedia` will fail with security errors on plain HTTP, except for `localhost`).
   - Ensure Let's Encrypt SSL/TLS certificate is active on Nginx (`certbot --nginx`).
2. **Local Static Assets:**
   - Barcode decoder (`/vendor/html5-qrcode/html5-qrcode.min.js`) and QR generator (`/vendor/qrcode/qrcode.min.js`) are served locally from `public/vendor/`, ensuring 100% operation without external CDNs or internet connectivity.
3. **Nginx FastCGI Buffer & Rate Limiting:**
   - Polling runs at 1.5s intervals and is automatically paused when the desktop browser tab is hidden (`document.hidden`).
   - Nginx handles HTTP polling effortlessly with standard keepalive connections.

