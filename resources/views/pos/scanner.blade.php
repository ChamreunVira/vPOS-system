<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-950 text-neutral-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>vPOS Mobile Scanner</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/vendor/html5-qrcode/html5-qrcode.min.js"></script>
    <style>
        #mobile-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 0.75rem;
        }
        #mobile-reader {
            border: none !important;
        }
    </style>
</head>
<body class="h-full bg-neutral-950 font-sans antialiased select-none"
      x-data="mobileScanner({
          initialToken: @js($initialToken),
          initialCode: @js($initialCode),
          pairQrUrl: '{{ route('pos.scanner.pair.qr') }}',
          pairCodeUrl: '{{ route('pos.scanner.pair.code') }}',
          statusUrl: '{{ route('pos.scanner.device.status') }}',
          scanUrl: '{{ route('pos.scanner.device.scan') }}',
          disconnectUrl: '{{ route('pos.scanner.device.disconnect') }}',
      })"
      x-init="initScanner()">

    <div class="flex min-h-dvh flex-col justify-between p-4 max-w-md mx-auto">
        {{-- Header --}}
        <header class="flex items-center justify-between pb-3 border-b border-neutral-800">
            <div class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-xl bg-neutral-900 border border-neutral-800 text-white">
                    <x-ui.icon name="smartphone" class="size-5" />
                </span>
                <div>
                    <h1 class="text-sm font-semibold tracking-tight text-white leading-tight">vPOS Mobile Scanner</h1>
                    <p class="text-xs text-neutral-400" x-text="isPaired ? ('Paired: ' + (cashierName || 'Cashier Register')) : 'Connect to Desktop POS'"></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span x-show="isPaired" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-400 border border-emerald-500/20">
                    <span class="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Connected</span>
                </span>
                <span x-show="!isPaired" class="inline-flex items-center gap-1.5 rounded-full bg-neutral-800 px-2.5 py-1 text-xs font-medium text-neutral-400">
                    <span>Unpaired</span>
                </span>
                <button type="button" x-show="isPaired" @click="disconnect()" class="rounded-lg p-1.5 text-neutral-400 hover:text-white hover:bg-neutral-800" title="Disconnect device">
                    <x-ui.icon name="log-out" class="size-4" />
                </button>
            </div>
        </header>

        {{-- Main Area --}}
        <main class="flex-1 flex flex-col justify-center my-4">
            {{-- UNPAIRED STATE: PAIRING INTERFACE --}}
            <template x-if="!isPaired">
                <div class="flex flex-col gap-5 py-4">
                    <div class="text-center space-y-2">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-neutral-900 border border-neutral-800 text-white">
                            <x-ui.icon name="scan" class="size-6 text-neutral-200" />
                        </div>
                        <h2 class="text-lg font-semibold text-white">Pair with Desktop POS</h2>
                        <p class="text-xs text-neutral-400 max-w-xs mx-auto">Enter the 6-character code shown on your POS screen, or scan the POS QR code.</p>
                    </div>

                    {{-- Manual 6-character Code Form --}}
                    <div class="rounded-2xl border border-neutral-800 bg-neutral-900/80 p-5 space-y-4">
                        <label class="block text-xs font-medium uppercase tracking-wider text-neutral-400 text-center">Pairing Code</label>
                        <div class="relative">
                            <input type="text"
                                   x-model="inputCode"
                                   maxlength="8"
                                   placeholder="e.g. K7X9PQ"
                                   @input="inputCode = inputCode.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                                   @keydown.enter.prevent="pairWithCode()"
                                   class="w-full tracking-widest text-center uppercase font-mono text-2xl font-bold rounded-xl border border-neutral-700 bg-neutral-950 px-4 py-3 text-white placeholder-neutral-600 focus:border-white focus:outline-none focus:ring-1 focus:ring-white">
                        </div>

                        <button type="button"
                                @click="pairWithCode()"
                                :disabled="pairingLoading || inputCode.length < 4"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-white py-3 text-sm font-semibold text-neutral-950 transition hover:bg-neutral-200 disabled:opacity-40 disabled:hover:bg-white">
                            <span x-show="pairingLoading" class="size-4 rounded-full border-2 border-neutral-950 border-t-transparent animate-spin"></span>
                            <span x-text="pairingLoading ? 'Verifying…' : 'Connect to POS'"></span>
                        </button>

                        <div x-show="pairingError" x-cloak class="rounded-lg bg-rose-500/10 border border-rose-500/20 p-3 text-xs text-rose-300 text-center" x-text="pairingError"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-xs text-neutral-500">
                            Or open your phone camera and scan the QR code on the desktop POS screen.
                        </p>
                    </div>
                </div>
            </template>

            {{-- PAIRED STATE: ACTIVE SCANNER INTERFACE --}}
            <template x-if="isPaired">
                <div class="flex-1 flex flex-col justify-between space-y-4">
                    {{-- Camera Viewport --}}
                    <div class="relative overflow-hidden rounded-2xl bg-neutral-900 border border-neutral-800 shadow-2xl aspect-[4/3] flex items-center justify-center">
                        <div id="mobile-reader" class="size-full"></div>

                        {{-- Scanning Laser animation overlay --}}
                        <div x-show="cameraScanning" class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div class="laser-line"></div>
                            {{-- Reticle corners --}}
                            <div class="absolute inset-8 border border-white/20 rounded-xl pointer-events-none">
                                <div class="absolute -top-1 -left-1 size-4 border-t-2 border-l-2 border-red-500 rounded-tl"></div>
                                <div class="absolute -top-1 -right-1 size-4 border-t-2 border-r-2 border-red-500 rounded-tr"></div>
                                <div class="absolute -bottom-1 -left-1 size-4 border-b-2 border-l-2 border-red-500 rounded-bl"></div>
                                <div class="absolute -bottom-1 -right-1 size-4 border-b-2 border-r-2 border-red-500 rounded-br"></div>
                            </div>
                        </div>

                        {{-- Loading / Error indicator inside viewport --}}
                        <div x-show="cameraLoading" class="absolute inset-0 bg-neutral-950/80 flex flex-col items-center justify-center gap-2 p-4 text-center">
                            <span class="size-8 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <span class="text-xs text-neutral-300">Starting camera…</span>
                        </div>

                        <div x-show="cameraError" x-cloak class="absolute inset-0 bg-neutral-950/95 flex flex-col items-center justify-center gap-3 p-5 text-center z-20">
                            <x-ui.icon name="alert" class="size-7 text-amber-400" />
                            <p class="text-xs text-neutral-200 leading-relaxed max-w-xs" x-text="cameraError"></p>
                            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-neutral-950 hover:bg-neutral-200 active:scale-95 transition shadow">
                                    <x-ui.icon name="camera" class="size-4" />
                                    <span>Snap Photo with Camera</span>
                                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanFromFile($event)">
                                </label>
                                <button type="button" @click="startCamera()" class="rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs font-medium text-white hover:bg-neutral-800">Retry</button>
                            </div>
                        </div>

                        {{-- Floating Camera Controls --}}
                        <div class="absolute top-3 right-3 flex items-center gap-2 z-10" x-show="cameraScanning">
                            <button type="button"
                                    x-show="hasTorch"
                                    @click="toggleTorch()"
                                    :class="torchOn ? 'bg-amber-400 text-neutral-950' : 'bg-neutral-950/60 text-white hover:bg-neutral-950/80'"
                                    class="size-9 rounded-xl backdrop-blur flex items-center justify-center transition border border-white/10"
                                    title="Toggle Flashlight">
                                <x-ui.icon name="zap" class="size-4" />
                            </button>

                            <button type="button"
                                    @click="toggleCameraPause()"
                                    class="size-9 rounded-xl bg-neutral-950/60 backdrop-blur flex items-center justify-center text-white border border-white/10"
                                    :title="cameraPaused ? 'Resume camera' : 'Pause camera'">
                                <span x-show="!cameraPaused"><x-ui.icon name="pause" class="size-4" /></span>
                                <span x-show="cameraPaused" x-cloak><x-ui.icon name="scan" class="size-4" /></span>
                            </button>
                        </div>
                    </div>

                    {{-- Feedback & Status Card --}}
                    <div class="rounded-2xl border border-neutral-800 bg-neutral-900/70 p-4 space-y-2">
                        <div class="flex items-center justify-between text-xs text-neutral-400">
                            <span>Last Scanned Barcode</span>
                            <span x-show="pendingQueue.length > 0" class="text-amber-400 font-mono" x-text="pendingQueue.length + ' queued'"></span>
                        </div>

                        <div class="flex items-center justify-between gap-3 min-h-9">
                            <span class="font-mono text-lg font-semibold tracking-wider text-white truncate"
                                  x-text="lastBarcode || 'Point camera at barcode'"></span>
                            <span x-show="scanSubmitting" class="size-4 rounded-full border-2 border-white border-t-transparent animate-spin shrink-0"></span>
                        </div>

                        <div x-show="scanFeedback"
                             x-cloak
                             :class="{
                                 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20': scanFeedbackType === 'success',
                                 'text-rose-400 bg-rose-500/10 border-rose-500/20': scanFeedbackType === 'error',
                                 'text-neutral-300 bg-neutral-800 border-neutral-700': scanFeedbackType === 'info'
                             }"
                             class="rounded-lg border px-3 py-1.5 text-xs flex items-center gap-2">
                            <span x-show="scanFeedbackType === 'success'"><x-ui.icon name="check" class="size-3.5 shrink-0" /></span>
                            <span x-show="scanFeedbackType === 'error'" x-cloak><x-ui.icon name="alert" class="size-3.5 shrink-0" /></span>
                            <span x-show="scanFeedbackType === 'info'" x-cloak><x-ui.icon name="info" class="size-3.5 shrink-0" /></span>
                            <span x-text="scanFeedback" class="truncate"></span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer flex flex-col items-center justify-center gap-1 rounded-xl border border-neutral-800 bg-neutral-900 px-2 py-2.5 text-center text-xs font-medium text-white hover:bg-neutral-800 active:scale-[0.98] transition">
                            <x-ui.icon name="camera" class="size-4 text-neutral-300" />
                            <span class="text-[11px] leading-tight">Snap Photo</span>
                            <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanFromFile($event)">
                        </label>

                        <button type="button"
                                @click="manualModalOpen = true"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl border border-neutral-800 bg-neutral-900 px-2 py-2.5 text-center text-xs font-medium text-white hover:bg-neutral-800 active:scale-[0.98] transition">
                            <x-ui.icon name="pencil" class="size-4 text-neutral-400" />
                            <span class="text-[11px] leading-tight">Type Code</span>
                        </button>

                        <button type="button"
                                @click="disconnect()"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl border border-neutral-800 bg-neutral-900 px-2 py-2.5 text-center text-xs font-medium text-rose-400 hover:bg-rose-500/10 hover:border-rose-500/20 active:scale-[0.98] transition">
                            <x-ui.icon name="log-out" class="size-4 text-rose-400" />
                            <span class="text-[11px] leading-tight">Disconnect</span>
                        </button>
                    </div>
                </div>
            </template>
        </main>

        {{-- Footer --}}
        <footer class="pt-3 border-t border-neutral-800 text-center">
            <p class="text-[11px] text-neutral-500">vPOS Hardware Bridge · Encrypted Device Token</p>
        </footer>

        {{-- Manual Barcode Input Modal --}}
        <div x-show="manualModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-neutral-950/80 backdrop-blur-sm" @keydown.escape.window="manualModalOpen = false">
            <div class="w-full max-w-sm rounded-2xl border border-neutral-800 bg-neutral-900 p-5 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">Manual Barcode Entry</h3>
                    <button type="button" @click="manualModalOpen = false" class="text-neutral-400 hover:text-white"><x-ui.icon name="x" class="size-4" /></button>
                </div>
                <div>
                    <label class="block text-xs text-neutral-400 mb-1">Barcode or SKU</label>
                    <input type="text"
                           x-model="manualBarcode"
                           inputmode="numeric"
                           placeholder="Enter digits or code"
                           @keydown.enter.prevent="submitManualBarcode()"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-950 px-3.5 py-2.5 font-mono text-sm text-white focus:border-white focus:outline-none">
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="manualModalOpen = false" class="flex-1 rounded-xl border border-neutral-700 py-2.5 text-xs font-medium text-neutral-300 hover:bg-neutral-800">Cancel</button>
                    <button type="button" @click="submitManualBarcode()" :disabled="!manualBarcode.trim()" class="flex-1 rounded-xl bg-white py-2.5 text-xs font-semibold text-neutral-950 hover:bg-neutral-200 disabled:opacity-40">Send to POS</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mobileScanner(config) {
            return {
                isPaired: false,
                cashierName: '',
                deviceToken: localStorage.getItem('vpos-scanner-token') || '',
                inputCode: config.initialCode || '',
                pairingLoading: false,
                pairingError: '',
                cameraScanning: false,
                cameraLoading: false,
                cameraPaused: false,
                cameraError: '',
                hasTorch: false,
                torchOn: false,
                lastBarcode: '',
                lastScannedAt: 0,
                scanFeedback: '',
                scanFeedbackType: 'info',
                scanSubmitting: false,
                pendingQueue: [],
                manualModalOpen: false,
                manualBarcode: '',
                scannerInstance: null,

                async initScanner() {
                    // Check URL params first
                    if (config.initialToken) {
                        await this.pairWithQr(config.initialToken);
                        return;
                    }

                    if (config.initialCode) {
                        this.inputCode = config.initialCode;
                        await this.pairWithCode();
                        return;
                    }

                    // Check existing device token in localStorage
                    if (this.deviceToken) {
                        await this.checkStatus();
                    }

                    window.addEventListener('online', () => {
                        this.flushQueue();
                    });
                },

                playBeep() {
                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        const ctx = new AudioCtx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(1850, ctx.currentTime);
                        gain.gain.setValueAtTime(0.2, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.12);
                    } catch {}
                    if (navigator.vibrate) {
                        navigator.vibrate(80);
                    }
                },

                async pairWithQr(token) {
                    this.pairingLoading = true;
                    this.pairingError = '';
                    try {
                        const response = await fetch(config.pairQrUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ token: token.trim() }),
                        });
                        const data = await response.json();
                        if (response.ok && data.success && data.data?.device_token) {
                            this.setPaired(data.data.device_token, data.data.cashier_name);
                        } else {
                            this.pairingError = data.message || Object.values(data.errors || {})[0]?.[0] || 'Invalid or expired QR code.';
                        }
                    } catch (e) {
                        this.pairingError = 'Connection failed. Please check network.';
                    } finally {
                        this.pairingLoading = false;
                    }
                },

                async pairWithCode() {
                    const code = this.inputCode.trim().toUpperCase();
                    if (!code) return;
                    this.pairingLoading = true;
                    this.pairingError = '';
                    try {
                        const response = await fetch(config.pairCodeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ code }),
                        });
                        const data = await response.json();
                        if (response.ok && data.success && data.data?.device_token) {
                            this.setPaired(data.data.device_token, data.data.cashier_name);
                        } else {
                            this.pairingError = data.message || Object.values(data.errors || {})[0]?.[0] || 'Invalid or expired pairing code.';
                        }
                    } catch (e) {
                        this.pairingError = 'Connection failed. Please check network.';
                    } finally {
                        this.pairingLoading = false;
                    }
                },

                async checkStatus() {
                    try {
                        const response = await fetch(config.statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Device-Token': this.deviceToken,
                            },
                        });
                        const data = await response.json();
                        if (response.ok && data.success && data.data?.is_paired) {
                            this.setPaired(this.deviceToken, data.data.cashier_name);
                        } else {
                            this.clearPaired();
                        }
                    } catch (e) {
                        // Offline or network glitch, preserve paired state if token exists
                        this.isPaired = true;
                        this.startCamera();
                    }
                },

                setPaired(token, cashier) {
                    this.deviceToken = token;
                    localStorage.setItem('vpos-scanner-token', token);
                    this.cashierName = cashier || '';
                    this.isPaired = true;
                    this.pairingError = '';
                    this.$nextTick(() => {
                        this.startCamera();
                    });
                },

                clearPaired() {
                    this.deviceToken = '';
                    localStorage.removeItem('vpos-scanner-token');
                    this.isPaired = false;
                    this.cashierName = '';
                    this.stopCamera();
                },

                async startCamera() {
                    if (!this.isPaired) return;
                    this.cameraError = '';
                    this.cameraLoading = true;

                    try {
                        await this.$nextTick();
                        if (!window.Html5Qrcode) {
                            throw new Error('Scanner engine is loading. Please try again.');
                        }

                        if (!this.scannerInstance) {
                            const formats = window.Html5QrcodeSupportedFormats ? [
                                window.Html5QrcodeSupportedFormats.EAN_13,
                                window.Html5QrcodeSupportedFormats.EAN_8,
                                window.Html5QrcodeSupportedFormats.CODE_128,
                                window.Html5QrcodeSupportedFormats.UPC_A,
                                window.Html5QrcodeSupportedFormats.UPC_E,
                            ] : undefined;

                            this.scannerInstance = new window.Html5Qrcode('mobile-reader', {
                                formatsToSupport: formats,
                                verbose: false,
                            });
                        }

                        if (this.scannerInstance.isScanning) {
                            this.cameraScanning = true;
                            this.cameraLoading = false;
                            return;
                        }

                        await this.scannerInstance.start(
                            { facingMode: 'environment' },
                            {
                                fps: 6, // Low resource & battery friendly (requirement 8)
                                videoConstraints: {
                                    width: { ideal: 640 },
                                    height: { ideal: 480 },
                                    facingMode: 'environment',
                                },
                            },
                            (decodedText) => this.onBarcodeScanned(decodedText),
                            () => {}
                        );

                        this.cameraScanning = true;
                        this.cameraLoading = false;
                        this.cameraPaused = false;

                        // Check torch capability
                        const videoEl = document.querySelector('#mobile-reader video');
                        const track = videoEl?.srcObject?.getVideoTracks()?.[0];
                        if (track && track.getCapabilities && track.getCapabilities().torch) {
                            this.hasTorch = true;
                        }
                    } catch (error) {
                        this.cameraLoading = false;
                        this.cameraScanning = false;
                        const msg = error?.message || String(error);
                        if (msg.includes('NotAllowedError') || msg.includes('Permission')) {
                            this.cameraError = 'Camera permission was denied. Allow camera access in browser settings.';
                        } else if (msg.includes('NotFoundError')) {
                            this.cameraError = 'No camera detected on this phone.';
                        } else {
                            this.cameraError = msg || 'Could not open camera.';
                        }
                    }
                },

                async stopCamera() {
                    if (this.scannerInstance && this.scannerInstance.isScanning) {
                        try {
                            await this.scannerInstance.stop();
                        } catch {}
                    }
                    this.cameraScanning = false;
                    this.torchOn = false;
                },

                toggleCameraPause() {
                    if (this.cameraPaused) {
                        this.scannerInstance?.resume();
                        this.cameraPaused = false;
                    } else {
                        this.scannerInstance?.pause(true);
                        this.cameraPaused = true;
                    }
                },

                async toggleTorch() {
                    if (!this.hasTorch) return;
                    this.torchOn = !this.torchOn;
                    try {
                        const videoEl = document.querySelector('#mobile-reader video');
                        const track = videoEl?.srcObject?.getVideoTracks()?.[0];
                        if (track) {
                            await track.applyConstraints({
                                advanced: [{ torch: this.torchOn }],
                            });
                        }
                    } catch {
                        this.torchOn = false;
                    }
                },

                onBarcodeScanned(decodedText) {
                    const code = decodedText?.trim();
                    if (!code) return;

                    const now = Date.now();
                    // Debounce duplicate barcode detections within 1.8 seconds (requirement 2 & 8)
                    if (code === this.lastBarcode && (now - this.lastScannedAt) < 1800) {
                        return;
                    }

                    this.lastBarcode = code;
                    this.lastScannedAt = now;
                    this.playBeep();

                    this.sendBarcode(code);
                },

                async sendBarcode(barcode) {
                    this.scanSubmitting = true;
                    this.scanFeedback = 'Sending to POS…';
                    this.scanFeedbackType = 'info';

                    try {
                        const response = await fetch(config.scanUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Device-Token': this.deviceToken,
                            },
                            body: JSON.stringify({ barcode }),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.scanFeedback = `"${barcode}" added to POS cart!`;
                            this.scanFeedbackType = 'success';
                        } else if (response.status === 401) {
                            this.scanFeedback = 'Session expired. Please re-pair.';
                            this.scanFeedbackType = 'error';
                            this.clearPaired();
                        } else {
                            this.scanFeedback = data.message || 'Error recording barcode.';
                            this.scanFeedbackType = 'error';
                        }
                    } catch (e) {
                        // Network error: queue for automatic recovery (requirement 2)
                        this.pendingQueue.push(barcode);
                        this.scanFeedback = `Offline: queued "${barcode}". Will retry.`;
                        this.scanFeedbackType = 'info';
                    } finally {
                        this.scanSubmitting = false;
                    }
                },

                async flushQueue() {
                    if (!this.pendingQueue.length || !this.deviceToken) return;
                    const items = [...this.pendingQueue];
                    this.pendingQueue = [];

                    for (const code of items) {
                        await this.sendBarcode(code);
                    }
                },

                submitManualBarcode() {
                    const code = this.manualBarcode.trim();
                    if (!code) return;
                    this.lastBarcode = code;
                    this.sendBarcode(code);
                    this.manualBarcode = '';
                    this.manualModalOpen = false;
                },

                async disconnect() {
                    if (confirm('Disconnect this mobile scanner?')) {
                        try {
                            await fetch(config.disconnectUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Device-Token': this.deviceToken,
                                },
                            });
                        } catch {}
                        this.clearPaired();
                    }
                },
            };
        }
    </script>
</body>
</html>
