<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { BookOpen, ChevronDown, History, LayoutDashboard, LogOut, Settings } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CoinPurse from './CoinPurse.vue';
import Logo from './Logo.vue';
import SoundToggle from './SoundToggle.vue';

/*
 * The header floats transparent over a night hero (tone="night") and turns
 * into a cream bar once the page scrolls. Over plain pages it is cream from
 * the start.
 */
const props = withDefaults(defineProps<{ tone?: 'night' | 'paper' }>(), { tone: 'paper' });

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const bookId = computed(() => page.props.config.books.advertised_book_id);

const scrolled = ref(false);
const menuOpen = ref(false);
const menu = ref<HTMLElement | null>(null);
onClickOutside(menu, () => (menuOpen.value = false));

const onScroll = () => (scrolled.value = window.scrollY > 24);
onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(/\s+/)
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

const onNight = computed(() => props.tone === 'night' && !scrolled.value);
</script>

<template>
    <header class="site-header" :class="{ 'is-night': onNight, 'is-scrolled': scrolled }">
        <div class="wrap site-header__inner">
            <Link :href="route('home')" class="site-header__logo" aria-label="Bajka — strona główna">
                <Logo />
            </Link>

            <nav class="site-header__nav" aria-label="Główna nawigacja">
                <Link :href="route('pages.show', { book: bookId, page: 1 })" class="site-header__link" data-sfx="bookOpen">
                    <BookOpen /> <span>Czytaj</span>
                </Link>
                <Link v-if="user" :href="route('dashboard')" class="site-header__link site-header__link--wide">
                    <LayoutDashboard /> <span>Moja półka</span>
                </Link>
            </nav>

            <div class="site-header__actions">
                <SoundToggle />

                <template v-if="user">
                    <CoinPurse :coins="user.coins ?? 0" />

                    <div ref="menu" class="usermenu">
                        <button type="button" class="usermenu__button" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
                            <img v-if="user.avatar" :src="user.avatar" alt="" class="usermenu__avatar" />
                            <span v-else class="usermenu__avatar">{{ initials }}</span>
                            <ChevronDown class="usermenu__chev" />
                            <span class="sr-only">Menu konta</span>
                        </button>

                        <Transition name="menu">
                            <div v-if="menuOpen" class="usermenu__panel" @click="menuOpen = false">
                                <p class="usermenu__hello">
                                    Cześć, <strong>{{ user.name.split(' ')[0] }}</strong>!
                                </p>
                                <Link :href="route('dashboard')"><LayoutDashboard /> Moja półka</Link>
                                <Link :href="route('coins.history')"><History /> Historia monet</Link>
                                <Link :href="route('profile.edit')"><Settings /> Ustawienia</Link>
                                <Link :href="route('logout')" method="post" as="button"><LogOut /> Wyloguj się</Link>
                            </div>
                        </Transition>
                    </div>
                </template>

                <template v-else>
                    <Link :href="route('login')" class="site-header__link site-header__link--login">Zaloguj się</Link>
                    <Link :href="route('register')" class="btn btn--gold btn--sm">Załóż konto</Link>
                </template>
            </div>
        </div>
    </header>
</template>

<style scoped>
.site-header {
    position: sticky;
    top: 0;
    z-index: 50;
    height: var(--header-h);
    color: var(--night);
    --logo-gutter: var(--cream);
    transition:
        background 0.35s,
        box-shadow 0.35s,
        color 0.35s;
}

.site-header.is-scrolled {
    background: color-mix(in srgb, var(--cream) 88%, transparent);
    backdrop-filter: blur(14px) saturate(1.4);
    box-shadow: 0 1px 0 var(--line);
}

.site-header.is-night {
    color: var(--cream);
    --logo-gutter: var(--night);
}

.site-header__inner {
    display: flex;
    align-items: center;
    gap: 1rem;
    height: 100%;
}

.site-header__logo {
    flex: none;
}

.site-header__nav {
    display: flex;
    gap: 0.25rem;
    margin-left: clamp(0.5rem, 3vw, 2.5rem);
}

.site-header__link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem 0.85rem;
    border-radius: 999px;
    font-weight: 800;
    font-size: 0.95rem;
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce);
}

.site-header__link:hover {
    background: color-mix(in srgb, currentColor 10%, transparent);
    transform: translateY(-1px);
}

.site-header__link svg {
    width: 1.1rem;
    height: 1.1rem;
}

.site-header__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.usermenu {
    position: relative;
}

.usermenu__button {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.2rem;
    border-radius: 999px;
    transition: background 0.2s;
}

.usermenu__button:hover {
    background: color-mix(in srgb, currentColor 10%, transparent);
}

.usermenu__avatar {
    display: grid;
    place-items: center;
    width: 2.3rem;
    height: 2.3rem;
    border-radius: 50%;
    object-fit: cover;
    background: var(--rose);
    color: #fff;
    font-weight: 900;
    font-size: 0.85rem;
}

.usermenu__chev {
    width: 1rem;
    height: 1rem;
    opacity: 0.7;
}

.usermenu__panel {
    position: absolute;
    right: 0;
    top: calc(100% + 0.6rem);
    width: 15rem;
    padding: 0.5rem;
    border-radius: 1.25rem;
    background: var(--paper);
    color: var(--ink);
    box-shadow: var(--shadow-lift);
    border: 1px solid var(--line);
    transform-origin: top right;
}

.usermenu__hello {
    padding: 0.6rem 0.75rem 0.7rem;
    font-size: 0.9rem;
    color: var(--ink-soft);
    border-bottom: 1px dashed var(--line-strong);
    margin-bottom: 0.35rem;
}

.usermenu__panel :is(a, button) {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 0.75rem;
    border-radius: 0.8rem;
    font-weight: 700;
    font-size: 0.95rem;
    text-align: left;
    transition:
        background 0.15s,
        padding 0.25s var(--ease-bounce);
}

.usermenu__panel :is(a, button):hover {
    background: var(--sand);
    padding-left: 1rem;
}

.usermenu__panel svg {
    width: 1.05rem;
    height: 1.05rem;
    color: var(--moss);
}

.menu-enter-active,
.menu-leave-active {
    transition:
        opacity 0.2s,
        transform 0.35s var(--ease-bounce);
}

.menu-enter-from,
.menu-leave-to {
    opacity: 0;
    transform: scale(0.85) translateY(-6px);
}

@media (max-width: 640px) {
    .site-header__nav span,
    .site-header__link--login,
    .site-header__link--wide {
        display: none;
    }
    .site-header__nav {
        margin-left: 0;
    }
}

@media (max-width: 380px) {
    .site-header__logo :deep(.logo__word) {
        display: none;
    }
}
</style>
