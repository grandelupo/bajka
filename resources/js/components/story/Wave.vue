<script setup lang="ts">
/* A soft scalloped edge between two sections; it takes its colour from `color`. */
withDefaults(defineProps<{ color?: string; flip?: boolean; animated?: boolean }>(), { color: 'var(--cream)', flip: false, animated: true });
</script>

<template>
    <div class="wave" :class="{ 'wave--flip': flip, 'wave--animated': animated }" :style="{ color }" aria-hidden="true">
        <svg viewBox="0 0 2880 80" preserveAspectRatio="none">
            <path
                fill="currentColor"
                d="M0 40 C 120 10 240 10 360 40 S 600 70 720 40 S 960 10 1080 40 S 1320 70 1440 40 S 1680 10 1800 40 S 2040 70 2160 40 S 2400 10 2520 40 S 2760 70 2880 40 V80 H0 Z"
            />
        </svg>
    </div>
</template>

<style scoped>
.wave {
    position: relative;
    height: clamp(28px, 4vw, 56px);
    overflow: hidden;
    line-height: 0;
    margin-top: calc(clamp(28px, 4vw, 56px) * -1);
    z-index: 2;
    pointer-events: none;
}

.wave--flip {
    transform: rotate(180deg);
    margin-top: 0;
    margin-bottom: calc(clamp(28px, 4vw, 56px) * -1);
}

.wave svg {
    width: 200%;
    height: 100%;
}

.wave--animated svg {
    animation: wave-drift 18s linear infinite;
}

@keyframes wave-drift {
    to {
        transform: translateX(-50%);
    }
}
</style>
