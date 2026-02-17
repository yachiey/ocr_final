<script setup>

import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <span class="text-3xl font-bold text-gray-500">Logo</span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-4 py-4 shadow-md sm:max-w-md sm:rounded-lg sm:px-6"
        >
            <slot />
        </div>
    </div>
</template>
