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
        <img :src="imageSrc" alt="Receipt Preview" class="max-h-[400px] rounded-lg shadow-md object-contain mb-6" />
        <div class="flex gap-4">
            <BaseButton 
                variant="white"
                @click.stop="$emit('remove')" 
                type="button"
            >
                Remove
            </BaseButton>
            <BaseButton 
                @click.stop="$emit('process')" 
                :disabled="processing"
                type="button"
            >
                {{ processing ? 'Processing...' : 'Process Receipt' }}
            </BaseButton>
        </div>
    </div>
</template>
