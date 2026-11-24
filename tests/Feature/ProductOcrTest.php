<?php

use App\Models\User;
use App\Services\Ocr\ProductImageOcrService;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('guest cannot access product ocr recognition endpoint', function (): void {
    $response = $this->postJson(route('products.recognize-image'), []);

    $response->assertUnauthorized();
});

test('ocr endpoint validates that an image file is provided', function (): void {
    $response = $this->actingAs($this->user)->postJson(route('products.recognize-image'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    $responseInvalid = $this->actingAs($this->user)->postJson(route('products.recognize-image'), [
        'image' => $invalidFile,
    ]);

    $responseInvalid->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('ocr recognizes text and returns structured response', function (): void {
    // Generate an image with clear text
    $im = imagecreatetruecolor(400, 150);
    $bg = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, 400, 150, $bg);
    imagestring($im, 5, 40, 40, 'Coca-Cola 500ml', $black);
    imagestring($im, 4, 40, 80, 'Original Taste', $black);

    $tempFile = tempnam(sys_get_temp_dir(), 'test_ocr_').'.png';
    imagepng($im, $tempFile);

    $file = new UploadedFile($tempFile, 'coke.png', 'image/png', null, true);

    $response = $this->actingAs($this->user)->postJson(route('products.recognize-image'), [
        'image' => $file,
    ]);

    @unlink($tempFile);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'product_name',
                'extracted_text',
            ],
        ]);

    $data = $response->json('data');
    expect($data['product_name'])->not->toBeEmpty();
});

test('ocr service handles empty images gracefully', function (): void {
    // Pure blank image
    $im = imagecreatetruecolor(200, 100);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, 200, 100, $bg);

    $tempFile = tempnam(sys_get_temp_dir(), 'test_blank_').'.png';
    imagepng($im, $tempFile);

    $file = new UploadedFile($tempFile, 'blank.png', 'image/png', null, true);

    $response = $this->actingAs($this->user)->postJson(route('products.recognize-image'), [
        'image' => $file,
    ]);

    @unlink($tempFile);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'product_name' => null,
                'extracted_text' => [],
            ],
        ]);
});

test('ocr candidate heuristics correctly filter out noise and nutritional facts', function (): void {
    $service = new ProductImageOcrService;

    expect($service->isBlacklisted('Nutrition Facts'))->toBeTrue()
        ->and($service->isBlacklisted('Serving Size 1 bottle'))->toBeTrue()
        ->and($service->isBlacklisted('Calories 210'))->toBeTrue()
        ->and($service->isBlacklisted('Ingredients: Water, Sugar, Citric Acid'))->toBeTrue()
        ->and($service->isBlacklisted('Manufactured by Coca-Cola Company'))->toBeTrue()
        ->and($service->isBlacklisted('Best Before 2027/12/31'))->toBeTrue()
        ->and($service->isBlacklisted('1234567890123'))->toBeTrue()
        ->and($service->isBlacklisted('500ml'))->toBeTrue()
        ->and($service->isBlacklisted('www.coca-cola.com'))->toBeTrue()
        ->and($service->isBlacklisted('Coca-Cola'))->toBeFalse()
        ->and($service->isBlacklisted('Angkor Beer'))->toBeFalse();
});

test('ocr candidate scoring prioritizes brand and product names', function (): void {
    $service = new ProductImageOcrService;

    $rawText = "Coca-Cola\nOriginal Taste\n500ml\nServing Size 1 bottle\nCalories 150\nIngredients: Carbonated Water\nwww.coca-cola.com";
    $candidates = $service->extractCandidates($rawText);

    expect($candidates)->toContain('Coca-Cola')
        ->and($candidates)->toContain('Original Taste')
        ->and($candidates)->not->toContain('Serving Size 1 bottle')
        ->and($candidates)->not->toContain('Calories 150');

    $best = $service->selectBestProductName($candidates);
    expect($best)->toBe('Coca-Cola');
});
