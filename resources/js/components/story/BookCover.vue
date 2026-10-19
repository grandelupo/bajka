<script setup lang="ts">
/* A hardback standing on the shelf: it leans and opens a crack on hover. */
withDefaults(defineProps<{ title: string; image?: string | null; color?: string }>(), { color: '#7a3b26' });
</script>

<template>
    <span class="cover" :style="{ '--c': color }">
        <span class="cover__pages" />
        <span class="cover__front">
            <img v-if="image" :src="image" alt="" loading="lazy" />
            <span class="cover__title">{{ title }}</span>
        </span>
        <span class="cover__spine" />
    </span>
</template>

<style scoped>
.cover {
    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 3 / 4;
    perspective: 1200px;
}

.cover__pages {
    position: absolute;
    inset: 3% 2% 2% 6%;
    border-radius: 2px 6px 6px 2px;
    background: repeating-linear-gradient(90deg, #f6ecd5 0 2px, #e2d2ad 2px 3px);
    box-shadow: 0 18px 30px -16px rgb(0 0 0 / 0.5);
}

.cover__front {
    position: absolute;
    inset: 0 4% 0 0;
    border-radius: 3px 8px 8px 3px;
    overflow: hidden;
    background: var(--c);
    transform-origin: left center;
    transition: transform 0.7s var(--ease-out);
    box-shadow:
        inset 6px 0 10px -4px rgb(0 0 0 / 0.45),
        inset -1px 0 0 rgb(255 255 255 / 0.2),
        0 14px 26px -14px rgb(0 0 0 / 0.6);
}

.cover__front img {
    position: absolute;
    inset: 8% 9% 30%;
    width: 82%;
    height: 62%;
    object-fit: cover;
    border-radius: 6px;
    box-shadow: 0 0 0 3px rgb(242 184 75 / 0.85);
}

.cover__title {
    position: absolute;
    left: 10%;
    right: 8%;
    bottom: 7%;
    font-family: var(--font-display);
    font-style: italic;
    font-weight: 600;
    font-size: clamp(0.85rem, 1.6vw, 1.15rem);
    line-height: 1.15;
    color: var(--gold-soft);
    text-align: center;
    text-wrap: balance;
}

.cover__spine {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 7%;
    border-radius: 3px 0 0 3px;
    background: linear-gradient(90deg, rgb(0 0 0 / 0.35), rgb(255 255 255 / 0.12) 60%, rgb(0 0 0 / 0.15));
    pointer-events: none;
}

.cover:hover .cover__front {
    transform: rotateY(-24deg);
}
</style>
