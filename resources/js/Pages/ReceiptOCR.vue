<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import OcrUploadSection from '@/Components/Organisms/OcrUploadSection.vue';
import OcrResultsSection from '@/Components/Organisms/OcrResultsSection.vue';

const imagePreview = ref(null);
const processing = ref(false);
const results = ref(null);
const error = ref(null);
const darkMode = ref(false);
let errorTimer = null;

const form = useForm({
    image: null,
});

onMounted(() => {
    darkMode.value = document.documentElement.classList.contains('dark');
});

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    localStorage.setItem('darkMode', darkMode.value);
};

const showError = (message) => {
    error.value = message;
    clearTimeout(errorTimer);
    errorTimer = setTimeout(() => { error.value = null; }, 8000);
};

const dismissError = () => {
    error.value = null;
    clearTimeout(errorTimer);
};

const handleFileSelect = (file) => {
    if (!file.type.startsWith('image/')) {
        showError('Please upload an image file. Supported formats: JPG, PNG, HEIC.');
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
        showError(err.response?.data?.error || err.message || 'Failed to process image. Please try again.');
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

    <div class="bg-gradient-to-br from-slate-50 via-primary-50/30 to-brand-light-blue/10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100 min-h-screen flex justify-center font-sans antialiased selection:bg-primary-500 selection:text-white transition-colors duration-300">
        <div class="w-full max-w-4xl px-4 sm:px-6 py-8 relative">

            <!-- Dark mode toggle -->
            <div class="flex justify-end mb-4 md:mb-0 md:absolute md:top-6 md:right-6 z-20">
                <button 
                    @click="toggleDarkMode" 
                    class="relative w-16 h-8 rounded-full bg-brand-light-blue/20 dark:bg-gray-700/50 backdrop-blur-sm border border-brand-light-blue/30 dark:border-gray-600 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                >
                    <!-- Sliding Thumb -->
                    <div 
                        class="absolute left-1 top-1 w-6 h-6 bg-white dark:bg-gray-800 rounded-full shadow-md transform transition-transform duration-300 flex items-center justify-center z-0"
                        :class="darkMode ? 'translate-x-8' : 'translate-x-0'"
                    >
                    </div>

                    <!-- Icons (Fixed positions) -->
                    <div class="relative z-10 flex justify-between items-center w-full h-full px-1.5 pointer-events-none">
                        <!-- Sun Icon -->
                        <div class="w-6 h-6 flex items-center justify-center">
                            <svg class="w-4 h-4 transition-colors duration-300" :class="!darkMode ? 'text-brand-orange' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>

                        <!-- Moon Icon -->
                        <div class="w-6 h-6 flex items-center justify-center">
                            <svg class="w-4 h-4 transition-colors duration-300" :class="darkMode ? 'text-brand-light-blue' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                    </div>
                </button>
            </div>
            
            <!-- Header with entrance animation -->
            <div class="animate-fade-up">
                <PageHeader />
            </div>

            <!-- Inline Error Toast -->
            <Transition
                enter-active-class="animate-slide-in"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div v-if="error" class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4 flex items-start gap-3 shadow-sm">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ error }}</p>
                    </div>
                    <button @click="dismissError" class="flex-shrink-0 text-red-400 hover:text-red-600 dark:hover:text-red-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </Transition>

            <!-- Upload section with entrance animation -->
            <div class="animate-fade-up-delay">
                <OcrUploadSection 
                    v-if="!results" 
                    :image-preview="imagePreview"
                    :processing="processing"
                    @file-selected="handleFileSelect"
                    @remove="resetUI"
                    @process="processFile"
                />
            </div>

            <OcrResultsSection 
                v-if="results" 
                :results="results"
                :image-preview="imagePreview"
                @reset="resetUI"
            />
            
            <!-- Footer -->
            <p class="text-center text-gray-400 dark:text-gray-500 text-sm mt-12 pb-6">
                &copy; {{ new Date().getFullYear() }} Receipt OCR. All rights reserved.
            </p>

        </div>
    </div>
</template>

