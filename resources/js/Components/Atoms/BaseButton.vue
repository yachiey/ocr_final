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
    primary: 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300',
    secondary: 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    white: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
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
