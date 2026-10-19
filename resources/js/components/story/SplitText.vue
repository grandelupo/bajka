<script setup lang="ts">
import { computed } from 'vue';

/* Splits a heading into word spans so v-reveal:words can raise them one by one. */
const props = defineProps<{ text: string; offset?: number }>();

const words = computed(() => props.text.split(/(\s+)/).filter((w) => w.trim().length));
</script>

<template>
    <span class="sr-only">{{ text }}</span>
    <span aria-hidden="true">
        <template v-for="(word, i) in words" :key="i">
            <span class="split__clip"><span class="split__word" :style="{ '--i': i + (offset ?? 0) }">{{ word }}</span></span>{{ ' ' }}
        </template>
    </span>
</template>
