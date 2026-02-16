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
    <li class="flex justify-between items-center text-sm border-b border-gray-200 pb-2 last:border-0 last:pb-0">
        <span class="text-gray-700 font-medium truncate w-2/3">{{ name }}</span>
        <span class="text-gray-900 font-semibold">{{ price }}</span>
    </li>
</template>
