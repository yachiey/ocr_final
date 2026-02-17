<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import BaseButton from '@/Components/Atoms/BaseButton.vue';

const emit = defineEmits(['capture', 'cancel']);

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const error = ref(null);
const facingMode = ref('environment'); // 'user' or 'environment'

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
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
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
    <div class="relative w-full bg-black rounded-2xl overflow-hidden shadow-xl">
        <div v-if="error" class="p-8 text-center text-white bg-red-600">
            <p>{{ error }}</p>
            <BaseButton variant="white" class="mt-4" @click="$emit('cancel')">Close</BaseButton>
        </div>

        <div v-else class="relative">
            <video 
                ref="videoRef" 
                autoplay 
                playsinline 
                class="w-full h-auto max-h-[600px] object-cover"
            ></video>
            
            <canvas ref="canvasRef" class="hidden"></canvas>

            <!-- Controls Overlay -->
            <div class="absolute bottom-0 left-0 w-full px-6 pb-8 pt-20 bg-gradient-to-t from-black/90 via-black/50 to-transparent flex flex-col gap-5 items-center">
                <div class="flex items-end gap-8">
                    <!-- Cancel Button -->
                    <div class="flex flex-col items-center gap-1.5">
                        <button 
                            @click="$emit('cancel')"
                            type="button"
                            class="p-4 bg-white/25 backdrop-blur-md rounded-full text-white hover:bg-red-500/70 transition-all duration-200 active:scale-90"
                            title="Cancel"
                        >
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <span class="text-white/70 text-xs font-medium">Cancel</span>
                    </div>

                    <!-- Capture Button -->
                    <div class="flex flex-col items-center gap-1.5">
                        <button 
                            @click="captureImage"
                            type="button"
                            class="p-1.5 border-4 border-white rounded-full transition-all duration-150 active:scale-90 hover:border-primary-300"
                        >
                            <div class="w-16 h-16 bg-white rounded-full hover:bg-primary-100 transition-colors"></div>
                        </button>
                        <span class="text-white/70 text-xs font-medium">Capture</span>
                    </div>

                    <!-- Switch Camera Button -->
                    <div class="flex flex-col items-center gap-1.5">
                        <button 
                            @click="switchCamera"
                            type="button"
                            class="p-4 bg-white/25 backdrop-blur-md rounded-full text-white hover:bg-white/40 transition-all duration-200 active:scale-90"
                            title="Switch Camera"
                        >
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </button>
                        <span class="text-white/70 text-xs font-medium">Flip</span>
                    </div>
                </div>
                <p class="text-white/60 text-sm">Align receipt within frame</p>
            </div>
        </div>
    </div>
</template>
