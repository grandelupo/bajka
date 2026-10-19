<script setup lang="ts">
/* Open book with a moon and a star rising out of it, beside the wordmark. */
withDefaults(defineProps<{ mark?: boolean; word?: boolean }>(), { mark: true, word: true });
</script>

<template>
    <span class="logo">
        <svg v-if="mark" class="logo__mark" viewBox="0 0 48 48" aria-hidden="true">
            <path class="logo__moon" d="M27 6.5 a9 9 0 1 0 8.5 12 a7 7 0 1 1 -8.5 -12 Z" fill="var(--gold)" />
            <path class="logo__star" d="M15 8 l1.3 3 3 1.2 -3 1.3 -1.3 3 -1.2 -3 -3.1 -1.3 3.1 -1.2 Z" fill="var(--gold-soft)" />
            <path d="M4 22 q10 -4 20 2 q10 -6 20 -2 v18 q-10 -4 -20 2 q-10 -6 -20 -2 Z" fill="currentColor" />
            <path d="M24 24 v18" stroke="var(--logo-gutter, var(--cream))" stroke-width="1.6" />
            <path d="M8 27 q7 -2 13 1 M8 32 q7 -2 13 1 M27 28 q6 -3 13 -1 M27 33 q6 -3 13 -1" stroke="var(--logo-gutter, var(--cream))" stroke-width="1.4" fill="none" stroke-linecap="round" opacity="0.6" />
        </svg>
        <span v-if="word" class="logo__word">Bajka</span>
    </span>
</template>

<style scoped>
.logo {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    line-height: 1;
}

.logo__mark {
    width: 2.4rem;
    height: 2.4rem;
    overflow: visible;
}

.logo__word {
    font-family: var(--font-display);
    font-style: italic;
    font-weight: 600;
    font-size: 1.75rem;
    letter-spacing: -0.02em;
    font-variation-settings:
        'SOFT' 100,
        'WONK' 1;
}

.logo__moon,
.logo__star {
    transform-box: fill-box;
    transform-origin: center;
    transition: transform 0.6s var(--ease-bounce);
}

.logo:hover .logo__moon {
    transform: translateY(-3px) rotate(-14deg);
}

.logo:hover .logo__star {
    transform: scale(1.5) rotate(45deg);
}
</style>
