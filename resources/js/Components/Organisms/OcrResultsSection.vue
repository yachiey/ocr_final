<script setup>
import Card from '@/Components/Atoms/Card.vue';
import Typography from '@/Components/Atoms/Typography.vue';
import ResultItem from '@/Components/Molecules/ResultItem.vue';
import BaseButton from '@/Components/Atoms/BaseButton.vue';

defineProps({
    results: {
        type: Object,
        required: true,
    },
});

defineEmits(['reset']);
</script>

<template>
    <div class="w-full max-w-4xl pb-12 transition-all duration-300">
        <Card>
            <div class="p-8 sm:p-12">
                <div class="flex items-center mb-6">
                    <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <Typography variant="h2" class="mb-0">Extraction Results</Typography>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Key Details -->
                    <div class="space-y-6">
                        <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                            <Typography variant="caption" class="mb-1">Store Name</Typography>
                            <p class="text-lg font-semibold text-gray-900">{{ results.parsed?.store_name || 'N/A' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                                <Typography variant="caption" class="mb-1">Date</Typography>
                                <p class="text-lg font-semibold text-gray-900">{{ results.parsed?.date || 'N/A' }}</p>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                                <Typography variant="caption" class="mb-1">Total</Typography>
                                <p class="text-lg font-semibold text-indigo-600">{{ results.parsed?.total_amount ? '$' + results.parsed.total_amount : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 h-full">
                         <Typography variant="caption" class="mb-4">Items</Typography>
                         <ul class="space-y-3 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                            <li v-if="!results.parsed?.items?.length" class="text-gray-400 text-sm italic">No items detected</li>
                            <ResultItem 
                                v-else 
                                v-for="(item, index) in results.parsed.items" 
                                :key="index"
                                :name="item.name"
                                :price="item.price ? '$' + item.price : '-'"
                            />
                         </ul>
                    </div>
                </div>

                <!-- Full Text Section -->
                <div class="mt-8 bg-gray-50 p-6 rounded-2xl border border-gray-100">
                    <Typography variant="caption" class="mb-2">Full Extracted Text</Typography>
                    <div class="text-gray-700 text-sm whitespace-pre-wrap font-mono bg-white p-4 rounded-xl border border-gray-200 max-h-[300px] overflow-y-auto">
                        {{ results.parsed?.full_text || 'No text extracted' }}
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <details class="group">
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none text-gray-500 hover:text-gray-900">
                            <span>View Raw Response</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                            </span>
                        </summary>
                        <div class="text-gray-500 mt-3 group-open:animate-fadeIn">
                             <pre class="bg-gray-800 text-gray-100 p-4 rounded-xl overflow-x-auto text-sm">{{ JSON.stringify(results.parsed || results, null, 2) }}</pre>
                        </div>
                    </details>
                </div>
                
                <BaseButton 
                    variant="white" 
                    @click="$emit('reset')" 
                    class="mt-8 w-full bg-gray-100"
                >
                    Scan Another Receipt
                </BaseButton>
            </div>
        </Card>
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
