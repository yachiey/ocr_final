<script setup>
import BaseButton from '@/Components/Atoms/BaseButton.vue';

defineProps({
    imageSrc: {
        type: String,
        required: true,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['remove', 'process']);
</script>

<template>
    <div class="w-full flex flex-col items-center">
        <!-- Image with scanning overlay -->
        <div class="relative mb-6 rounded-lg overflow-hidden" :class="{ 'animate-pulse-glow': processing }">
            <img :src="imageSrc" alt="Receipt Preview" class="max-h-[400px] rounded-lg shadow-md object-contain transition-all duration-300" :class="{ 'brightness-75': processing }" />
            
            <!-- Scanning overlay -->
            <div v-if="processing" class="absolute inset-0 pointer-events-none">
                <div class="absolute inset-0 bg-primary-900/10 rounded-lg"></div>
                <div class="absolute left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-brand-light-blue to-transparent animate-scan-line shadow-[0_0_8px_2px_rgba(95,167,214,0.4)]"></div>
            </div>
        </div>

        <div class="flex gap-4">
            <BaseButton 
                variant="white"
                @click.stop="$emit('remove')" 
                type="button"
                :disabled="processing"
            >
                Remove
            </BaseButton>
            <BaseButton 
                @click.stop="$emit('process')" 
                :disabled="processing"
                type="button"
            >
                <div class="flex items-center gap-2">
                    <!-- Spinner icon -->
                    <svg v-if="processing" class="w-4 h-4 animate-spinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ processing ? 'Analyzing Receipt...' : 'Process Receipt' }}
                </div>
            </BaseButton>
        </div>
    </div>
</template>
