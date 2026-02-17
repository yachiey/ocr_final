<script setup>
defineProps({
    variant: {
        type: String,
        default: 'body', // h1, h2, h3, body, caption, gradient
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
    h1: 'text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl mb-6',
    h2: 'text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-4',
    h3: 'text-xl font-bold tracking-tight text-gray-900 dark:text-white mb-2',
    body: 'text-base text-gray-600 dark:text-gray-300 sm:text-lg leading-relaxed',
    caption: 'text-sm text-gray-500 dark:text-gray-400',
    gradient: 'bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-brand-light-blue',
};
</script>

<template>
    <component :is="as || tags[variant]" :class="classes[variant]">
        <slot />
    </component>
</template>
