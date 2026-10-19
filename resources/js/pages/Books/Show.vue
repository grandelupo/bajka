<script setup lang="ts">
import BookPage from '@/components/reader/BookPage.vue';
import CoinPurse from '@/components/story/CoinPurse.vue';
import Fireflies from '@/components/story/Fireflies.vue';
import FlashToast from '@/components/story/FlashToast.vue';
import Logo from '@/components/story/Logo.vue';
import NightSky from '@/components/story/NightSky.vue';
import SoundToggle from '@/components/story/SoundToggle.vue';
import { postJson } from '@/lib/http';
import { ambienceScene, duckAmbience, play, preload, preloadAmbience, SCENES, setAmbience, type Scene } from '@/lib/sound';
import { burstFrom, coinsFly } from '@/lib/sparkle';
import type { ReaderPage, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AudioLines, ChevronLeft, ChevronRight, Gauge, List, Lock, Pause, Play, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

interface Book {
    id: number;
    title: string;
    description: string;
    total_pages: number;
    price_per_page: number;
}

const props = defineProps<{
    book: Book;
    pages: ReaderPage[];
    currentPageNumber: number;
    accessConfig: { freePages: number; registeredFreePages: number; isAuthenticated: boolean };
    userCoins: number;
}>();

const shared = usePage<SharedData>();
const user = computed(() => shared.props.auth.user);

/* ── Pages ───────────────────────────────────────────────────────────── */
// A local copy, so unlocked pages can be swapped in without a reload.
const pages = reactive<ReaderPage[]>(props.pages.map((p) => ({ ...p })));
const total = computed(() => pages.length);
const pageAt = (n: number) => (n >= 1 && n <= total.value ? pages[n - 1] : null);

const chapters = computed(() => pages.filter((p) => p.chapter).map((p) => ({ title: p.chapter!, page: p.page_number })));
const chapterNumberOf = (n: number) => {
    const index = chapters.value.findIndex((c) => c.page === n);
    return index >= 0 ? index + 1 : null;
};
const chapterFor = (n: number) => [...chapters.value].reverse().find((c) => c.page <= n) ?? null;

const coins = ref(user.value?.coins ?? props.userCoins);

/* ── Layout: two pages side by side, or one ─────────────────────────── */
const stage = ref<HTMLElement | null>(null);
const spread = ref(true);
const size = reactive({ w: 0, h: 0 });

function measure() {
    const el = stage.value;
    if (!el) return;
    const W = el.clientWidth;
    const H = el.clientHeight;
    const wide = W >= 820 && W / H > 1.05;
    spread.value = wide;

    if (wide) {
        // Each page between 0.66 and 0.8 as wide as tall, filling what it can.
        let h = H;
        let w = Math.min(W / 2, h * 0.8);
        if (w < h * 0.66) h = w / 0.66;
        size.w = Math.floor(w);
        size.h = Math.floor(h);
    } else {
        let h = H;
        let w = Math.min(W, h * 0.78);
        if (w < h * 0.5) h = w / 0.5;
        size.w = Math.floor(w);
        size.h = Math.floor(h);
    }
}

/* ── Where we are ────────────────────────────────────────────────────── */
const normalise = (n: number) => {
    const max = total.value + 1; // one past the last page is "the end"
    n = Math.max(1, Math.min(n, max));
    return spread.value && n % 2 === 0 ? n - 1 : n;
};

const current = ref(props.currentPageNumber);
const step = computed(() => (spread.value ? 2 : 1));
const lastStop = computed(() => normalise(total.value + 1));
const canPrev = computed(() => current.value > 1);
const canNext = computed(() => current.value < lastStop.value);

const visibleNumbers = computed(() => (spread.value ? [current.value, current.value + 1] : [current.value]));
const visiblePages = computed(() => visibleNumbers.value.map(pageAt).filter((p): p is ReaderPage => !!p));
const shownTo = computed(() => Math.min(total.value, visibleNumbers.value[visibleNumbers.value.length - 1]));

const progressLabel = computed(() => {
    if (current.value > total.value) return 'Koniec';
    const a = current.value;
    const b = shownTo.value;
    return a === b ? `${a} / ${total.value}` : `${a}–${b} / ${total.value}`;
});

const currentChapter = computed(() => chapterFor(current.value));

function pageUrl(n: number) {
    return route('pages.show', { book: props.book.id, page: Math.min(Math.max(1, n), total.value) });
}

const returnUrl = computed(() => new URL(pageUrl(current.value)).pathname);

watch(current, (n) => {
    const url = pageUrl(n);
    window.history.replaceState(window.history.state, '', url);
    try {
        localStorage.setItem(`bajka:progress:${props.book.id}`, String(Math.min(n, total.value)));
    } catch {
        /* storage may be off; progress just will not be remembered */
    }
});

/* ── Turning pages ───────────────────────────────────────────────────── */
interface Flip {
    dir: 1 | -1;
    from: number;
    to: number;
    /** Landed: the pages underneath already show the new spread, the leaf just lingers. */
    settled?: boolean;
}
const flip = ref<Flip | null>(null);
const FLIP_MS = 900;

// What lies under the turning leaf, and what is printed on its two faces.
const under = computed(() => {
    const f = flip.value;
    if (!f || f.settled) return spread.value ? { left: current.value, right: current.value + 1, single: current.value } : { single: current.value };
    if (spread.value) {
        return f.dir === 1 ? { left: f.from, right: f.to + 1 } : { left: f.to, right: f.from + 1 };
    }
    return { single: f.dir === 1 ? f.to : f.from };
});

const leaf = computed(() => {
    const f = flip.value;
    if (!f) return null;
    if (spread.value) return f.dir === 1 ? { front: f.from + 1, back: f.to } : { front: f.from, back: f.to + 1 };
    return { front: f.dir === 1 ? f.from : f.to, back: null };
});

function turnTo(target: number, { sound = true } = {}) {
    target = normalise(target);
    if (flip.value || target === current.value) return;
    stopNarrationAudio();
    flip.value = { dir: target > current.value ? 1 : -1, from: current.value, to: target };
    if (sound) void play('flip', { vary: 0.08 });
    flipTimer = setTimeout(finishFlip, FLIP_MS + 40);
}

let flipTimer: ReturnType<typeof setTimeout> | undefined;
function finishFlip() {
    clearTimeout(flipTimer);
    if (!flip.value) return;
    // Swap the pages underneath first and keep the finished leaf on top for a
    // few frames, until they have laid themselves out: no blink as it lifts.
    const landed = flip.value;
    current.value = landed.to;
    flip.value = { ...landed, settled: true };
    void nextTick(() =>
        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                if (flip.value === null || flip.value.to !== landed.to) return;
                flip.value = null;
            }),
        ),
    );
    void afterArrive();
}

