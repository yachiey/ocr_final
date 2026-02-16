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
        :class="{'border-indigo-500 bg-indigo-50': dragging, 'border-gray-300': !dragging}"
        class="border-3 border-dashed rounded-2xl p-8 sm:p-12 text-center transition-all duration-300 group flex flex-col items-center justify-center min-h-[300px] hover:border-indigo-500 hover:bg-gray-50"
    >
        <input 
            ref="fileInput"
            type="file" 
            class="hidden" 
            accept="image/*" 
            @change="onFileChange"
        />
        
        <div class="flex flex-col items-center transition-opacity duration-300 w-full">
            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
               <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Upload a Receipt</h3>
            <p class="text-gray-500 mb-6 max-w-xs">Drag and drop your image here, or choose an option below</p>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                <BaseButton type="button" @click="triggerFileInput">
                    Browse Files
                </BaseButton>
                <div class="text-gray-400 font-medium flex items-center justify-center sm:hidden">- OR -</div>
                <BaseButton type="button" variant="secondary" @click="$emit('open-camera')">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Use Camera
                    </div>
                </BaseButton>
            </div>
        </div>
    </div>
</template>
