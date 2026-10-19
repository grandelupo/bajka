<script setup lang="ts">
import { play, setSoundEnabled, soundEnabled } from '@/lib/sound';
import { Volume2, VolumeX } from 'lucide-vue-next';

function toggle() {
    setSoundEnabled(!soundEnabled.value);
    if (soundEnabled.value) void play('sparkle', { volume: 0.25 });
}
</script>

<template>
    <button
        type="button"
        class="sound-toggle"
        :class="{ 'is-off': !soundEnabled }"
        :aria-pressed="soundEnabled"
        :title="soundEnabled ? 'Wycisz dźwięki' : 'Włącz dźwięki'"
        data-sfx="none"
        @click="toggle"
    >
        <Volume2 v-if="soundEnabled" />
        <VolumeX v-else />
        <span class="sr-only">{{ soundEnabled ? 'Wycisz dźwięki' : 'Włącz dźwięki' }}</span>
    </button>
</template>

<style scoped>
.sound-toggle {
    display: grid;
    place-items: center;
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 50%;
    color: inherit;
    background: color-mix(in srgb, currentColor 8%, transparent);
    transition:
        transform 0.3s var(--ease-bounce),
        background 0.2s;
}

.sound-toggle:hover {
    transform: scale(1.08) rotate(-6deg);
    background: color-mix(in srgb, currentColor 14%, transparent);
}

.sound-toggle svg {
    width: 1.2rem;
    height: 1.2rem;
}

.sound-toggle.is-off {
    opacity: 0.6;
}
</style>
