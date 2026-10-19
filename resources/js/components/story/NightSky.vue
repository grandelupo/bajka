<script setup lang="ts">
/*
 * A sky full of stars that twinkle out of step, an occasional shooting star
 * and (optionally) a glowing moon. Star positions come from a seeded random
 * so the sky is the same on every visit.
 */
const props = withDefaults(defineProps<{ stars?: number; moon?: boolean; seed?: number }>(), { stars: 70, moon: true, seed: 7 });

function seeded(seed: number) {
    let s = seed;
    return () => {
        s = (s * 16807) % 2147483647;
        return (s - 1) / 2147483646;
    };
}

const rand = seeded(props.seed * 7919 + 1);
const starField = Array.from({ length: props.stars }, (_, i) => ({
    x: rand() * 100,
    y: rand() * 75,
    r: 0.6 + rand() * 1.4,
    sparkle: i % 9 === 0,
    delay: -rand() * 4,
    duration: 2.2 + rand() * 3,
    gold: rand() < 0.25,
}));
</script>

<template>
    <div class="sky" aria-hidden="true">
        <span
            v-for="(s, i) in starField"
            :key="i"
            class="sky__star twinkle"
            :class="{ 'sky__star--sparkle': s.sparkle, 'sky__star--gold': s.gold }"
            :style="{
                left: `${s.x}%`,
                top: `${s.y}%`,
                '--size': `${s.sparkle ? 12 + s.r * 4 : s.r * 2.2}px`,
                animationDelay: `${s.delay}s`,
                animationDuration: `${s.duration}s`,
            }"
        />

        <span class="sky__shooting" />
        <span class="sky__shooting sky__shooting--late" />

        <div v-if="moon" class="sky__moon">
            <svg viewBox="0 0 120 120">
                <defs>
                    <radialGradient id="moon-face" cx="40%" cy="38%" r="65%">
                        <stop offset="0" stop-color="#fff8e1" />
                        <stop offset="1" stop-color="#f6dc9a" />
                    </radialGradient>
                </defs>
                <circle cx="60" cy="60" r="44" fill="url(#moon-face)" />
                <circle cx="44" cy="50" r="7" fill="#efd28b" opacity="0.6" />
                <circle cx="72" cy="74" r="10" fill="#efd28b" opacity="0.5" />
                <circle cx="74" cy="40" r="4.5" fill="#efd28b" opacity="0.55" />
                <!-- a sleepy face, for the little ones -->
                <path d="M40 66 q6 5 12 0 M66 62 q6 5 12 0" stroke="#c99a3c" stroke-width="2.6" fill="none" stroke-linecap="round" />
                <path d="M54 80 q6 4 12 0" stroke="#c99a3c" stroke-width="2.6" fill="none" stroke-linecap="round" />
            </svg>
        </div>
    </div>
</template>

<style scoped>
.sky {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.sky__star {
    position: absolute;
    width: var(--size);
    height: var(--size);
    margin: calc(var(--size) / -2);
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 0 6px rgb(255 255 255 / 0.6);
}

.sky__star--gold {
    background: var(--gold-soft);
}

.sky__star--sparkle {
    border-radius: 0;
    background: var(--gold-soft);
    box-shadow: none;
    clip-path: polygon(50% 0, 58% 42%, 100% 50%, 58% 58%, 50% 100%, 42% 58%, 0 50%, 42% 42%);
}

.sky__moon {
    position: absolute;
    top: 9%;
    right: 9%;
    width: clamp(6rem, 12vw, 10rem);
    aspect-ratio: 1;
    border-radius: 50%;
    filter: drop-shadow(0 0 40px rgb(251 227 164 / 0.55));
    animation: moon-glow 6s var(--ease-in-out) infinite alternate;
}

.sky__moon svg {
    width: 100%;
    height: 100%;
}

@keyframes moon-glow {
    from {
        filter: drop-shadow(0 0 28px rgb(251 227 164 / 0.4));
        transform: translateY(0) rotate(-3deg);
    }
    to {
        filter: drop-shadow(0 0 60px rgb(251 227 164 / 0.7));
        transform: translateY(-6px) rotate(3deg);
    }
}

.sky__shooting {
    position: absolute;
    top: 12%;
    left: 20%;
    width: 140px;
    height: 2px;
    border-radius: 2px;
    background: linear-gradient(90deg, rgb(255 255 255 / 0.9), transparent);
    transform: rotate(-20deg);
    opacity: 0;
    animation: shooting 11s ease-in infinite 3s;
}

.sky__shooting--late {
    top: 26%;
    left: 55%;
    animation-delay: 8.5s;
    animation-duration: 14s;
}

@keyframes shooting {
    0% {
        opacity: 0;
        transform: rotate(-20deg) translateX(0);
    }
    2% {
        opacity: 1;
    }
    7% {
        opacity: 0;
        transform: rotate(-20deg) translateX(-320px);
    }
    100% {
        opacity: 0;
        transform: rotate(-20deg) translateX(-320px);
    }
}
</style>
