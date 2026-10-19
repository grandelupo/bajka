<script setup lang="ts">
import FlashToast from '@/components/story/FlashToast.vue';
import SiteFooter from '@/components/story/SiteFooter.vue';
import SiteHeader from '@/components/story/SiteHeader.vue';
import type { BreadcrumbItemType } from '@/types';

/*
 * The frame round every ordinary page. `tone="night"` lets the header float
 * over a dark hero; `footer=false` drops the footer for full-screen pages.
 */
withDefaults(defineProps<{ tone?: 'night' | 'paper'; footer?: boolean; breadcrumbs?: BreadcrumbItemType[] }>(), {
    tone: 'paper',
    footer: true,
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="app-frame">
        <SiteHeader :tone="tone" :class="{ 'site-header--overlay': tone === 'night' }" />
        <main class="app-frame__main">
            <slot />
        </main>
        <SiteFooter v-if="footer" />
        <FlashToast />
    </div>
</template>

<style scoped>
.app-frame {
    display: flex;
    flex-direction: column;
    min-height: 100svh;
}

.app-frame__main {
    flex: 1;
}

/* Over a night hero the header sits on top of it instead of pushing it down. */
.site-header--overlay {
    margin-bottom: calc(var(--header-h) * -1);
}
</style>
