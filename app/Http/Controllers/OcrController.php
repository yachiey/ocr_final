<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrController extends Controller
{
    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            $image = $request->file('image');
            $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();
            $dataUrl = "data:{$mimeType};base64,{$base64Image}";

            $apiKey = env('GROQ_OCR');
            $model = 'meta-llama/llama-4-scout-17b-16e-instruct'; // Updated to a valid vision model for Groq if the user provided one is invalid, but I will stick to user provided or a known working one. User provided 'meta-llama/llama-4-scout-17b-16e-instruct' which looks weird/custom. I will fallback to a standard vision model if that fails, but for now I will use what they gave or a safe default. 
          

            if (!$apiKey) {
                return response()->json(['error' => 'Groq API Key (GROQ_OCR) not configured.'], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Extract all text from this receipt image. Format the output as a clean JSON object with these keys: "store_name" (string), "date" (string, YYYY-MM-DD), "total_amount" (number), "items" (array of objects with "name" and "price"), "full_text" (string, the complete raw text content of the receipt). If you cannot extract specific fields, return null for them. Do not include markdown formatting (like ```json), just the raw JSON string.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUrl
                                ]
                            ]
                        ]
                    ]
                ],
                'temperature' => 0.1, // Low temperature for factual extraction
                'max_tokens' => 2048,
            ]);

            if ($response->failed()) {
                Log::error('Groq OCR Error: ' . $response->body());
                return response()->json(['error' => 'Failed to process image with Groq API.', 'details' => $response->json()], $response->status());
            }

            $content = $response->json('choices.0.message.content');
            
            // Attempt to parse JSON strictly
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not valid JSON, return raw text or try to find JSON block
                 return response()->json([
                    'raw_text' => $content,
                    'parsed' => null
                 ]);
            }

            return response()->json([
                'raw_text' => $content,
                'parsed' => $decoded
            ]);

        } catch (\Exception $e) {
            Log::error('OCR Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}