const next = () => canNext.value && turnTo(current.value + step.value);
const prev = () => canPrev.value && turnTo(current.value - step.value);

/* ── Unlocking with coins ────────────────────────────────────────────── */
const AUTO_KEY = 'bajka:auto-unlock';
const autoUnlock = ref(false);
try {
    autoUnlock.value = localStorage.getItem(AUTO_KEY) === '1';
} catch {
    /* ignore */
}
watch(autoUnlock, (on) => {
    try {
        localStorage.setItem(AUTO_KEY, on ? '1' : '0');
    } catch {
        /* ignore */
    }
});

const lockedHere = computed(() => visiblePages.value.filter((p) => p.locked === 'coins'));
const lockCost = computed(() => lockedHere.value.length * props.book.price_per_page);
const unlocking = ref(false);
const bookEl = ref<HTMLElement | null>(null);

async function unlock() {
    if (unlocking.value || !lockedHere.value.length) return;
    if (coins.value < lockCost.value) {
        router.visit(route('coins.purchase', { return: returnUrl.value }));
        return;
    }

    unlocking.value = true;
    void play('coin');
    coinsFly(document.querySelector('[data-purse]'), bookEl.value, 6);

    const { ok, status, data } = await postJson<{ pages: ReaderPage[]; remaining_coins: number }>(route('transactions.purchase-page'), {
        book_id: props.book.id,
        page_numbers: lockedHere.value.map((p) => p.page_number),
    });

    if (!ok) {
        unlocking.value = false;
        if (status === 402) router.visit(route('coins.purchase', { return: returnUrl.value }));
        else if (status === 401 || status === 419) router.visit(route('login', { return: returnUrl.value }));
        return;
    }

    for (const fresh of data.pages) pages[fresh.page_number - 1] = fresh;
    coins.value = data.remaining_coins;
    if (shared.props.auth.user) shared.props.auth.user.coins = data.remaining_coins;

    await nextTick();
    unlocking.value = false;
    void play('unlock');
    setTimeout(() => void play('sparkle'), 180);
    burstFrom(bookEl.value, { count: 24, spread: 220 });
    if (narrating.value) void playView();
}

/* ── Narration ("Czytaj mi") ─────────────────────────────────────────── */
const narrating = ref(false);
const narratedPage = ref<number | null>(null);
const narrationProgress = ref(0);
const SPEEDS = [0.85, 1, 1.2];
const speed = ref(1);
let audio: HTMLAudioElement | null = null;
let queue: ReaderPage[] = [];

watch(narratedPage, (n) => duckAmbience(n !== null));

const hasNarration = computed(() => visiblePages.value.some((p) => p.audio_url));

function ensureAudio() {
    if (audio) return audio;
    audio = new Audio();
    audio.preload = 'auto';
    audio.addEventListener('timeupdate', () => {
        if (audio && audio.duration) narrationProgress.value = audio.currentTime / audio.duration;
    });
    audio.addEventListener('ended', onNarrationEnded);
    return audio;
}

function stopNarrationAudio() {
    if (audio) audio.pause();
    narratedPage.value = null;
    narrationProgress.value = 0;
}

async function playPage(p: ReaderPage) {
    const a = ensureAudio();
    narratedPage.value = p.page_number;
    narrationProgress.value = 0;
    a.src = p.audio_url!;
    a.playbackRate = speed.value;
    try {
        await a.play();
    } catch {
        narrating.value = false;
        narratedPage.value = null;
    }
}

