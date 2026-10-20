<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    label: string;
    value: string | number;
    subtitle?: string;
    icon?: string;
    color?: 'teal' | 'orange' | 'blue' | 'emerald' | 'purple' | 'rose';
    trend?: {
        value: number;
        label: string;
    };
}

const props = withDefaults(defineProps<Props>(), {
    color: 'teal',
});

const colorClasses = computed(() => {
    const colors = {
        teal: 'bg-aadi-teal-50 text-aadi-teal-600',
        orange: 'bg-aadi-orange-50 text-aadi-orange-600',
        blue: 'bg-blue-50 text-blue-600',
        emerald: 'bg-emerald-50 text-emerald-600',
        purple: 'bg-purple-50 text-purple-600',
        rose: 'bg-rose-50 text-rose-600',
    };
    return colors[props.color];
});

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString();
    }
    return props.value;
});
</script>

<template>
    <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-xs hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-600">{{ label }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ formattedValue }}</p>
                
                <p v-if="subtitle" class="mt-1 text-sm text-slate-500">
                    {{ subtitle }}
                </p>

                <!-- Trend Indicator -->
                <div v-if="trend" class="mt-2 flex items-center gap-1">
                    <svg
                        v-if="trend.value > 0"
                        class="h-4 w-4 text-emerald-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    <svg
                        v-else-if="trend.value < 0"
                        class="h-4 w-4 text-red-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    <span
                        :class="[
                            'text-sm font-medium',
                            trend.value > 0 ? 'text-emerald-600' : 'text-red-600',
                        ]"
                    >
                        {{ Math.abs(trend.value) }}% {{ trend.label }}
                    </span>
                </div>
            </div>

            <!-- Icon -->
            <div
                v-if="icon"
                :class="['rounded-lg p-3', colorClasses]"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <!-- You can add icon paths here or use a library like Heroicons -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>
    </div>
</template>
