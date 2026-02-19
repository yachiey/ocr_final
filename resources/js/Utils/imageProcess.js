/**
 * Preprocesses an image file for OCR.
 * Resizes the image to a maximum dimension (default 2400px), applies sharpening
 * and contrast enhancement, and optimizes quality for text extraction.
 * This ensures consistency between gallery uploads and camera captures.
 */
export const preprocessImage = (file, maxDimension = 1600) => {
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

                // Calculate new dimensions
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
                const ctx = canvas.getContext('2d');

                // Draw image to canvas (this also flattens orientation)
                ctx.drawImage(img, 0, 0, width, height);

                // Apply contrast enhancement for better text readability
                applyContrastEnhancement(ctx, width, height);

                // Apply sharpening to counteract canvas resampling softness
                applySharpen(ctx, width, height);

                // Convert canvas back to blob
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
                }, 'image/jpeg', 0.85); // 85% quality is a sweet spot for OCR
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

/**
 * Applies a mild contrast enhancement to make text stand out.
 * Uses a simple linear contrast stretch.
 */
function applyContrastEnhancement(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const factor = 1.2; // Mild contrast boost (1.0 = no change)
    const intercept = 128 * (1 - factor);

    for (let i = 0; i < data.length; i += 4) {
        data[i] = clamp(factor * data[i] + intercept);     // R
        data[i + 1] = clamp(factor * data[i + 1] + intercept); // G
        data[i + 2] = clamp(factor * data[i + 2] + intercept); // B
    }

    ctx.putImageData(imageData, 0, 0);
}

/**
 * Applies a 3x3 unsharp-mask-style sharpening kernel to enhance text edges.
 * Uses a lightweight convolution that sharpens without introducing excessive noise.
 */
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

/**
 * Clamps a value to [0, 255].
 */
function clamp(val) {
    return Math.max(0, Math.min(255, Math.round(val)));
}
