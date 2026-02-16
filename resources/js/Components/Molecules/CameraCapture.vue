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
            <div class="absolute bottom-0 left-0 w-full p-6 bg-gradient-to-t from-black/80 to-transparent flex flex-col gap-4 items-center">
                <div class="flex items-center gap-4">
                    <button 
                        @click="$emit('cancel')"
                        type="button"
                        class="p-3 bg-white/20 backdrop-blur-sm rounded-full text-white hover:bg-white/30 transition-all"
                        title="Cancel"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <button 
                        @click="captureImage"
                        type="button"
                        class="p-1 border-4 border-white rounded-full transition-transform active:scale-95"
                    >
                        <div class="w-16 h-16 bg-white rounded-full"></div>
                    </button>

                    <button 
                        @click="switchCamera"
                        type="button"
                        class="p-3 bg-white/20 backdrop-blur-sm rounded-full text-white hover:bg-white/30 transition-all"
                        title="Switch Camera"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
                <p class="text-white/80 text-sm">Align receipt securely</p>
            </div>
        </div>
    </div>
</template>
