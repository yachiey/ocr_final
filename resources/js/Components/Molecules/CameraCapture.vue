<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import BaseButton from '@/Components/Atoms/BaseButton.vue';

const emit = defineEmits(['capture', 'cancel']);

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const error = ref(null);
const facingMode = ref('environment'); // 'user' or 'environment'

    const isFlashOn = ref(false);
    const hasFlash = ref(false);

    const startCamera = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            error.value = "Camera access is not supported in this browser or requires a secure connection (HTTPS). If you are testing on mobile via a local IP, this is expected.";
            return;
        }

        try {
            if (stream.value) {
                stopCamera();
            }
            
            const constraints = {
                video: {
                    facingMode: facingMode.value,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            stream.value = await navigator.mediaDevices.getUserMedia(constraints);
            
            // Check for flash capability
            const track = stream.value.getVideoTracks()[0];
            const capabilities = track.getCapabilities();
            hasFlash.value = !!capabilities.torch;
            isFlashOn.value = false;

            if (videoRef.value) {
                videoRef.value.srcObject = stream.value;
            }
            error.value = null;
        } catch (err) {
            console.error("Camera error:", err);
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                 error.value = "Camera permission denied. Please allow access in your browser settings.";
            } else {
                 error.value = "Unable to access camera. Ensure no other app is using it.";
            }
        }
    };

    const stopCamera = () => {
        if (stream.value) {
            stream.value.getTracks().forEach(track => {
                track.stop();
            });
            stream.value = null;
        }
    };

    const toggleFlash = async () => {
        if (!stream.value) return;
        const track = stream.value.getVideoTracks()[0];
        isFlashOn.value = !isFlashOn.value;
        await track.applyConstraints({
            advanced: [{ torch: isFlashOn.value }]
        });
    };

    const switchCamera = () => {
        facingMode.value = facingMode.value === 'user' ? 'environment' : 'user';
        startCamera();
    };

    const captureImage = () => {
        if (!videoRef.value || !canvasRef.value) return;

        const video = videoRef.value;
        const canvas = canvasRef.value;
        const context = canvas.getContext('2d');

        // Set canvas dimensions to match video stream
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw video frame to canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Turn off flash after capture if it was on
        if (isFlashOn.value) {
            toggleFlash(); 
        }

        // Convert to file
        canvas.toBlob((blob) => {
            if (blob) {
                const file = new File([blob], `capture-${Date.now()}.jpg`, { type: 'image/jpeg' });
                emit('capture', file);
                stopCamera();
            }
        }, 'image/jpeg', 0.95);
    };

    onMounted(() => {
        startCamera();
    });

    onUnmounted(() => {
        stopCamera();
    });
</script>

<template>
    <div class="relative w-full bg-black rounded-xl overflow-hidden shadow-xl aspect-[9/16] max-h-[80vh]">
        <div v-if="error" class="p-8 text-center text-white bg-red-600 h-full flex flex-col justify-center items-center">
            <p>{{ error }}</p>
            <BaseButton variant="white" class="mt-4" @click="$emit('cancel')">Close</BaseButton>
        </div>

        <div v-else class="relative h-full">
            <video 
                ref="videoRef" 
                autoplay 
                playsinline 
                class="w-full h-full object-cover"
            ></video>
            
            <canvas ref="canvasRef" class="hidden"></canvas>

            <!-- Controls Overlay -->
            <div class="absolute bottom-0 left-0 w-full px-6 pb-6 pt-20 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex flex-col gap-4 items-center">
                <div class="flex items-center justify-between w-full max-w-xs px-4">
                    <!-- Flash Button (Left) -->
                     <button 
                        v-if="hasFlash"
                        @click="toggleFlash"
                        type="button"
                        class="p-3 rounded-full text-white transition-all duration-200 active:scale-90"
                        :class="isFlashOn ? 'bg-yellow-400 text-black' : 'bg-white/20 backdrop-blur-md hover:bg-white/30'"
                        title="Toggle Flash"
                    >
                        <svg v-if="isFlashOn" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"></path></svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </button>
                    <div v-else class="w-12"></div> <!-- Spacer -->

                    <!-- Capture Button (Center) -->
                    <button 
                        @click="captureImage"
                        type="button"
                        class="p-1 border-4 border-white rounded-full transition-all duration-150 active:scale-95 hover:border-primary-300"
                    >
                        <div class="w-14 h-14 bg-white rounded-full hover:bg-gray-200 transition-colors"></div>
                    </button>

                    <!-- Switch Camera Button (Right) -->
                    <button 
                        @click="switchCamera"
                        type="button"
                        class="p-3 bg-white/20 backdrop-blur-md rounded-full text-white hover:bg-white/30 transition-all duration-200 active:scale-90"
                        title="Switch Camera"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
                
                <!-- Cancel Button (Bottom) -->
                <button 
                    @click="$emit('cancel')"
                    class="mt-2 text-white/80 text-sm font-medium hover:text-white px-4 py-2 rounded-full hover:bg-white/10 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>
