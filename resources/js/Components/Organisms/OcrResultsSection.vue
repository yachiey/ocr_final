<script setup>
import Card from '@/Components/Atoms/Card.vue';
import Typography from '@/Components/Atoms/Typography.vue';
import ResultItem from '@/Components/Molecules/ResultItem.vue';
import BaseButton from '@/Components/Atoms/BaseButton.vue';
import { ref } from 'vue';

const props = defineProps({
    results: {
        type: Object,
        required: true,
    },
    imagePreview: {
        type: String,
        default: null,
    }
});

defineEmits(['reset']);

const copied = ref({});
const showOriginal = ref(false);

const copyToClipboard = async (text, key) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value[key] = true;
        setTimeout(() => { copied.value[key] = false; }, 2000);
    } catch (err) {
        console.error('Copy failed', err);
    }
};

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return 'N/A';
    const currency = props.results.parsed?.totals?.currency || props.results.parsed?.currency || 'USD';
    const symbols = {
        'USD': '$',
        'PHP': '₱',
        'EUR': '€',
        'JPY': '¥',
        'GBP': '£',
        'AUD': 'A$',
        'CAD': 'C$',
        'CNY': '¥',
        'INR': '₹',
        'KRW': '₩',
        'SGD': 'S$',
        'NZD': 'NZ$',
        'THB': '฿',
        'VND': '₫',
        'IDR': 'Rp',
        'MYR': 'RM',
        'HKD': 'HK$',
        'TWD': 'NT$',
        'CHF': 'Fr',
        'RUB': '₽',
        'BRL': 'R$',
        'MXN': 'Mx$',
        'ZAR': 'R',
    };
    const symbol = symbols[currency] || '$';
    return `${symbol}${amount}`;
};
</script>

