<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, danger
        validator: (value) => ['primary', 'secondary', 'danger', 'white'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = {
    primary: 'bg-primary-600 text-white shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:shadow-primary-600/40 hover:-translate-y-0.5 active:translate-y-0 active:shadow-primary-600/20 ring-1 ring-primary-500/20',
    secondary: 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm hover:shadow-md ring-1 ring-black/5',
    danger: 'bg-red-600 text-white shadow-lg shadow-red-500/30 hover:bg-red-700 hover:shadow-red-600/40 hover:-translate-y-0.5 ring-1 ring-red-500/20',
    white: 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm hover:shadow',
};
</script>

<template>
    <button
        :type="type"
        :class="[
            'px-6 py-2.5 font-medium rounded-xl transition-all duration-200 transform',
            variantClasses[variant],
            disabled ? 'opacity-75 cursor-not-allowed' : 'hover:-translate-y-0.5'
        ]"
        :disabled="disabled"
    >
        <slot />
    </button>
</template>
