<script setup>
import Card from '@/Components/Atoms/Card.vue';
import FileUpload from '@/Components/Molecules/FileUpload.vue';
import ImagePreview from '@/Components/Molecules/ImagePreview.vue';
import CameraCapture from '@/Components/Molecules/CameraCapture.vue';
import { ref } from 'vue';

defineProps({
    imagePreview: {
        type: String,
        default: null,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['file-selected', 'remove', 'process']);

const showCamera = ref(false);

const handleCapture = (file) => {
    showCamera.value = false;
    emit('file-selected', file);
};
</script>

<template>
    <Card class="transition-all duration-300">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-light-blue to-brand-dark-blue opacity-80"></div>
        
        <div class="p-4 sm:p-8 md:p-12">
            <template v-if="showCamera">
                <CameraCapture 
                    @capture="handleCapture"
                    @cancel="showCamera = false"
                />
            </template>

            <template v-else>
                <FileUpload 
                    v-if="!imagePreview"
                    @file-selected="$emit('file-selected', $event)"
                    @open-camera="showCamera = true"
                />

                <ImagePreview 
                    v-else
                    :image-src="imagePreview"
                    :processing="processing"
                    @remove="$emit('remove')"
                    @process="$emit('process')"
                />
            </template>
        </div>
    </Card>
</template>
