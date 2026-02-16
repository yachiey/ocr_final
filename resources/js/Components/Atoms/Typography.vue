<script setup>
defineProps({
    variant: {
        type: String,
        default: 'body', // h1, h2, h3, body, caption
    },
    as: {
        type: String,
        default: null, // override the HTML tag
    },
});

const tags = {
    h1: 'h1',
    h2: 'h2',
    h3: 'h3',
    body: 'p',
    caption: 'span',
};

const classes = {
    h1: 'text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-6xl mb-4',
    h2: 'text-2xl font-bold text-gray-900 mb-6',
    h3: 'text-xl font-bold text-gray-900 mb-2',
    body: 'text-base text-gray-500 sm:text-lg',
    caption: 'text-sm text-gray-500',
};
</script>

<template>
    <component :is="as || tags[variant]" :class="classes[variant]">
        <slot />
    </component>
</template>
