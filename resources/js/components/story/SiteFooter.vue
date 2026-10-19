<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from './Logo.vue';
import NightSky from './NightSky.vue';
import Wave from './Wave.vue';

const page = usePage<SharedData>();
const bookId = computed(() => page.props.config.books.advertised_book_id);
const year = new Date().getFullYear();
</script>

<template>
    <footer class="footer night">
        <Wave color="var(--night-deep)" class="footer__wave" />
        <NightSky :stars="40" :moon="false" :seed="3" />
        <div class="wrap footer__inner">
            <div class="footer__brand">
                <Logo />
                <p>Bajka do czytania i słuchania — o przyjaźni, odwadze i smoku, który zgubił swój ogień.</p>
            </div>
            <nav class="footer__nav">
                <Link :href="route('pages.show', { book: bookId, page: 1 })">Czytaj od początku</Link>
                <Link :href="route('dashboard')">Moja półka</Link>
                <Link :href="route('coins.purchase')">Monety</Link>
            </nav>
        </div>
        <div class="wrap footer__base">
            <span>© {{ year }} Bajka</span>
            <span>Zrobione z <span class="footer__heart">♥</span> dla małych i dużych czytelników</span>
        </div>
    </footer>
</template>

<style scoped>
.footer {
    background: var(--night-deep);
    position: relative;
    padding-top: 4rem;
    --logo-gutter: var(--night);
}

.footer__wave {
    position: absolute;
    left: 0;
    right: 0;
    bottom: calc(100% - 1px);
    margin: 0;
}

.footer__inner {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 2rem;
    padding-bottom: 2.5rem;
}

.footer__brand {
    max-width: 24rem;
}

.footer__brand p {
    margin-top: 1rem;
    color: rgb(251 244 230 / 0.7);
    line-height: 1.6;
}

.footer__nav {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    font-weight: 800;
}

.footer__nav a {
    transition:
        color 0.2s,
        transform 0.3s var(--ease-bounce);
}

.footer__nav a:hover {
    color: var(--gold);
    transform: translateX(4px);
}

.footer__base {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem;
    padding-block: 1.5rem 2rem;
    border-top: 1px solid var(--line-light);
    font-size: 0.85rem;
    color: rgb(251 244 230 / 0.6);
}

.footer__heart {
    display: inline-block;
    color: var(--rose);
    animation: beat 1.4s var(--ease-in-out) infinite;
}

@keyframes beat {
    0%,
    100% {
        transform: scale(1);
    }
    15% {
        transform: scale(1.3);
    }
    30% {
        transform: scale(1);
    }
}
</style>