<template>
    <div class="w-full max-w-4xl pb-12 transition-all duration-300 ease-in-out">
        <div class="flex flex-col items-start space-y-6">
            
            <!-- Extraction Results -->
            <div class="w-full">
                <Card>
                    <div class="p-4 sm:p-8 md:p-12 relative">
                        
                        <!-- Top Right View Original Button -->
                        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-10">
                            <button 
                                v-if="imagePreview"
                                @click="showOriginal = true"
                                class="flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-200 hover:text-primary-600 dark:hover:text-primary-400 hover:border-primary-200 dark:hover:border-primary-500 transition-all duration-200 text-xs sm:text-sm font-medium"
                            >
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <span class="hidden sm:inline">View Original</span>
                                <span class="sm:hidden">View</span>
                            </button>
                        </div>

                        <div class="flex flex-col items-center justify-center mb-8 text-center">
                            <div class="relative flex items-center justify-center w-16 h-16 rounded-full bg-green-50 dark:bg-green-900/20 text-green-500 mb-4 shadow-lg shadow-green-500/10 ring-1 ring-green-500/20 animate-fade-in-up">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <Typography variant="h2" class="mb-0 text-2xl sm:text-3xl animate-fade-in-up" style="animation-delay: 100ms">
                                Extraction Results
                            </Typography>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Key Details -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600">
                                    <Typography variant="caption" class="mb-1">Merchant</Typography>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ results.parsed?.merchant?.name || 'N/A' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ results.parsed?.merchant?.address || '' }}</p>
                                    <p v-if="results.parsed?.merchant?.tax_id" class="text-xs text-gray-400 mt-2 font-mono">TIN: {{ results.parsed?.merchant?.tax_id }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600">
                                        <Typography variant="caption" class="mb-1">Date</Typography>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ results.parsed?.transaction?.date || 'N/A' }}</p>
                                        <p v-if="results.parsed?.transaction?.time" class="text-xs text-gray-500">{{ results.parsed?.transaction?.time }}</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600">
                                        <Typography variant="caption" class="mb-1">Total</Typography>
                                        <p class="text-lg font-semibold text-primary-600">{{ formatCurrency(results.parsed?.totals?.total) }}</p>
                                    </div>
                                </div>
                                <!-- Extra Details -->
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                     <div v-if="results.parsed?.transaction?.invoice_number" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider font-semibold">Invoice #</span>
                                        <span class="font-mono text-gray-700 dark:text-gray-300">{{ results.parsed?.transaction?.invoice_number }}</span>
                                    </div>
                                     <div v-if="results.parsed?.payment?.authorization_code" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                                        <span class="block text-xs text-gray-400 uppercase tracking-wider font-semibold">Auth Code</span>
                                        <span class="font-mono text-gray-700 dark:text-gray-300">{{ results.parsed?.payment?.authorization_code }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600 h-full flex flex-col">
                                 <div class="flex justify-between items-end mb-4">
                                     <Typography variant="caption">Items</Typography>
                                     <span class="text-xs text-gray-400">{{ results.parsed?.items?.length || 0 }} items</span>
                                 </div>
                                 <ul class="space-y-3 flex-1 overflow-y-auto pr-2 custom-scrollbar max-h-[300px]">
                                    <li v-if="!results.parsed?.items?.length" class="text-gray-400 dark:text-gray-500 text-sm italic py-4 text-center">No items detected</li>
                                    <ResultItem 
                                        v-else 
                                        v-for="(item, index) in results.parsed.items" 
                                        :key="index"
                                        :name="item.name"
                                        :price="formatCurrency(item.total_price || item.unit_price)"
                                    />
                                 </ul>
                                 <!-- Subtotal/Tax Summary at bottom of items -->
                                 <div v-if="results.parsed?.totals" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 text-sm space-y-1">
                                    <div v-if="results.parsed.totals.subtotal" class="flex justify-between text-gray-500">
                                        <span>Subtotal</span>
                                        <span>{{ formatCurrency(results.parsed.totals.subtotal) }}</span>
                                    </div>
                                    <div v-if="results.parsed.totals.vatable_sales" class="flex justify-between text-gray-500">
                                        <span>VATable Sales</span>
                                        <span>{{ formatCurrency(results.parsed.totals.vatable_sales) }}</span>
                                    </div>
                                    <div v-if="results.parsed.totals.vat_amount" class="flex justify-between text-gray-500">
                                        <span>VAT Amount</span>
                                        <span>{{ formatCurrency(results.parsed.totals.vat_amount) }}</span>
                                    </div>
                                 </div>
                            </div>
                        </div>

                        <!-- Full Text Section -->
                        <div class="mt-8 bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-2">
                                <Typography variant="caption">Full Extracted Text</Typography>
                                <button
                                    @click="copyToClipboard(results.parsed?.full_text || '', 'text')"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-150"
                                    title="Copy text"
                                >
                                    <svg v-if="!copied.text" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg v-else class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                            <div class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap font-mono bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-600 max-h-[300px] overflow-y-auto">
                                {{ results.parsed?.full_text || 'No text extracted' }}
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <details class="group">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    <span>View Raw Response</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="text-gray-500 dark:text-gray-400 mt-3 group-open:animate-fadeIn relative">
                                     <button
                                        @click="copyToClipboard(JSON.stringify(results.parsed || results, null, 2), 'json')"
                                        class="absolute top-2 right-2 p-1.5 rounded-lg text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all duration-150 z-10"
                                        title="Copy JSON"
                                     >
                                        <svg v-if="!copied.json" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg v-else class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                     </button>
                                     <pre class="bg-gray-50 dark:bg-gray-900/50 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 p-4 rounded-xl overflow-x-auto text-sm font-mono">{{ JSON.stringify(results.parsed || results, null, 2) }}</pre>
                                </div>
                            </details>
                        </div>
                        
                        <BaseButton 
                            variant="primary" 
                            @click="$emit('reset')" 
                            class="mt-8 w-full"
                        >
                            Scan Another Receipt
                        </BaseButton>
                    </div>
                </Card>
            </div>
        </div>
        
        <!-- Image Modal Overlay -->
        <div 
            v-if="showOriginal && imagePreview" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm transition-opacity duration-300"
            @click.self="showOriginal = false"
        >
            <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center animate-fade-in-up">
                <button 
                    @click="showOriginal = false"
                    class="absolute -top-10 right-0 md:-right-12 text-white/70 hover:text-white transition-colors p-2 bg-black/20 rounded-full md:bg-transparent"
                >
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <img 
                    :src="imagePreview" 
                    alt="Original Receipt" 
                    class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl" 
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
