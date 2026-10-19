<script setup lang="ts">
/* Fireflies that drift and pulse, like the ones round the fort's gate. */
const props = withDefaults(defineProps<{ count?: number }>(), { count: 18 });

const flies = Array.from({ length: props.count }, (_, i) => {
    const r = (n: number) => ((Math.sin((i + 1) * n) + 1) / 2) % 1;
    return {
        left: r(12.9898) * 100,
        top: 35 + r(78.233) * 60,
        size: 3 + r(3.17) * 4,
        dx: (r(9.1) - 0.5) * 120,
        dy: (r(4.7) - 0.5) * 90,
        duration: 7 + r(2.3) * 8,
        delay: -r(5.9) * 10,
    };
});
</script>

<template>
    <div class="flies" aria-hidden="true">
        <span
            v-for="(f, i) in flies"
            :key="i"
            :style="{
                left: `${f.left}%`,
                top: `${f.top}%`,
                '--size': `${f.size}px`,
                '--dx': `${f.dx}px`,
                '--dy': `${f.dy}px`,
                animationDuration: `${f.duration}s, ${f.duration / 3}s`,
                animationDelay: `${f.delay}s, ${f.delay}s`,
            }"
        />
    </div>
</template>

<style scoped>
.flies {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.flies span {
    position: absolute;
    width: var(--size);
    height: var(--size);
    border-radius: 50%;
    background: #ffe9a8;
    box-shadow:
        0 0 8px 2px rgb(242 184 75 / 0.9),
        0 0 22px 6px rgb(242 184 75 / 0.35);
    animation:
        fly-drift ease-in-out infinite alternate,
        fly-glow ease-in-out infinite alternate;
}

@keyframes fly-drift {
    from {
        transform: translate3d(0, 0, 0);
    }
    to {
        transform: translate3d(var(--dx), var(--dy), 0);
    }
}

@keyframes fly-glow {
    from {
        opacity: 0.15;
    }
    to {
        opacity: 1;
    }
}
</style>
