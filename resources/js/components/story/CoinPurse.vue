<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import CoinIcon from './CoinIcon.vue';

/*
 * The coin balance in the header. When it changes the number rolls to its
 * new value and the purse gives a little jump.
 */
const props = defineProps<{ coins: number }>();

const shown = ref(props.coins);
const bump = ref(false);
let frame = 0;

watch(
    () => props.coins,
    (to, from) => {
        cancelAnimationFrame(frame);
        bump.value = false;
        requestAnimationFrame(() => (bump.value = true));
        const start = performance.now();
        const step = (now: number) => {
            const t = Math.min(1, (now - start) / 700);
            const eased = 1 - Math.pow(1 - t, 3);
            shown.value = Math.round(from + (to - from) * eased);
            if (t < 1) frame = requestAnimationFrame(step);
        };
        frame = requestAnimationFrame(step);
    },
);

onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
    <Link :href="route('coins.purchase')" class="purse" :class="{ 'is-bumping': bump }" data-purse title="Twoje monety — doładuj" @animationend="bump = false">
        <CoinIcon class="purse__coin" />
        <span class="purse__count">{{ shown }}</span>
        <span class="purse__plus" aria-hidden="true">+</span>
        <span class="sr-only">monet, doładuj</span>
    </Link>
</template>

<style scoped>
.purse {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 2.6rem;
    padding: 0 0.35rem 0 0.4rem;
    border-radius: 999px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    background: color-mix(in srgb, var(--gold) 22%, transparent);
    color: inherit;
    transition: transform 0.3s var(--ease-bounce);
}

.purse:hover {
    transform: scale(1.05);
}

.purse__coin {
    width: 1.8rem;
    height: 1.8rem;
    transition: transform 0.6s var(--ease-bounce);
}

.purse:hover .purse__coin {
    transform: rotateY(180deg);
}

.purse__count {
    min-width: 1.5ch;
}

.purse__plus {
    display: grid;
    place-items: center;
    width: 1.7rem;
    height: 1.7rem;
    border-radius: 50%;
    background: var(--gold);
    color: var(--night-deep);
    font-size: 1.1rem;
    line-height: 1;
}

.is-bumping {
    animation: purse-bump 0.7s var(--ease-bounce);
}

@keyframes purse-bump {
    0% {
        transform: scale(1);
    }
    30% {
        transform: scale(1.22) rotate(-6deg);
    }
    60% {
        transform: scale(0.95) rotate(3deg);
    }
    100% {
        transform: scale(1);
    }
}
</style>