async function playView() {
    queue = visiblePages.value.filter((p) => p.audio_url && !p.locked);
    const first = queue.shift();
    if (first) await playPage(first);
    else narratedPage.value = null;
}

function onNarrationEnded() {
    const nextPage = queue.shift();
    if (nextPage) {
        void playPage(nextPage);
        return;
    }
    narratedPage.value = null;
    narrationProgress.value = 0;
    // Turn the page on our own, and keep reading on the other side.
    if (narrating.value && canNext.value) setTimeout(() => narrating.value && next(), 600);
    else narrating.value = false;
}

function toggleNarration() {
    if (narrating.value) {
        narrating.value = false;
        audio?.pause();
        return;
    }
    narrating.value = true;
    if (audio && narratedPage.value && audio.src && !audio.ended && audio.currentTime > 0) {
        void audio.play();
        return;
    }
    void playView();
}

function cycleSpeed() {
    speed.value = SPEEDS[(SPEEDS.indexOf(speed.value) + 1) % SPEEDS.length];
    if (audio) audio.playbackRate = speed.value;
}

/* After landing on a new spread: unlock it if asked to, keep reading. */
async function afterArrive() {
    if (autoUnlock.value && lockedHere.value.length && coins.value >= lockCost.value) await unlock();
    if (narrating.value) {
        if (visiblePages.value.some((p) => p.audio_url && !p.locked)) void playView();
        else narrating.value = false;
    }
}

/* ── Soundscape ("Klimat") ───────────────────────────────────────────────
   Each page knows where it happens; the ambience follows it, crossfading as
   the story moves from Olga's room to the fort, the park or the rain. */
const AMBIENCE_KEY = 'bajka:ambience';
const ambienceOn = ref(false);
try {
    ambienceOn.value = localStorage.getItem(AMBIENCE_KEY) === '1';
} catch {
    /* ignore */
}

const sceneOf = (n: number) => (pageAt(n)?.scene ?? null) as Scene | null;
// Follow the page being read aloud; otherwise the spread we are on (or turning to).
const scene = computed<Scene>(() => {
    const n = narratedPage.value ?? flip.value?.to ?? current.value;
    const s = sceneOf(n) ?? sceneOf(n + 1) ?? sceneOf(Math.min(n, total.value));
    return s && s in SCENES ? s : 'fireplace';
});
const sceneLabel = computed(() => SCENES[scene.value].label);

watch(
    [ambienceOn, scene],
    ([on, s]) => {
        void setAmbience(on ? s : null);
        if (on) preloadAmbience(sceneOf(current.value + step.value * 2));
    },
    { immediate: true },
);

function toggleAmbience() {
    ambienceOn.value = !ambienceOn.value;
    try {
        localStorage.setItem(AMBIENCE_KEY, ambienceOn.value ? '1' : '0');
    } catch {
        /* ignore */
    }
}

/* ── Table of contents ───────────────────────────────────────────────── */
const tocOpen = ref(false);
function jump(n: number) {
    tocOpen.value = false;
    turnTo(n);
}
const isLocked = (n: number) => !!pageAt(n)?.locked;

/* ── Input: keys, swipes, the page corners ───────────────────────────── */
function onKey(e: KeyboardEvent) {
    if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return;
    if (e.key === 'ArrowRight' || e.key === 'PageDown') next();
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') prev();
    else if (e.key === ' ' && hasNarration.value) {
        e.preventDefault();
        toggleNarration();
    } else if (e.key === 'Escape') tocOpen.value = false;
}

/* Clicking the paper turns the page (the left page back, the right one on;
   on a single page, its outer thirds). Buttons and links on it still work. */
function onPageClick(e: MouseEvent, dir: -1 | 0 | 1) {
    if (flip.value || (e.target as Element).closest('a, button, input, label, video, .bp__overlay')) return;
    if (dir === 0) {
        const r = (e.currentTarget as HTMLElement).getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        dir = x < 0.33 ? -1 : x > 0.67 ? 1 : 0;
    }
    if (dir === 1) next();
    else if (dir === -1) prev();
}

let swipe: { x: number; y: number; t: number } | null = null;
function onPointerDown(e: PointerEvent) {
    swipe = { x: e.clientX, y: e.clientY, t: Date.now() };
}
function onPointerUp(e: PointerEvent) {
    if (!swipe) return;
    const dx = e.clientX - swipe.x;
    const dy = e.clientY - swipe.y;
    const quick = Date.now() - swipe.t < 600;
    swipe = null;
    if (quick && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.4) {
        if (dx < 0) next();
        else prev();
    }
}

/* ── Lifecycle ───────────────────────────────────────────────────────── */
let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    measure();
    current.value = normalise(current.value);
    resizeObserver = new ResizeObserver(() => {
        const wasSpread = spread.value;
        measure();
        if (wasSpread !== spread.value) current.value = normalise(current.value);
    });
    if (stage.value) resizeObserver.observe(stage.value);
    window.addEventListener('keydown', onKey);
    window.addEventListener('pointerdown', () => preload('flip', 'coin', 'unlock', 'sparkle'), { once: true });
    void afterArrive();
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    window.removeEventListener('keydown', onKey);
    clearTimeout(flipTimer);
    audio?.pause();
    audio = null;
    void setAmbience(null);
});

