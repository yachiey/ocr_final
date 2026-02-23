<?php

namespace App\Http\Controllers;

use App\Models\OcrResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// handles receipt scanning — sends image to Groq API and parses the result
class OcrController extends Controller
{
    // main endpoint: takes an uploaded receipt image and returns structured data
    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            // convert uploaded image to base64 for the API
            $image = $request->file('image');
            $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();
            $dataUrl = "data:{$mimeType};base64,{$base64Image}";
            $storedImagePath = 'storage/' . $image->store('ocr_images', 'public');

            $apiKey = env('GROQ_OCR');
            $model = 'meta-llama/llama-4-scout-17b-16e-instruct';


            if (!$apiKey) {
                return response()->json(['error' => 'Groq API Key (GROQ_OCR) not configured.'], 500);
            }

            // send image to Groq vision API with our extraction prompt
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

            // try to parse the JSON — the LLM sometimes wraps it in markdown code blocks
            $cleanedContent = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
            $decoded = json_decode($cleanedContent, true);

            // fallback: strip all backticks and try to find a JSON object
            if (json_last_error() !== JSON_ERROR_NONE) {
                $stripped = str_replace('`', '', $content);
                if (preg_match('/\{.*\}/s', $stripped, $matches)) {
                    $decoded = json_decode($matches[0], true);
                }
            }

