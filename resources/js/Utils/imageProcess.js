// resize and clean up an image before sending it to the OCR API
// only applies sharpening/contrast to big images (desktop scans)
// mobile photos are left alone since heavy processing hurts text quality
export const preprocessImage = (file, maxDimension = 2400) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // scale down if too big
                if (width > height) {
                    if (width > maxDimension) {
                        height *= maxDimension / width;
                        width = maxDimension;
                    }
                } else {
                    if (height > maxDimension) {
                        width *= maxDimension / height;
                        height = maxDimension;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });

                // draw image to canvas (also fixes mobile rotation issues)
                ctx.drawImage(img, 0, 0, width, height);

                const originalMaxDim = Math.max(img.width, img.height);

                // all images get grayscale + mild contrast (helps OCR on receipts)
                applyGrayscale(ctx, width, height);
                applyContrastEnhancement(ctx, width, height);

                // only sharpen big images (desktop scans) — hurts small phone photos
                if (originalMaxDim > 2000) {
                    applySharpen(ctx, width, height);
                }

                // convert back to JPEG
                canvas.toBlob((blob) => {
                    if (blob) {
                        const processedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        resolve(processedFile);
                    } else {
                        reject(new Error('Canvas to Blob conversion failed'));
                    }
                }, 'image/jpeg', 0.92); // 92% quality preserves text detail for OCR
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

// convert to grayscale — strips color noise and makes text stand out more
function applyGrayscale(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;

    for (let i = 0; i < data.length; i += 4) {
        // standard luminance weighting (human eye is most sensitive to green)
        const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        data[i] = gray;       // R
        data[i + 1] = gray;   // G
        data[i + 2] = gray;   // B
    }

    ctx.putImageData(imageData, 0, 0);
}

// bump up contrast a little so text pops more
function applyContrastEnhancement(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const factor = 1.1; // keep it mild so we don't blow out the text
    const intercept = 128 * (1 - factor);

    for (let i = 0; i < data.length; i += 4) {
        data[i] = clamp(factor * data[i] + intercept);     // R
        data[i + 1] = clamp(factor * data[i + 1] + intercept); // G
        data[i + 2] = clamp(factor * data[i + 2] + intercept); // B
    }

    ctx.putImageData(imageData, 0, 0);
}

// sharpen edges so text is crisper after resizing
function applySharpen(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const src = imageData.data;
    const output = new Uint8ClampedArray(src);

    // Sharpen kernel:  0 -1  0
    //                 -1  5 -1
    //                  0 -1  0
    const kernel = [0, -1, 0, -1, 5, -1, 0, -1, 0];
    const kSize = 3;
    const half = Math.floor(kSize / 2);

    for (let y = half; y < height - half; y++) {
        for (let x = half; x < width - half; x++) {
            let r = 0, g = 0, b = 0;
            for (let ky = -half; ky <= half; ky++) {
                for (let kx = -half; kx <= half; kx++) {
                    const idx = ((y + ky) * width + (x + kx)) * 4;
                    const ki = (ky + half) * kSize + (kx + half);
                    r += src[idx] * kernel[ki];
                    g += src[idx + 1] * kernel[ki];
                    b += src[idx + 2] * kernel[ki];
                }
            }
            const outIdx = (y * width + x) * 4;
            output[outIdx] = clamp(r);
            output[outIdx + 1] = clamp(g);
            output[outIdx + 2] = clamp(b);
        }
    }

    const outputData = new ImageData(output, width, height);
    ctx.putImageData(outputData, 0, 0);
}

// keep pixel values in valid range
function clamp(val) {
    return Math.max(0, Math.min(255, Math.round(val)));
}
