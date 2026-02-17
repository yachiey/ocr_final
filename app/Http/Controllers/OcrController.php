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
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'You are a receipt data extraction system.
Your task: Extract structured data from the OCR text of a receipt.

CRITICAL RULES:
- Return ONLY valid JSON.
- Follow the exact schema.
- If a field does not exist, return null.
- Do NOT guess missing values.
- Detect currency from symbols 
- Convert dates to ISO format (YYYY-MM-DD) when possible.
- Extract quantity from item lines if present.
- Separate subtotal, tax, VAT, and total correctly.
- Keep numeric values as numbers (no currency symbols).

JSON SCHEMA:
{
  "merchant": {
    "name": string | null,
    "branch": string | null,
    "address": string | null,
    "phone": string | null,
    "tax_id": string | null
  },
  "transaction": {
    "date": string | null,
    "time": string | null,
    "invoice_number": string | null,
    "order_number": string | null,
    "terminal": string | null
  },
  "items": [
    {
      "name": string,
      "quantity": number | null,
      "unit_price": number | null,
      "total_price": number | null
    }
  ],
  "totals": {
    "subtotal": number | null,
    "tax": number | null,
    "vat_amount": number | null,
    "vatable_sales": number | null,
    "total": number | null,
    "currency": string | null
  },
  "payment": {
    "method": string | null,
    "card_last4": string | null,
    "authorization_code": string | null,
    "reference_number": string | null,
    "status": string | null
  },
  "lines": string[] (Array of strings, representing each physical line of text on the receipt, preserving layout. Crucial: Do not flatmap this, keep it line-by-line),
  "full_text": string (The complete raw text content. If possible, generate this from the lines)
}'
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

            // 1. Clean markdown code blocks if present
            $cleanedContent = preg_replace('/^```json\s*|\s*```$/', '', trim($content));

            // 2. Attempt to parse cleaned content
            $decoded = json_decode($cleanedContent, true);

            // 3. Fallback: If null, try to find the first '{' and last '}'
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (preg_match('/\{.*\}/s', $content, $matches)) {
                    $decoded = json_decode($matches[0], true);
                }
            }

            // 4. Ultimate Fallback: If still invalid, treat raw content as full_text
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $decoded = [
                    'store_name' => null,
                    'date' => null,
                    'total_amount' => null,
                    'currency' => null,
                    'items' => [],
                    'lines' => [],
                    'full_text' => $content // Show raw content so user sees something
                ];
            }

            // Reconstruct full_text from lines if available (and we haven't already set it from raw)
            if (isset($decoded['lines']) && is_array($decoded['lines']) && !empty($decoded['lines'])) {
                $decoded['full_text'] = implode("\n", $decoded['lines']);
            } elseif (empty($decoded['full_text']) && isset($decoded['lines'])) {
                $decoded['full_text'] = '';
            }

            return response()->json([
                'raw_text' => $content,
                'parsed' => $decoded
            ]);

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
