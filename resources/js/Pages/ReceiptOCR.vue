<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import OcrUploadSection from '@/Components/Organisms/OcrUploadSection.vue';
import OcrResultsSection from '@/Components/Organisms/OcrResultsSection.vue';

const imagePreview = ref(null);
const processing = ref(false);
const results = ref(null);
const error = ref(null);

const form = useForm({
    image: null,
});

const handleFileSelect = (file) => {
    if (!file.type.startsWith('image/')) {
        alert('Please upload an image file.');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    
    // Reset state
    results.value = null;
    error.value = null;
    form.image = file;
};

const processFile = async () => {
    if (!form.image) return;

    processing.value = true;
    error.value = null;

    try {
        const formData = new FormData();
        formData.append('image', form.image);

        const response = await axios.post('/ocr/extract', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        results.value = response.data;
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.error || err.message || 'Failed to process image';
        alert('Error: ' + error.value);
    } finally {
        processing.value = false;
    }
};

const resetUI = () => {
    form.image = null;
    imagePreview.value = null;
    results.value = null;
    error.value = null;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
    <Head title="Receipt OCR" />

    <div class="bg-gray-50 text-gray-900 min-h-screen flex items-center justify-center font-sans antialiased selection:bg-indigo-500 selection:text-white">
        <div class="w-full max-w-4xl px-6 py-12">
            
            <PageHeader />

            <OcrUploadSection 
                v-if="!results" 
                :image-preview="imagePreview"
                :processing="processing"
                @file-selected="handleFileSelect"
                @remove="resetUI"
                @process="processFile"
            />

            <OcrResultsSection 
                v-if="results" 
                :results="results"
                @reset="resetUI"
            />
            
            <!-- Footer -->
            <p class="text-center text-gray-400 text-sm mt-12 pb-6">
                &copy; {{ new Date().getFullYear() }} Receipt OCR. All rights reserved.
            </p>

        </div>
    </div>
</template>
