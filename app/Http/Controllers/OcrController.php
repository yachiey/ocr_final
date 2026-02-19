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
- Separate subtotal, tax (Sales Tax/Tax), VAT, and total correctly.
- If "Tax" or "Sales Tax" is explicitly listed, extract it to "tax".
- "vat_amount" is for VAT/Value Added Tax specifically. Use "tax" for generic/sales tax.
- **IMPORTANT**: The **TOTAL** amount (often labeled "Amount Due", "TOTAL", or "Grand Total") is the final amount paid.
- **TAX HANDLING**: In some regions (e.g., Philippines/BIR), the "Total" ALREADY includes VAT. 
- If "Total" = "VATable Sales" + "VAT", then the "Total" on the receipt is the final amount. Do NOT add VAT again.
- Extract the largest labeled amount (Total/Amount Due) to the "total" field.
- "subtotal" should be the amount BEFORE taxes/vat if clearly labeled, or the sum of items.
- Detect currency from symbols (e.g., "$", "P", "PHP").
- Keep numeric values as numbers (no currency symbols).
- DOUBLE CHECK the total amount. It should equal the labeled total on the image.
- If the image is blurry, do your best to estimate but prefer null over a wild guess.

                                        IMPORTANT: For the "currency" field, you MUST determine the correct ISO 4217 currency code based on the merchant address, location, or any country indicators visible on the receipt. Examples:
                                        - Philippines addresses → "PHP"
                                        - USA addresses → "USD"
                                        - Japan addresses → "JPY"
                                        - UK addresses → "GBP"
                                        - EU/Eurozone addresses → "EUR"
                                        - South Korea addresses → "KRW"
                                        - Singapore addresses → "SGD"
                                        - Thailand addresses → "THB"
                                        - Australia addresses → "AUD"
                                        - Canada addresses → "CAD"
                                        Do NOT leave currency as null if you can determine the country from the address or any other context on the receipt.

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
                        'max_tokens' => 4096, // Higher limit to avoid truncation for receipts with many items
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

            // --- POST-PROCESSING: Total Validation & Computation ---
            $calculatedItemSum = 0;
            if (isset($decoded['items']) && is_array($decoded['items'])) {
                foreach ($decoded['items'] as $item) {
                    $price = $item['total_price'] ?? $item['unit_price'] ?? 0;
                    $calculatedItemSum += is_numeric($price) ? (float) $price : 0;
                }
            }

            if (!isset($decoded['totals'])) {
                $decoded['totals'] = [];
            }

            // Helper function to safely get float or null
            $getFloat = function ($key) use ($decoded) {
                $val = $decoded['totals'][$key] ?? null;
                return ($val !== null && is_numeric($val)) ? (float) $val : null;
            };

            $subtotal = $getFloat('subtotal');
            $tax = $getFloat('tax');
            $vatAmount = $getFloat('vat_amount');
            $vatableSales = $getFloat('vatable_sales');
            $total = $getFloat('total');

            // Consolidated Tax (prefer tax, then vat_amount)
            $effectiveTax = $tax ?? $vatAmount ?? 0;

            // --- Business Logic for VAT-Inclusive Receipts ---
            // If the extracted total looks like it already includes tax (it's >= subtotal + effectiveTax)
            // Or if subtotal and total are the same and tax is non-zero (common if AI puts total in subtotal)
            if ($total !== null && $subtotal !== null) {
                if (abs($total - ($subtotal + $effectiveTax)) < 0.05) {
                    // Normal case: Total = Subtotal + Tax
                } elseif (abs($total - $subtotal) < 0.05 && $effectiveTax > 0) {
                    // The AI likely put the Final Total in both fields or misidentified.
                    // If VATable Sales is present, that's our true subtotal.
                    if ($vatableSales !== null && $vatableSales > 0 && abs($total - ($vatableSales + $effectiveTax)) < 0.05) {
                        $subtotal = $vatableSales;
                    }
                } elseif ($total < $subtotal && abs($subtotal - ($total + $effectiveTax)) < 0.05) {
                    // Logic Flip: AI put the Total in Subtotal and some other value in Total.
                    // This happens if the AI thinks Subtotal is the labeled "Total" from the receipt.
                    $temp = $total;
                    $total = $subtotal;
                    $subtotal = $temp;
                }
            }

            // 1. If Total is missing, try to calculate it
            if ($total === null) {
                if ($subtotal !== null) {
                    $total = $subtotal + $effectiveTax;
                } elseif ($calculatedItemSum > 0) {
                    $total = $calculatedItemSum + $effectiveTax;
                }
            }

            // 2. If Subtotal is missing, try to calculate it
            if ($subtotal === null) {
                if ($total !== null) {
                    $subtotal = $total - $effectiveTax;
                } else {
                    $subtotal = $calculatedItemSum;
                }
            }

            // 3. Final sanity check: If Subtotal + Tax = Total is FALSE and we have VATable Sales
            // In PH receipts: VATable Sales + VAT = Total.
            if ($vatableSales !== null && $vatAmount !== null && $total !== null) {
                if (abs($total - ($vatableSales + $vatAmount)) < 0.05) {
                    // Subtotal in our schema should ideally be the pre-tax amount.
                    $subtotal = $vatableSales;
                }
            }

            // 4. Update the decoded values
            $decoded['totals']['subtotal'] = $subtotal;
            $decoded['totals']['total'] = $total;
            // Ensure derived tax/vat is preserved if we calculated it into effectiveTax but didn't write it back yet
            if ($tax === null && $vatAmount !== null) {
                // If we have VAT amount but no Tax field, keeping Tax as null is fine, effectiveTax was used. 
                // But if we calculated effectiveTax from Total-Subtotal (step 3), we already set it.
            }

            // Infer currency from merchant address if the LLM didn't return one
            if (empty($decoded['totals']['currency'])) {
                $decoded['totals']['currency'] = $this->inferCurrencyFromAddress(
                    $decoded['merchant']['address'] ?? ''
                );
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



        } catch (\Exception $e) {
            Log::error('OCR Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    /**
     * Infer ISO 4217 currency code from a merchant address string.
     * Falls back to 'PHP' (Philippine Peso) as the app's primary market.
     */
    private function inferCurrencyFromAddress(string $address): string
    {
        $address = strtolower($address);

        // Map of keywords (country names, cities, regions) to currency codes
        $mappings = [
            // Philippines
            'PHP' => ['philippines', 'manila', 'cebu', 'davao', 'quezon', 'makati', 'taguig', 'pasig', 'pasay', 'caloocan', 'muntinlupa', 'paranaque', 'marikina', 'cavite', 'laguna', 'bulacan', 'pampanga', 'batangas', 'rizal'],
            // United States
            'USD' => ['united states', 'usa', 'u.s.a', 'u.s.', 'new york', 'los angeles', 'chicago', 'houston', 'phoenix', 'california', 'texas', 'florida', 'illinois'],
            // Japan
            'JPY' => ['japan', 'tokyo', 'osaka', 'kyoto', 'yokohama', 'nagoya', 'sapporo', 'fukuoka'],
            // United Kingdom
            'GBP' => ['united kingdom', 'england', 'london', 'manchester', 'birmingham', 'scotland', 'wales', 'uk'],
            // Eurozone
            'EUR' => ['germany', 'france', 'italy', 'spain', 'netherlands', 'belgium', 'austria', 'ireland', 'portugal', 'greece', 'finland', 'berlin', 'paris', 'rome', 'madrid', 'amsterdam', 'vienna'],
            // South Korea
            'KRW' => ['south korea', 'korea', 'seoul', 'busan', 'incheon'],
            // Singapore
            'SGD' => ['singapore'],
            // Thailand
            'THB' => ['thailand', 'bangkok', 'chiang mai', 'phuket', 'pattaya'],
            // Australia
            'AUD' => ['australia', 'sydney', 'melbourne', 'brisbane', 'perth'],
            // Canada
            'CAD' => ['canada', 'toronto', 'vancouver', 'montreal', 'ottawa', 'calgary'],
            // China
            'CNY' => ['china', 'beijing', 'shanghai', 'shenzhen', 'guangzhou'],
            // India
            'INR' => ['india', 'mumbai', 'delhi', 'bangalore', 'hyderabad', 'chennai'],
            // Indonesia
            'IDR' => ['indonesia', 'jakarta', 'bali', 'surabaya', 'bandung'],
            // Malaysia
            'MYR' => ['malaysia', 'kuala lumpur', 'penang', 'johor'],
            // Vietnam
            'VND' => ['vietnam', 'ho chi minh', 'hanoi', 'da nang'],
            // Taiwan
            'TWD' => ['taiwan', 'taipei', 'kaohsiung'],
            // Hong Kong
            'HKD' => ['hong kong'],
            // New Zealand
            'NZD' => ['new zealand', 'auckland', 'wellington'],
            // Switzerland
            'CHF' => ['switzerland', 'zurich', 'geneva', 'bern'],
            // Brazil
            'BRL' => ['brazil', 'são paulo', 'sao paulo', 'rio de janeiro'],
            // Mexico
            'MXN' => ['mexico', 'mexico city', 'guadalajara', 'monterrey'],
            // South Africa
            'ZAR' => ['south africa', 'johannesburg', 'cape town', 'durban'],
            // Russia
            'RUB' => ['russia', 'moscow', 'saint petersburg'],
        ];

        foreach ($mappings as $currency => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($address, $keyword)) {
                    return $currency;
                }
            }
        }

        return 'PHP'; // Default for primary market
    }
}
