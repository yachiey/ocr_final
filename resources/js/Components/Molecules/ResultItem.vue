<script setup>
defineProps({
    name: {
        type: String,
        default: 'Unknown Item',
    },
    price: {
        type: String,
        default: '-',
    },
});
</script>

<template>
    <li class="flex justify-between items-center text-sm border-b border-gray-200 dark:border-gray-600 pb-2 last:border-0 last:pb-0">
        <span class="text-gray-700 dark:text-gray-300 font-medium whitespace-normal pr-4 flex-1 min-w-0">{{ name }}</span>
        <span class="text-gray-900 dark:text-white font-semibold">{{ price }}</span>
    </li>
</template>
