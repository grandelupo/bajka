<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, UserRound } from 'lucide-vue-next';

const items = [
    { title: 'Profil', href: '/settings/profile', icon: UserRound },
    { title: 'Hasło', href: '/settings/password', icon: KeyRound },
];

const page = usePage<SharedData>();
const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="wrap settings">
        <header class="settings__head">
            <p class="eyebrow" v-reveal>Konto</p>
            <h1 class="h1" v-reveal="0.05">Ustawienia</h1>
        </header>

        <div class="settings__grid">
            <nav class="settings__nav" aria-label="Ustawienia">
                <Link v-for="item in items" :key="item.href" :href="item.href" :class="{ 'is-active': currentPath === item.href }">
                    <component :is="item.icon" /> {{ item.title }}
                </Link>
            </nav>
            <section class="settings__panel card">
                <slot />
            </section>
        </div>
    </div>
</template>

<style scoped>
.settings {
    padding-block: 3rem var(--section);
    max-width: 62rem;
}

.settings__head {
    margin-bottom: 2rem;
}

.settings__grid {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}

@media (min-width: 800px) {
    .settings__grid {
        grid-template-columns: 13rem 1fr;
    }
}

.settings__nav {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}

@media (min-width: 800px) {
    .settings__nav {
        flex-direction: column;
        position: sticky;
        top: calc(var(--header-h) + 1rem);
    }
}

.settings__nav a {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    font-weight: 800;
    color: var(--ink-soft);
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce);
}

.settings__nav a:hover {
    background: var(--sand);
    transform: translateX(3px);
}

.settings__nav a.is-active {
    background: var(--night);
    color: var(--cream);
}

.settings__nav svg {
    width: 1.1rem;
    height: 1.1rem;
}

.settings__panel {
    padding: clamp(1.25rem, 4vw, 2.5rem);
    display: grid;
    gap: 3rem;
}
</style>
