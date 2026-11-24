<?php

namespace App\Http\Controllers;

use App\Services\Ocr\ProductImageOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductOcrController extends Controller
{
    /**
     * Recognize text from an uploaded product image and suggest a product name.
     */
    public function recognize(Request $request, ProductImageOcrService $ocrService): JsonResponse
    {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240', // 10MB max
            ],
        ]);

        $result = $ocrService->recognize($validated['image']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to process image.',
                'data' => [
                    'product_name' => null,
                    'extracted_text' => [],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? null,
            'data' => [
                'product_name' => $result['product_name'],
                'extracted_text' => $result['extracted_text'],
            ],
        ]);
    }
}
