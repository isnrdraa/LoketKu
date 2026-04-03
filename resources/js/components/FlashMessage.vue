<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, XCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const page = usePage<{ flash: { success?: string; error?: string } }>();

const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error'>('success');
let timer: ReturnType<typeof setTimeout> | null = null;

function show(msg: string, msgType: 'success' | 'error') {
    if (timer) {
        clearTimeout(timer);
    }

    message.value = msg;
    type.value = msgType;
    visible.value = true;
    timer = setTimeout(() => {
        visible.value = false;
    }, 3500);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            show(flash.success, 'success');
        } else if (flash?.error) {
            show(flash.error, 'error');
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="visible"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-lg px-4 py-3 shadow-lg text-sm font-medium"
            :class="type === 'success' ? 'bg-green-600 text-white' : 'bg-destructive text-destructive-foreground'"
        >
            <CheckCircle v-if="type === 'success'" class="h-4 w-4 shrink-0" />
            <XCircle v-else class="h-4 w-4 shrink-0" />
            {{ message }}
        </div>
    </Transition>
</template>
