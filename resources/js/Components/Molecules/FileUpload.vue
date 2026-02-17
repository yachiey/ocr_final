<script setup>
import BaseButton from '@/Components/Atoms/BaseButton.vue';
import { ref } from 'vue';

const emit = defineEmits(['file-selected', 'open-camera']);

const fileInput = ref(null);
const dragging = ref(false);

const triggerFileInput = () => {
    fileInput.value.click();
};

const onFileChange = (e) => {
    const files = e.target.files || e.dataTransfer.files;
    if (files.length) {
        emit('file-selected', files[0]);
    }
};

const onDrop = (e) => {
    dragging.value = false;
    onFileChange(e);
};
</script>

<template>
    <div 
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="onDrop"
        :class="{'border-primary-500 bg-primary-50 dark:bg-primary-900/20': dragging, 'border-gray-300 dark:border-gray-600': !dragging}"
        class="border-3 border-dashed rounded-2xl p-8 sm:p-12 text-center transition-all duration-300 group flex flex-col items-center justify-center min-h-[300px] hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-gray-700/50"
    >
        <input 
            ref="fileInput"
            type="file" 
            class="hidden" 
            accept="image/*" 
            @change="onFileChange"
        />
        
        <div class="flex flex-col items-center transition-opacity duration-300 w-full">
            <!-- Receipt scan icon with float animation -->
            <div class="w-20 h-20 bg-primary-50 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-6 group-hover:animate-float transition-transform duration-300">
                <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 3v5a1 1 0 001 1h5" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Upload a Receipt</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4 max-w-xs">Drag and drop your image here, or choose an option below</p>
            
            <!-- File type badges -->
            <div class="flex flex-wrap justify-center gap-2 mb-6">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-700">JPG</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-700">PNG</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-700">HEIC</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-700">WEBP</span>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                <BaseButton type="button" @click="triggerFileInput">
                    Browse Files
                </BaseButton>
                <div class="text-gray-400 dark:text-gray-500 font-medium flex items-center justify-center sm:hidden">- OR -</div>
                <div class="tooltip-wrap">
                    <span class="tooltip-text bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800">Best on mobile devices</span>
                    <BaseButton type="button" variant="secondary" @click="$emit('open-camera')">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Use Camera
                        </div>
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>
