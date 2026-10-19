<script setup lang="ts">
import { burst } from '@/lib/sparkle';
import { play } from '@/lib/sound';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, Sparkles } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

/* Shows the server's flash message (and celebrates a success). */
const page = usePage<SharedData>();
const message = ref<{ text: string; kind: 'success' | 'error' } | null>(null);
const el = ref<HTMLElement | null>(null);
let timer: ReturnType<typeof setTimeout>;

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    async ([success, error]) => {
        if (!success && !error) return;
        message.value = success ? { text: success, kind: 'success' } : { text: error!, kind: 'error' };
        clearTimeout(timer);
        timer = setTimeout(() => (message.value = null), 5500);
        if (success) {
            await nextTick();
            void play('sparkle');
            const r = el.value?.getBoundingClientRect();
            if (r) burst(r.left + r.width / 2, r.top, { count: 18, spread: 120 });
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition name="toast">
        <div v-if="message" ref="el" class="toast" :class="{ 'toast--error': message.kind === 'error' }" role="status" @click="message = null">
            <Sparkles v-if="message.kind === 'success'" class="h-5 w-5 flex-none text-[var(--gold)]" />
            <CircleAlert v-else class="h-5 w-5 flex-none" />
            {{ message.text }}
        </div>
    </Transition>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition:
        opacity 0.3s,
        transform 0.5s var(--ease-bounce);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(24px) scale(0.9);
}
</style>
