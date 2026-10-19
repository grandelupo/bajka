<script setup lang="ts">
import { play } from '@/lib/sound';
import { ref } from 'vue';

/*
 * The book's dragon, drawn chubby and friendly. It flaps, blinks and swishes
 * its tail on its own; poke it and it tries (with a little puff) to breathe
 * the fire it lost.
 */
withDefaults(defineProps<{ flying?: boolean; interactive?: boolean }>(), { flying: false, interactive: true });

const puffing = ref(false);

function poke() {
    if (puffing.value) return;
    puffing.value = true;
    void play('dragon');
    setTimeout(() => (puffing.value = false), 1400);
}
</script>

<template>
    <svg
        viewBox="0 0 240 190"
        class="dragon"
        :class="{ 'dragon--flying': flying, 'dragon--puff': puffing, 'cursor-pointer': interactive }"
        role="img"
        aria-label="Smok"
        @click="interactive && poke()"
    >
        <!-- far wing -->
        <g class="dragon__wing dragon__wing--far">
            <path d="M104 92 C 96 52, 70 34, 52 40 C 62 50, 62 58, 58 66 C 70 64, 76 72, 74 82 C 84 78, 94 84, 104 92 Z" fill="#2a6a52" />
        </g>

        <!-- tail -->
        <g class="dragon__tail">
            <path
                d="M70 128 C 44 140, 22 132, 18 112 C 15 98, 28 90, 38 98"
                fill="none"
                stroke="#3f8f6b"
                stroke-width="15"
                stroke-linecap="round"
            />
            <path d="M30 104 L 40 86 L 46 104 Z" fill="#e8643a" />
        </g>

        <!-- legs -->
        <ellipse cx="88" cy="160" rx="15" ry="11" fill="#357a5c" />
        <ellipse cx="132" cy="162" rx="15" ry="11" fill="#357a5c" />
        <path d="M78 166 h6 M88 167 h6 M122 168 h6 M132 169 h6" stroke="#f6d79a" stroke-width="3" stroke-linecap="round" />

        <!-- body -->
        <ellipse cx="108" cy="124" rx="52" ry="42" fill="#3f8f6b" />
        <!-- back spikes -->
        <path d="M66 104 l6 -14 l8 12 Z M80 92 l8 -14 l8 13 Z M96 85 l9 -14 l8 14 Z" fill="#e8643a" />
        <!-- belly -->
        <ellipse cx="118" cy="132" rx="30" ry="29" fill="#f6d79a" />
        <path d="M96 120 q22 6 44 0 M93 132 q25 7 50 0 M96 144 q22 6 44 0" fill="none" stroke="#e4b96a" stroke-width="2.5" stroke-linecap="round" />

        <!-- near wing -->
        <g class="dragon__wing">
            <path d="M98 100 C 84 58, 52 44, 30 54 C 44 60, 46 70, 42 78 C 56 74, 64 84, 62 94 C 76 88, 88 94, 98 100 Z" fill="#4fa47c" />
            <path d="M96 98 C 80 72, 62 62, 44 62 M92 96 C 80 84, 70 80, 60 82" fill="none" stroke="#2f6f55" stroke-width="2.5" stroke-linecap="round" />
        </g>

        <!-- arm -->
        <path d="M134 120 q14 10 10 22" fill="none" stroke="#357a5c" stroke-width="11" stroke-linecap="round" />

        <!-- head -->
        <g class="dragon__head">
            <path d="M150 50 L 144 22 L 162 44 Z" fill="#f6d79a" />
            <path d="M172 48 L 180 22 L 188 50 Z" fill="#f6d79a" />
            <circle cx="166" cy="76" r="36" fill="#3f8f6b" />
            <ellipse cx="196" cy="90" rx="25" ry="19" fill="#4fa47c" />
            <ellipse cx="206" cy="84" rx="3.2" ry="2.4" fill="#2a6a52" />
            <ellipse cx="194" cy="83" rx="3.2" ry="2.4" fill="#2a6a52" />
            <path d="M182 100 q12 9 26 -1" fill="none" stroke="#1d4a3a" stroke-width="3" stroke-linecap="round" />
            <ellipse cx="146" cy="92" rx="9" ry="6" fill="#ec8fae" opacity="0.55" />

            <g class="dragon__eye">
                <ellipse cx="164" cy="68" rx="12" ry="14" fill="#fff" />
                <circle cx="167" cy="70" r="7.5" fill="#1d2a2c" />
                <circle cx="170" cy="66" r="2.8" fill="#fff" />
            </g>
        </g>

        <!-- the puff of fire it is trying so hard to make -->
        <g class="dragon__fire">
            <path d="M222 92 q12 -10 6 -24 q10 8 12 20 q2 -10 -4 -18 q14 6 10 26 q-6 14 -24 -4 Z" fill="#f2b84b" />
            <circle cx="232" cy="96" r="5" fill="#e8643a" />
            <circle cx="236" cy="80" r="3" fill="#bbb" opacity="0.7" />
            <circle cx="230" cy="72" r="2.2" fill="#bbb" opacity="0.5" />
        </g>
    </svg>
</template>

<style scoped>
.dragon {
    overflow: visible;
}

.dragon__wing {
    transform-box: view-box;
    transform-origin: 98px 100px;
    animation: dragon-flap 2.6s var(--ease-in-out) infinite;
}

.dragon__wing--far {
    transform-origin: 104px 92px;
    animation-delay: -0.15s;
}

.dragon--flying .dragon__wing {
    animation-duration: 0.7s;
}

.dragon__tail {
    transform-box: view-box;
    transform-origin: 72px 128px;
    animation: dragon-tail 3.4s var(--ease-in-out) infinite;
}

.dragon__head {
    transform-box: view-box;
    transform-origin: 150px 110px;
    animation: dragon-nod 5s var(--ease-in-out) infinite;
}

.dragon__eye {
    transform-box: fill-box;
    transform-origin: center;
    animation: dragon-blink 4.5s infinite;
}

.dragon__fire {
    transform-box: view-box;
    transform-origin: 220px 92px;
    transform: scale(0);
    opacity: 0;
}

.dragon--puff .dragon__fire {
    animation: dragon-puff 1.3s var(--ease-out);
}

.dragon--puff .dragon__head {
    animation: dragon-huff 1.3s var(--ease-in-out);
}

@keyframes dragon-flap {
    0%,
    100% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(-22deg) scaleY(0.92);
    }
}

@keyframes dragon-tail {
    0%,
    100% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(9deg);
    }
}

@keyframes dragon-nod {
    0%,
    100% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(-4deg);
    }
}

@keyframes dragon-blink {
    0%,
    92%,
    100% {
        transform: scaleY(1);
    }
    95% {
        transform: scaleY(0.1);
    }
}

@keyframes dragon-huff {
    0%,
    100% {
        transform: rotate(0);
    }
    30% {
        transform: rotate(-10deg) translateX(-4px);
    }
    55% {
        transform: rotate(6deg) translateX(4px);
    }
}

@keyframes dragon-puff {
    0%,
    45% {
        transform: scale(0);
        opacity: 0;
    }
    60% {
        transform: scale(1.15);
        opacity: 1;
    }
    100% {
        transform: scale(0.6) translateX(14px) translateY(-10px);
        opacity: 0;
    }
}
</style>