            // last resort: if JSON still broken, just show the raw text so the user sees something
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                Log::warning('OCR JSON parse failed', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200),
                ]);
                $decoded = [
                    'store_name' => null,
                    'date' => null,
                    'total_amount' => null,
                    'currency' => null,
                    'items' => [],
                    'lines' => [],
                    'full_text' => $content
                ];
            }

            // --- post-processing: fix totals that the LLM might have gotten wrong ---

            // add up item prices so we can cross-check later
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

            // safely grab a float from totals (returns null if missing)
            $getFloat = function ($key) use ($decoded) {
                $val = $decoded['totals'][$key] ?? null;
                return ($val !== null && is_numeric($val)) ? (float) $val : null;
            };

            $subtotal = $getFloat('subtotal');
            $tax = $getFloat('tax');
            $vatAmount = $getFloat('vat_amount');
            $vatableSales = $getFloat('vatable_sales');
            $total = $getFloat('total');

            // use whichever tax value we have (prefer tax over vat_amount)
            $effectiveTax = $tax ?? $vatAmount ?? 0;

            // log what the LLM gave us (useful for debugging weird receipts)
            Log::info('OCR Post-Processing — LLM raw values', [
                'subtotal' => $subtotal,
                'total' => $total,
                'tax' => $tax,
                'vat_amount' => $vatAmount,
                'vatable_sales' => $vatableSales,
                'effectiveTax' => $effectiveTax,
                'calculatedItemSum' => $calculatedItemSum,
            ]);

            // handle VAT-inclusive receipts (common in PH)
            // the LLM often double-counts VAT or swaps subtotal/total
            if ($total !== null && $subtotal !== null) {
                if (abs($total - ($subtotal + $effectiveTax)) < 0.05) {
                    // looks like total = subtotal + tax, which is normal
                    // but check if the LLM double-counted VAT on BIR receipts
                    if (
                        $vatableSales !== null && $vatAmount !== null
                        && $calculatedItemSum > 0 && abs($subtotal - $calculatedItemSum) < 1.00
                    ) {
                        // yep, LLM double-counted — subtotal is actually the real total
                        $total = $subtotal;
                        $subtotal = $total - $effectiveTax;
                    }
                    // otherwise it's genuinely total = subtotal + tax, all good
                } elseif (abs($total - $subtotal) < 0.05 && $effectiveTax > 0) {
                    // AI put the same value in both fields — figure out the real subtotal
                    if ($vatableSales !== null && $vatableSales > 0 && abs($total - ($vatableSales + $effectiveTax)) < 0.05) {
                        $subtotal = $vatableSales;
                    } elseif ($vatableSales !== null && $vatAmount !== null) {
                        // total already includes VAT, just fix subtotal
                        $subtotal = $total - $effectiveTax;
                    }
                } elseif ($total < $subtotal && abs($subtotal - ($total + $effectiveTax)) < 0.05) {
                    // AI swapped total and subtotal — flip them back
                    $temp = $total;
                    $total = $subtotal;
                    $subtotal = $temp;
                }
            }

            // if total is missing, try to calculate it
            if ($total === null) {
                if ($subtotal !== null) {
                    // check if subtotal is actually the VAT-inclusive total (BIR receipts)
                    if (
                        $vatableSales !== null && $vatAmount !== null
                        && $calculatedItemSum > 0 && abs($subtotal - $calculatedItemSum) < 1.00
                    ) {
                        // subtotal is really the total — don't add VAT again
                        $total = $subtotal;
                        $subtotal = $total - $effectiveTax;
                    } else {
                        $total = $subtotal + $effectiveTax;
                    }
                } elseif ($calculatedItemSum > 0) {
                    // same check but using item sum instead
                    if ($vatableSales !== null && $vatAmount !== null) {
                        $total = $calculatedItemSum;
                        $subtotal = $total - $effectiveTax;
                    } else {
                        $total = $calculatedItemSum + $effectiveTax;
                    }
                }
            }

            // if subtotal is missing, derive it from total
            if ($subtotal === null) {
                if ($total !== null) {
                    $subtotal = $total - $effectiveTax;
                } else {
                    $subtotal = $calculatedItemSum;
                }
            }

            // final sanity check: for PH receipts, VATable Sales + VAT = Total
            if ($vatableSales !== null && $vatAmount !== null && $total !== null) {
                if (abs($total - ($vatableSales + $vatAmount)) < 0.05) {
                    $subtotal = $vatableSales;
                }
            }

            // write the corrected values back
            $decoded['totals']['subtotal'] = $subtotal;
            $decoded['totals']['total'] = $total;

            // if the LLM couldn't figure out the currency, guess it from the address
            if (empty($decoded['totals']['currency'])) {
                $decoded['totals']['currency'] = $this->inferCurrencyFromAddress(
                    $decoded['merchant']['address'] ?? ''
                );
            }

            // rebuild full_text from lines if we have them
            if (isset($decoded['lines']) && is_array($decoded['lines']) && !empty($decoded['lines'])) {
                $decoded['full_text'] = implode("\n", $decoded['lines']);
            } elseif (empty($decoded['full_text']) && isset($decoded['lines'])) {
                $decoded['full_text'] = '';
            }

            $ocrResult = OcrResult::create($this->buildOcrResultPayload(
                $request,
                $decoded,
                $content,
                $storedImagePath
            ));

            return response()->json([
                'raw_text' => $content,
                'parsed' => $decoded,
                'saved_result_id' => (string) $ocrResult->getKey(),
            ]);



        } catch (\Throwable $e) {
            Log::error('OCR Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.';

            return response()->json(['error' => $errorMessage], 500);
        }
    }
    /**
     * Map OCR output to the persisted MongoDB document structure.
     */
    private function buildOcrResultPayload(
        Request $request,
        array $decoded,
        string $rawContent,
        string $storedImagePath
    ): array {
        $totalAmount = data_get($decoded, 'totals.total', data_get($decoded, 'total_amount'));
        $totalAmount = is_numeric($totalAmount) ? (float) $totalAmount : null;

        $items = data_get($decoded, 'items', []);
        if (!is_array($items)) {
            $items = [];
        }

        $lines = data_get($decoded, 'lines', []);
        if (!is_array($lines)) {
            $lines = [];
        }

        $rawText = data_get($decoded, 'full_text');
        if (!is_string($rawText) || $rawText === '') {
            $rawText = $rawContent;
        }

        return [
            'user_id' => $request->user()?->id,
            'store_name' => data_get($decoded, 'merchant.name', data_get($decoded, 'store_name')),
            'date' => data_get($decoded, 'transaction.date', data_get($decoded, 'date')),
            'total_amount' => $totalAmount,
            'items' => $items,
            'raw_text' => $rawText,
            'image_path' => $storedImagePath,
            'merchant' => data_get($decoded, 'merchant', []),
            'transaction' => data_get($decoded, 'transaction', []),
            'totals' => data_get($decoded, 'totals', []),
            'payment' => data_get($decoded, 'payment', []),
            'lines' => $lines,
        ];
    }

    /**
     * Infer ISO 4217 currency code from a merchant address string.
     * Falls back to 'PHP' (Philippine Peso) as the app's primary market.
     */
    private function inferCurrencyFromAddress(string $address): string
    {
        $address = strtolower($address);

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