const pageProps = computed(() => ({
    pricePerPage: props.book.price_per_page,
    coins: coins.value,
    lockCost: lockCost.value,
    isAuthenticated: props.accessConfig.isAuthenticated,
    returnUrl: returnUrl.value,
    unlocking: unlocking.value,
    registeredFreePages: props.accessConfig.registeredFreePages,
}));

const sliderValue = computed(() => Math.min(current.value, total.value));
function onSlide(e: Event) {
    turnTo(Number((e.target as HTMLInputElement).value));
}
</script>

<template>
    <Head :title="`${book.title} — str. ${Math.min(current, total)}`" />

    <div class="reader" :class="ambienceOn && ambienceScene ? `scene--${ambienceScene}` : ''">
        <div class="reader__backdrop" aria-hidden="true">
            <NightSky :stars="40" :moon="false" :seed="5" />
            <div class="reader__glow" />
            <div class="reader__rain" />
            <Fireflies class="reader__flies" :count="16" />
        </div>

        <!-- ── Top bar ────────────────────────────────────────────────── -->
        <header class="reader__top">
            <Link :href="route('home')" class="reader__home" aria-label="Strona główna">
                <Logo :word="false" />
            </Link>
            <div class="reader__title">
                <strong>{{ book.title }}</strong>
                <Transition name="fade" mode="out-in">
                    <span :key="currentChapter?.title">{{ currentChapter?.title ?? '' }}</span>
                </Transition>
            </div>
            <div class="reader__tools">
                <button type="button" class="tool" :class="{ 'is-on': tocOpen }" title="Spis treści" data-sfx="whoosh" @click="tocOpen = !tocOpen">
                    <List /><span class="tool__label">Spis treści</span>
                </button>
                <button
                    type="button"
                    class="tool"
                    :class="{ 'is-on': ambienceOn }"
                    :title="ambienceOn ? `Klimat: ${sceneLabel} — wyłącz` : 'Włącz odgłosy otoczenia'"
                    data-sfx="none"
                    @click="toggleAmbience"
                >
                    <AudioLines />
                    <span class="tool__label">Klimat</span>
                    <Transition name="fade" mode="out-in">
                        <span v-if="ambienceOn" :key="sceneLabel" class="tool__scene">{{ sceneLabel }}</span>
                    </Transition>
                </button>
                <SoundToggle />
                <CoinPurse v-if="user" :coins="coins" />
                <Link v-else :href="route('login', { return: returnUrl })" class="btn btn--gold btn--sm reader__login">Zaloguj się</Link>
            </div>
        </header>

        <!-- ── The book ───────────────────────────────────────────────── -->
        <main ref="stage" class="reader__stage" @pointerdown="onPointerDown" @pointerup="onPointerUp">
            <div
                v-if="size.w"
                ref="bookEl"
                class="book"
                :class="{ 'book--spread': spread, 'book--single': !spread, 'is-flipping': flip }"
                :style="{ '--pw': `${size.w}px`, '--ph': `${size.h}px` }"
            >
                <div class="book__cover" />
                <div class="book__edges" />

                <template v-if="spread">
                    <div class="book__slot book__slot--left" @click="onPageClick($event, -1)">
                        <BookPage
                            :key="`L${under.left}`"
                            :page="pageAt(under.left!)"
                            :number="under.left!"
                            side="left"
                            :is-end="under.left! > total"
                            :chapter-number="chapterNumberOf(under.left!)"
                            :narrating="narratedPage === under.left"
                            :narration-progress="narrationProgress"
                            v-bind="pageProps"
                            v-model:auto-unlock="autoUnlock"
                            @unlock="unlock"
                            @restart="turnTo(1)"
                        />
                        <span v-if="canPrev && !flip" class="book__corner book__corner--left" aria-hidden="true" />
                    </div>
                    <div class="book__slot book__slot--right" @click="onPageClick($event, 1)">
                        <BookPage
                            :key="`R${under.right}`"
                            :page="pageAt(under.right!)"
                            :number="under.right!"
                            side="right"
                            :is-end="under.right === total + 1"
                            :teaser="!!pageAt(under.left!)?.locked && pageAt(under.left!)?.locked === pageAt(under.right!)?.locked"
                            :chapter-number="chapterNumberOf(under.right!)"
                            :narrating="narratedPage === under.right"
                            :narration-progress="narrationProgress"
                            v-bind="pageProps"
                            v-model:auto-unlock="autoUnlock"
                            @unlock="unlock"
                            @restart="turnTo(1)"
                        />
                        <span v-if="canNext && !flip" class="book__corner book__corner--right" aria-hidden="true" />
                    </div>
                    <div class="book__spine" />
                </template>

                <div v-else class="book__slot book__slot--single" @click="onPageClick($event, 0)">
                    <BookPage
                        :key="`S${under.single}`"
                        :page="pageAt(under.single!)"
                        :number="under.single!"
                        side="single"
                        :is-end="under.single! > total"
                        :chapter-number="chapterNumberOf(under.single!)"
                        :narrating="narratedPage === under.single"
                        :narration-progress="narrationProgress"
                        v-bind="pageProps"
                        v-model:auto-unlock="autoUnlock"
                        @unlock="unlock"
                        @restart="turnTo(1)"
                    />
                </div>

                <!-- The turning leaf -->
                <div
                    v-if="flip && leaf"
                    class="leaf"
                    :class="[spread ? (flip.dir === 1 ? 'leaf--next' : 'leaf--prev') : flip.dir === 1 ? 'leaf--single-next' : 'leaf--single-prev']"
                    :style="{ animationDuration: `${FLIP_MS}ms`, '--flip-ms': `${FLIP_MS}ms` }"
                >
                    <div class="leaf__face leaf__face--front">
                        <BookPage
                            :page="pageAt(leaf.front)"
                            :number="leaf.front"
                            :side="spread ? (flip.dir === 1 ? 'right' : 'left') : 'single'"
                            :is-end="leaf.front > total"
                            :chapter-number="chapterNumberOf(leaf.front)"
                            still
                            v-bind="pageProps"
                        />
                    </div>
                    <div class="leaf__face leaf__face--back">
                        <BookPage
                            v-if="leaf.back !== null"
                            :page="pageAt(leaf.back)"
                            :number="leaf.back"
                            :side="flip.dir === 1 ? 'left' : 'right'"
                            :is-end="leaf.back > total"
                            :chapter-number="chapterNumberOf(leaf.back)"
                            still
                            v-bind="pageProps"
                        />
                        <div v-else class="leaf__blank" />
                    </div>
                </div>
            </div>
        </main>

        <!-- ── Controls ───────────────────────────────────────────────── -->
        <footer class="reader__bar">
            <button type="button" class="turn" :disabled="!canPrev || !!flip" aria-label="Poprzednia strona" data-sfx="none" @click="prev">
                <ChevronLeft />
            </button>

            <div class="reader__mid">
                <button
                    type="button"
                    class="narrate"
                    :class="{ 'is-on': narrating }"
                    :disabled="!hasNarration && !narrating"
                    data-sfx="none"
                    @click="toggleNarration"
                >
                    <Pause v-if="narrating" />
                    <Play v-else />
                    <span>{{ narrating ? 'Pauza' : 'Czytaj mi' }}</span>
                </button>
                <button v-if="narrating || narratedPage" type="button" class="speed" title="Tempo lektora" data-sfx="pop" @click="cycleSpeed">
                    <Gauge /> {{ speed }}×
                </button>

                <div class="scrub">
                    <input
                        type="range"
                        min="1"
                        :max="total"
                        :value="sliderValue"
                        :step="step"
                        aria-label="Przejdź do strony"
                        :style="{ '--fill': `${((sliderValue - 1) / Math.max(1, total - 1)) * 100}%` }"
                        @change="onSlide"
                    />
                    <div class="scrub__marks" aria-hidden="true">
                        <i v-for="c in chapters" :key="c.page" :style="{ left: `${((c.page - 1) / Math.max(1, total - 1)) * 100}%` }" />
                    </div>
                    <span class="scrub__label">{{ progressLabel }}</span>
                </div>
            </div>

            <button type="button" class="turn turn--next" :disabled="!canNext || !!flip" aria-label="Następna strona" data-sfx="none" @click="next">
                <ChevronRight />
            </button>
        </footer>

        <!-- ── Contents drawer ────────────────────────────────────────── -->
        <Transition name="drawer">
            <aside v-if="tocOpen" class="toc" aria-label="Spis treści">
                <header class="toc__head">
                    <h2 class="h3">Spis treści</h2>
                    <button type="button" class="tool" aria-label="Zamknij" @click="tocOpen = false"><X /></button>
                </header>
                <ol class="toc__list">
                    <li v-for="(c, i) in chapters" :key="c.page">
                        <button
                            type="button"
                            :class="{ 'is-here': currentChapter?.page === c.page }"
                            data-sfx="none"
                            :style="{ '--i': i }"
                            @click="jump(c.page)"
                        >
                            <span class="toc__no">{{ i + 1 }}</span>
                            <span class="toc__title">{{ c.title }}</span>
                            <Lock v-if="isLocked(c.page)" class="toc__lock" />
                            <span class="toc__page">{{ c.page }}</span>
                        </button>
                    </li>
                </ol>
            </aside>
        </Transition>
        <Transition name="fade">
            <div v-if="tocOpen" class="toc__scrim" @click="tocOpen = false" />
        </Transition>

        <FlashToast />
    </div>
