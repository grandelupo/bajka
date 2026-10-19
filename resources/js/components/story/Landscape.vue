<script setup lang="ts">
/*
 * Rolling hills with the old fort from the story on the far one, its
 * windows lit and flickering. Three layers so the hero can drift them at
 * different speeds.
 */
defineProps<{ shift?: number }>();
</script>

<template>
    <div class="land" aria-hidden="true">
        <svg class="land__layer land__layer--far" viewBox="0 0 1440 420" preserveAspectRatio="xMidYMax slice" :style="{ '--k': 0.25 }">
            <path d="M0 300 C 180 250 300 270 460 240 C 640 205 760 250 900 230 C 1080 205 1240 250 1440 225 V420 H0 Z" fill="#1d4440" />
            <!-- the fort -->
            <g transform="translate(920 118)" fill="#173a36">
                <path
                    d="M0 120 V36 h12 v-10 h10 v10 h10 v-10 h10 v10 h10 v-10 h10 v10 h8 V120 Z M70 120 V70 h150 V120 Z M210 120 V20 h12 v-10 h10 v10 h10 v-10 h10 v10 h10 v-10 h10 v10 h10 V120 Z"
                />
                <path d="M120 120 v-28 a20 20 0 0 1 40 0 v28 Z" fill="#0f2826" />
                <path d="M250 20 v-22" stroke="#173a36" stroke-width="2.5" />
                <path class="land__flag" d="M250 -2 l22 6 l-22 6 Z" fill="#e8643a" />
                <rect class="land__window" x="26" y="58" width="9" height="14" rx="4.5" fill="#f2b84b" />
                <rect class="land__window land__window--b" x="236" y="46" width="9" height="14" rx="4.5" fill="#f2b84b" />
                <rect class="land__window land__window--c" x="262" y="78" width="9" height="14" rx="4.5" fill="#f2b84b" />
                <rect class="land__window land__window--d" x="94" y="86" width="8" height="12" rx="4" fill="#f2b84b" />
            </g>
        </svg>

        <svg class="land__layer land__layer--mid" viewBox="0 0 1440 420" preserveAspectRatio="xMidYMax slice" :style="{ '--k': 0.5 }">
            <path d="M0 330 C 220 290 360 320 560 300 C 760 280 900 330 1100 310 C 1260 295 1360 300 1440 305 V420 H0 Z" fill="#163733" />
            <g fill="#12302d">
                <path d="M120 318 l18 -64 l18 64 Z M150 322 l14 -46 l14 46 Z" />
                <path d="M1240 306 l20 -72 l20 72 Z M1272 310 l15 -50 l15 50 Z M1210 312 l12 -40 l12 40 Z" />
                <path d="M640 300 l16 -56 l16 56 Z" />
            </g>
        </svg>

        <svg class="land__layer land__layer--near" viewBox="0 0 1440 420" preserveAspectRatio="xMidYMax slice" :style="{ '--k': 1 }">
            <path d="M0 370 C 260 340 420 380 700 360 C 980 340 1160 385 1440 360 V420 H0 Z" fill="var(--land-near, #0f2624)" />
            <!-- grass tufts and tiny flowers -->
            <g stroke="#0f2624" stroke-width="3" stroke-linecap="round">
                <path d="M180 362 l-6 -14 M186 362 l0 -16 M192 362 l6 -13" />
                <path d="M980 352 l-6 -14 M986 352 l0 -16 M992 352 l6 -13" />
            </g>
            <g fill="#fbe3a4">
                <circle cx="240" cy="356" r="3" />
                <circle cx="520" cy="362" r="2.5" />
                <circle cx="1110" cy="358" r="3" />
                <circle cx="1330" cy="364" r="2.5" />
            </g>
        </svg>
    </div>
</template>

<style scoped>
.land {
    position: absolute;
    inset: auto 0 0;
    height: min(42vh, 26rem);
    pointer-events: none;
}

.land__layer {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: translate3d(calc(var(--mx, 0) * var(--k) * -18px), calc(var(--scroll-y, 0) * var(--k) * 0.12px), 0);
    transition: transform 0.6s var(--ease-out);
}

.land__window {
    animation: flicker 3.6s steps(1) infinite;
}

.land__window--b {
    animation-delay: -1.2s;
    animation-duration: 4.4s;
}

.land__window--c {
    animation-delay: -2.1s;
}

.land__window--d {
    animation-delay: -0.6s;
    animation-duration: 5.2s;
}

@keyframes flicker {
    0%,
    100% {
        opacity: 1;
    }
    41% {
        opacity: 0.55;
    }
    44% {
        opacity: 1;
    }
    72% {
        opacity: 0.8;
    }
}

.land__flag {
    transform-box: fill-box;
    transform-origin: left center;
    animation: flag 1.6s var(--ease-in-out) infinite alternate;
}

@keyframes flag {
    from {
        transform: scaleX(1) skewY(0);
    }
    to {
        transform: scaleX(0.82) skewY(-8deg);
    }
}
</style>