</template>

<style scoped>
.reader {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    color: var(--cream);
    background: radial-gradient(120% 90% at 50% 30%, #1d403c 0%, var(--night) 45%, var(--night-deep) 100%);
    user-select: none;
    -webkit-user-select: none;
    touch-action: pan-y;
}

.reader__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.reader__glow {
    position: absolute;
    inset: auto -10% -30%;
    height: 70%;
    background: radial-gradient(50% 50% at 50% 50%, rgb(232 100 58 / 0.45), transparent 70%);
    opacity: 0;
    transition: opacity 1.5s;
    animation: fire-flicker 2.2s ease-in-out infinite alternate;
}

.scene--fireplace .reader__glow {
    opacity: 1;
}

.scene--magic .reader__glow {
    opacity: 0.8;
    background: radial-gradient(50% 50% at 50% 50%, rgb(155 138 214 / 0.45), transparent 70%);
}

/* Rain streaking down behind the book */
.reader__rain {
    position: absolute;
    inset: -20% 0 0;
    opacity: 0;
    transition: opacity 2.5s;
    background-image:
        linear-gradient(105deg, transparent 49.6%, rgb(200 225 235 / 0.35) 50%, transparent 50.4%),
        linear-gradient(105deg, transparent 49.7%, rgb(200 225 235 / 0.22) 50%, transparent 50.3%);
    background-size:
        90px 180px,
        57px 130px;
    animation: rain 0.7s linear infinite;
}

.scene--rain .reader__rain {
    opacity: 1;
}

@keyframes rain {
    to {
        background-position:
            -30px 180px,
            -20px 130px;
    }
}

.reader__flies {
    opacity: 0;
    transition: opacity 2.5s;
}

.scene--night .reader__flies,
.scene--wind .reader__flies,
.scene--meadow .reader__flies {
    opacity: 1;
}

@keyframes fire-flicker {
    0% {
        transform: scale(1) translateY(0);
        filter: brightness(1);
    }
    33% {
        transform: scale(1.04) translateY(-2%);
        filter: brightness(1.15);
    }
    66% {
        transform: scale(0.98) translateY(1%);
        filter: brightness(0.9);
    }
    100% {
        transform: scale(1.02) translateY(-1%);
        filter: brightness(1.1);
    }
}

/* ── Top bar ───────────────────────────────────────────────────────── */
.reader__top {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    height: 3.75rem;
    padding: 0 clamp(0.75rem, 2vw, 1.5rem);
    flex: none;
    --logo-gutter: var(--night);
}

.reader__home {
    flex: none;
}

.reader__title {
    display: grid;
    min-width: 0;
    line-height: 1.2;
}

.reader__title strong {
    font-family: var(--font-display);
    font-style: italic;
    font-weight: 600;
    font-size: 1.05rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.reader__title span {
    font-size: 0.8rem;
    font-weight: 700;
    color: rgb(251 244 230 / 0.6);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.reader__tools {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-left: auto;
}

.tool {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 2.6rem;
    padding: 0 0.8rem;
    border-radius: 999px;
    font-weight: 800;
    font-size: 0.88rem;
    background: rgb(255 255 255 / 0.07);
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce),
        color 0.2s;
}

.tool:hover {
    background: rgb(255 255 255 / 0.14);
    transform: translateY(-1px);
}

.tool.is-on {
    background: var(--gold);
    color: var(--night-deep);
}

.tool svg {
    width: 1.15rem;
    height: 1.15rem;
}

.tool__scene {
    padding: 0.1rem 0.5rem;
    border-radius: 999px;
    background: rgb(11 28 29 / 0.15);
    font-size: 0.78rem;
    font-weight: 800;
    white-space: nowrap;
}

@media (max-width: 900px) {
    .tool__label,
    .tool__scene {
        display: none;
    }
    .tool {
        width: 2.6rem;
        justify-content: center;
        padding: 0;
    }
}

@media (max-width: 560px) {
    .reader__title {
        display: none;
    }
    .reader__login {
        padding-inline: 0.8rem;
    }
}

/* ── Stage & book ──────────────────────────────────────────────────── */
.reader__stage {
    position: relative;
    z-index: 1;
    flex: 1;
    min-height: 0;
    margin: 0.25rem clamp(0.75rem, 3vw, 3rem) 0.5rem;
    display: grid;
    place-items: center;
}

.book {
    position: relative;
    display: flex;
    width: calc(var(--pw) * 2);
    height: var(--ph);
    perspective: calc(var(--pw) * 5);
    animation: book-in 0.9s var(--ease-out);
}

.book--single {
    width: var(--pw);
}

@keyframes book-in {
    from {
        opacity: 0;
        transform: translateY(24px) scale(0.97);
    }
}

/* The hard cover peeking out round the pages */
.book__cover {
    position: absolute;
    inset: -10px -12px -12px;
    border-radius: 10px;
    background:
        linear-gradient(90deg, transparent 48.5%, rgb(0 0 0 / 0.35) 50%, transparent 51.5%),
        linear-gradient(180deg, #7a3b26, #5a2a1b);
    box-shadow:
        0 40px 70px -30px rgb(0 0 0 / 0.75),
        0 14px 30px -10px rgb(0 0 0 / 0.5);
}

.book--single .book__cover {
    inset: -8px -10px -10px -6px;
    background: linear-gradient(180deg, #7a3b26, #5a2a1b);
}

/* Stacked page edges */
.book__edges {
    position: absolute;
    inset: -4px -5px -6px;
    border-radius: 6px;
    background: repeating-linear-gradient(180deg, #efe3c6 0 2px, #d9c8a2 2px 3px);
}

.book--single .book__edges {
    inset: -3px -5px -5px 0;
}

.book__slot {
    position: relative;
    width: var(--pw);
    height: var(--ph);
    cursor: pointer;
}

.book__slot--left {
    border-radius: 4px 0 0 4px;
    overflow: hidden;
}

.book__slot--right {
    border-radius: 0 4px 4px 0;
    overflow: hidden;
}

.book__slot--single {
    border-radius: 2px 4px 4px 2px;
    overflow: hidden;
}

.book__spine {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 30px;
    margin-left: -15px;
    pointer-events: none;
    background: linear-gradient(90deg, transparent, rgb(60 40 15 / 0.18) 45%, rgb(60 40 15 / 0.28) 50%, rgb(60 40 15 / 0.18) 55%, transparent);
}

/* A dog-ear that lifts on hover, inviting a turn */
.book__corner {
    position: absolute;
    bottom: 0;
    width: 0;
    height: 0;
    pointer-events: none;
    transition:
        width 0.35s var(--ease-bounce),
        height 0.35s var(--ease-bounce);
    filter: drop-shadow(-3px -3px 4px rgb(60 40 15 / 0.25));
}

.book__corner--right {
    right: 0;
    background: linear-gradient(135deg, #f3e6c8 50%, transparent 50%);
}

.book__corner--left {
    left: 0;
    background: linear-gradient(225deg, #f3e6c8 50%, transparent 50%);
    filter: drop-shadow(3px -3px 4px rgb(60 40 15 / 0.25));
}

.book__slot:hover .book__corner {
    width: 46px;
    height: 46px;
}

/* ── The turning leaf ──────────────────────────────────────────────── */
.leaf {
    position: absolute;
    top: 0;
    width: var(--pw);
    height: var(--ph);
    transform-style: preserve-3d;
    animation-timing-function: cubic-bezier(0.45, 0.05, 0.3, 1);
    animation-fill-mode: forwards;
    z-index: 5;
    pointer-events: none;
}

.leaf--next {
    left: 50%;
    transform-origin: left center;
    animation-name: leaf-next;
}

.leaf--prev {
    left: 0;
    transform-origin: right center;
    animation-name: leaf-prev;
}

.leaf--single-next {
    left: 0;
    transform-origin: left center;
    animation-name: leaf-next;
}

.leaf--single-prev {
    left: 0;
    transform-origin: left center;
    animation-name: leaf-single-prev;
}

@keyframes leaf-next {
    from {
        transform: rotateY(0deg);
    }
    to {
        transform: rotateY(-180deg);
    }
}

@keyframes leaf-prev {
    from {
        transform: rotateY(0deg);
    }
    to {
        transform: rotateY(180deg);
    }
}

@keyframes leaf-single-prev {
    from {
        transform: rotateY(-180deg);
    }
    to {
        transform: rotateY(0deg);
    }
}

.leaf__face {
    position: absolute;
    inset: 0;
    overflow: hidden;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.leaf__face--back {
    transform: rotateY(180deg);
}

/* Light and shade sweeping over the leaf as it lifts */
.leaf__face::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    animation: leaf-shade var(--flip-ms) ease-in-out forwards;
}

.leaf--next .leaf__face--front::after,
.leaf--single-next .leaf__face--front::after {
    background: linear-gradient(90deg, rgb(0 0 0 / 0.25), transparent 40%, rgb(255 255 255 / 0.25));
}

.leaf--prev .leaf__face--front::after {
    background: linear-gradient(270deg, rgb(0 0 0 / 0.25), transparent 40%, rgb(255 255 255 / 0.25));
}

.leaf__face--back::after {
    background: linear-gradient(90deg, rgb(255 255 255 / 0.2), transparent 50%, rgb(0 0 0 / 0.25));
}

@keyframes leaf-shade {
    0%,
    100% {
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
}

.leaf__blank {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #f3e6c8, #fbf3e1);
}

/* ── Controls ──────────────────────────────────────────────────────── */
.reader__bar {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: clamp(0.5rem, 2vw, 1.5rem);
    height: 4.5rem;
    padding: 0 clamp(0.75rem, 2vw, 1.5rem) env(safe-area-inset-bottom);
    flex: none;
}

.turn {
    display: grid;
    place-items: center;
    width: 3.2rem;
    height: 3.2rem;
    flex: none;
    border-radius: 50%;
    background: var(--cream);
    color: var(--night);
    box-shadow: 0 4px 0 #c9b98f;
    transition:
        transform 0.3s var(--ease-bounce),
        box-shadow 0.2s,
        opacity 0.2s;
}

.turn:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 0 #c9b98f;
}

.turn:active:not(:disabled) {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #c9b98f;
}

.turn:disabled {
    opacity: 0.3;
    cursor: default;
}

.turn svg {
    width: 1.6rem;
    height: 1.6rem;
}

.turn--next:not(:disabled) {
    background: var(--gold);
    box-shadow: 0 4px 0 #c98a1d;
}

.reader__mid {
    flex: 1;
    display: flex;
    align-items: center;
    gap: clamp(0.5rem, 1.5vw, 1rem);
    min-width: 0;
}

.narrate {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    height: 2.8rem;
    padding: 0 1.1rem 0 0.9rem;
    flex: none;
    border-radius: 999px;
    font-weight: 900;
    background: rgb(255 255 255 / 0.1);
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce);
}

.narrate:hover:not(:disabled) {
    background: rgb(255 255 255 / 0.18);
    transform: scale(1.04);
}

.narrate:disabled {
    opacity: 0.35;
}

.narrate.is-on {
    background: var(--moss);
    box-shadow: 0 0 0 4px rgb(124 196 164 / 0.25);
}

.narrate svg {
    width: 1.2rem;
    height: 1.2rem;
}

.speed {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    height: 2.4rem;
    padding: 0 0.7rem;
    flex: none;
    border-radius: 999px;
    font-weight: 900;
    font-size: 0.85rem;
    font-variant-numeric: tabular-nums;
    background: rgb(255 255 255 / 0.08);
}

.speed svg {
    width: 1rem;
    height: 1rem;
}

.scrub {
    position: relative;
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.scrub input {
    flex: 1;
    min-width: 0;
    height: 0.5rem;
    border-radius: 999px;
    appearance: none;
    background: linear-gradient(90deg, var(--gold) var(--fill), rgb(255 255 255 / 0.15) var(--fill));
    cursor: pointer;
}

.scrub input::-webkit-slider-thumb {
    appearance: none;
    width: 1.3rem;
    height: 1.3rem;
    border-radius: 50%;
    background: var(--cream);
    border: 4px solid var(--gold);
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.4);
    transition: transform 0.2s var(--ease-bounce);
}

.scrub input::-webkit-slider-thumb:hover {
    transform: scale(1.25);
}

.scrub input::-moz-range-thumb {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--cream);
    border: 4px solid var(--gold);
}

.scrub__marks {
    position: absolute;
    left: 0.65rem;
    right: calc(0.65rem + var(--label-w, 6.5rem));
    top: 50%;
    height: 0;
    pointer-events: none;
}

.scrub__marks i {
    position: absolute;
    top: -2px;
    width: 4px;
    height: 4px;
    margin-left: -2px;
    border-radius: 50%;
    background: rgb(11 28 29 / 0.6);
}

.scrub__label {
    flex: none;
    width: 6.5rem;
    text-align: right;
    font-weight: 900;
    font-size: 0.9rem;
    font-variant-numeric: tabular-nums;
    color: rgb(251 244 230 / 0.75);
}

@media (max-width: 640px) {
    .narrate span {
        display: none;
    }
    .narrate {
        width: 2.8rem;
        padding: 0;
        justify-content: center;
    }
    .scrub__label {
        width: 4.5rem;
        font-size: 0.8rem;
    }
    .scrub__marks {
        right: calc(0.65rem + 4.5rem);
    }
}

/* ── Contents ──────────────────────────────────────────────────────── */
.toc {
    position: absolute;
    z-index: 30;
    top: 0;
    bottom: 0;
    left: 0;
    width: min(26rem, 88vw);
    display: flex;
    flex-direction: column;
    background: var(--paper);
    color: var(--ink);
    box-shadow: 20px 0 60px -20px rgb(0 0 0 / 0.6);
    border-radius: 0 1.75rem 1.75rem 0;
}

.toc__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.25rem 0.75rem 1.75rem;
}

.toc__head .tool {
    background: var(--sand);
}

.toc__list {
    flex: 1;
    overflow-y: auto;
    padding: 0.25rem 0.75rem 1.5rem;
}

.toc__list button {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    text-align: left;
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce);
    animation: toc-in 0.5s var(--ease-out) backwards;
    animation-delay: calc(var(--i) * 35ms);
}

@keyframes toc-in {
    from {
        opacity: 0;
        transform: translateX(-16px);
    }
}

.toc__list button:hover {
    background: var(--sand);
    transform: translateX(4px);
}

.toc__list button.is-here {
    background: #dcebdf;
}

.toc__no {
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    flex: none;
    border-radius: 50%;
    font-weight: 900;
    font-size: 0.85rem;
    background: var(--night);
    color: var(--gold);
}

.toc__title {
    flex: 1;
    font-family: var(--font-display);
    font-size: 1.02rem;
    line-height: 1.25;
}

.toc__lock {
    width: 0.95rem;
    height: 0.95rem;
    color: var(--mute);
}

.toc__page {
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--mute);
    font-variant-numeric: tabular-nums;
}

.toc__scrim {
    position: absolute;
    inset: 0;
    z-index: 25;
    background: rgb(11 28 29 / 0.5);
    backdrop-filter: blur(2px);
}

.drawer-enter-active,
.drawer-leave-active {
    transition: transform 0.5s var(--ease-out);
}

.drawer-enter-from,
.drawer-leave-to {
    transform: translateX(-105%);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
