<script setup lang="ts">
import CoinIcon from '@/components/story/CoinIcon.vue';
import Dragon from '@/components/story/Dragon.vue';
import type { ReaderPage } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Image as ImageIcon, LoaderCircle, Lock, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/*
 * One page of the book. Whatever is on it — text, an illustration, a lock or
 * the last page — has to fit the paper exactly: the reader never scrolls.
 * So after every layout the text is sized down (and the picture shrunk, or
 * tucked behind a button) until everything sits on the page.
 */
const props = withDefaults(
    defineProps<{
        page: ReaderPage | null;
        number: number;
        side: 'left' | 'right' | 'single';
        /** Past the last page: the "the end" page. */
        isEnd?: boolean;
        chapterNumber?: number | null;
        /** A face on a turning leaf: no video, no buttons. */
        still?: boolean;
        narrating?: boolean;
        narrationProgress?: number;
        // lock context
        pricePerPage?: number;
        coins?: number;
        lockCost?: number;
        isAuthenticated?: boolean;
        returnUrl?: string;
        unlocking?: boolean;
        autoUnlock?: boolean;
        registeredFreePages?: number;
        /** The facing page already shows the lock card; this one just teases. */
        teaser?: boolean;
    }>(),
    { isEnd: false, still: false, narrating: false, narrationProgress: 0, chapterNumber: null },
);

const emit = defineEmits<{ unlock: []; 'update:autoUnlock': [value: boolean]; restart: [] }>();

const root = ref<HTMLElement | null>(null);
const inner = ref<HTMLElement | null>(null);
const text = ref<HTMLElement | null>(null);
const figure = ref<HTMLElement | null>(null);

const mediaFits = ref(true);
const showMedia = ref(false);
const fitted = ref(false);
const videoReady = ref(false);

const readable = computed(() => !!props.page && !props.page.locked && props.page.content !== null);

const body = computed(() => {
    const content = props.page?.content ?? '';
    return content
        .replace(/\[chapter\][\s\S]*?\[\/chapter\]/, '')
        .trim()
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean)
        .map((p) => `<p>${p.replace(/\n/g, '<br>')}</p>`)
        .join('');
});

function fit() {
    const box = inner.value;
    const t = text.value;
    if (!box || !t || !readable.value) {
        fitted.value = true;
        return;
    }

    const H = box.clientHeight;
    const W = box.clientWidth;
    if (!H || !W) return;

    const fig = figure.value;
    const maxSize = Math.min(21, Math.max(15, W / 22));
    const fits = () => t.offsetHeight <= H;
    const done = (media: 'block' | 'float' | 'none') => {
        t.dataset.media = media;
        mediaFits.value = media !== 'none' || !fig;
        fitted.value = true;
        // Show it in this very frame, not after Vue's next render.
        root.value?.classList.add('is-fitted');
    };

    if (fig) {
        // 1. The picture across the page, above comfortable text.
        t.dataset.media = 'block';
        fig.style.width = '';
        const floor = Math.max(H * 0.28, 110);
        fig.style.height = `${floor}px`;
        for (let size = maxSize; size >= Math.max(15, maxSize - 3); size -= 0.5) {
            t.style.fontSize = `${size}px`;
            if (fits()) {
                fig.style.height = `${Math.min(floor + H - t.offsetHeight, H * 0.56, W * 0.85)}px`;
                return done('block');
            }
        }

        // 2. The picture tucked into the outer corner, text flowing round it.
        t.dataset.media = 'float';
        fig.style.height = '';
        for (let size = maxSize; size >= 13.5; size -= 0.5) {
            t.style.fontSize = `${size}px`;
            for (const width of [0.5, 0.44, 0.38]) {
                fig.style.width = `${width * 100}%`;
                if (fits()) return done('float');
            }
        }
    }

    // 3. The text alone, as large as it will go (the picture waits behind a button).
    t.dataset.media = 'none';
    let size = maxSize;
    t.style.fontSize = `${size}px`;
    while (!fits() && size > 10.5) {
        size -= 0.5;
        t.style.fontSize = `${size}px`;
    }
    done('none');
}

let observer: ResizeObserver | null = null;
let raf = 0;
const schedule = () => {
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(fit);
};

onMounted(() => {
    fit();
    document.fonts?.ready.then(schedule);
    observer = new ResizeObserver(schedule);
    if (root.value) observer.observe(root.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    cancelAnimationFrame(raf);
});

watch(
    () => [props.page?.id, props.page?.content],
    async () => {
        fitted.value = false;
        videoReady.value = false;
        showMedia.value = false;
        await nextTick();
        fit();
    },
);

const lockKind = computed(() => (props.page?.locked ? props.page.locked : null));
const canAfford = computed(() => (props.coins ?? 0) >= (props.lockCost ?? 0));
const registerHref = computed(() => route('register', { return: props.returnUrl }));
const loginHref = computed(() => route('login', { return: props.returnUrl }));
const coinsHref = computed(() => route('coins.purchase', { return: props.returnUrl }));
</script>

<template>
    <article
        ref="root"
        class="bp"
        :class="[`bp--${side}`, { 'bp--chapter': page?.chapter && readable, 'bp--locked': lockKind, 'bp--end': isEnd, 'is-fitted': fitted, 'is-narrating': narrating }]"
    >
        <div class="bp__paper" />

        <!-- ── Readable page ───────────────────────────────────────── -->
        <div v-if="readable" ref="inner" class="bp__inner">

            <div ref="text" class="bp__text">
                <header v-if="page!.chapter" class="bp__chapter">
                    <span v-if="chapterNumber" class="bp__chapter-no">Rozdział {{ chapterNumber }}</span>
                    <h2>{{ page!.chapter }}</h2>
                    <svg viewBox="0 0 120 12" aria-hidden="true">
                        <path d="M2 6 H48 M72 6 H118" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                        <path d="M60 1 l3 5 -3 5 -3 -5 Z" fill="currentColor" />
                    </svg>
                </header>
                <figure v-if="page!.image" ref="figure" class="bp__figure">
                    <img :src="page!.image" alt="" draggable="false" />
                    <video
                        v-if="page!.video && !still"
                        :src="page!.video"
                        :class="{ 'is-ready': videoReady }"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        @canplay="videoReady = true"
                    />
                </figure>
                <div class="bp__body" v-html="body" />
            </div>
        </div>

        <!-- ── Locked page ─────────────────────────────────────────── -->
        <div v-else-if="page && lockKind" class="bp__lock">
            <img v-if="page.image" :src="page.image" alt="" class="bp__lock-bg" draggable="false" />
            <div v-if="teaser" class="bp__teaser">
                <svg viewBox="0 0 64 64" aria-hidden="true" class="float">
                    <path d="M32 6 l6 18 18 2 -14 12 4 18 -14 -9 -14 9 4 -18 -14 -12 18 -2 Z" fill="var(--gold)" />
                </svg>
                <p>Co będzie dalej?</p>
            </div>
            <div v-else class="bp__lock-card">
                <span class="bp__padlock" :class="{ 'is-opening': unlocking }"><Lock /></span>
                <p v-if="page.chapter" class="bp__lock-chapter">{{ page.chapter }}</p>

                <template v-if="lockKind === 'login'">
                    <h3>Psst… dalej czytamy z kontem!</h3>
                    <p>Załóż darmowe konto, a odblokujesz strony aż do {{ registeredFreePages }}. Zapamiętamy też, gdzie skończyłeś.</p>
                    <div v-if="!still" class="bp__lock-actions">
                        <Link :href="registerHref" class="btn btn--gold btn--sm">Załóż darmowe konto</Link>
                        <Link :href="loginHref" class="bp__lock-link">Mam już konto</Link>
                    </div>
                </template>

                <template v-else>
                    <h3>Ta strona czeka na odblokowanie</h3>
                    <p>
                        Każda strona to <strong>{{ pricePerPage }} monet</strong> i zostaje Twoja na zawsze. Masz
                        <strong>{{ coins }}</strong>.
                    </p>
                    <div v-if="!still" class="bp__lock-actions">
                        <button v-if="canAfford" type="button" class="btn btn--gold btn--sm" data-sfx="none" :disabled="unlocking" @click="emit('unlock')">
                            <LoaderCircle v-if="unlocking" class="animate-spin" />
                            <CoinIcon v-else />
                            Odblokuj za {{ lockCost }}
                        </button>
                        <Link v-else :href="coinsHref" class="btn btn--gold btn--sm"><CoinIcon /> Zdobądź monety</Link>
                        <label class="bp__auto">
                            <input type="checkbox" :checked="autoUnlock" @change="emit('update:autoUnlock', ($event.target as HTMLInputElement).checked)" />
                            Odblokowuj kolejne strony od razu
                        </label>
                    </div>
                </template>
            </div>
        </div>

        <!-- ── The end ─────────────────────────────────────────────── -->
        <div v-else-if="isEnd" class="bp__end">
            <div class="bp__end-dragon float"><Dragon /></div>
            <h3>Koniec…</h3>
            <p>…a może dopiero początek? Smok, Olga, Igor, Kukiełka i Apetyt dziękują za wspólne czytanie.</p>
            <div v-if="!still" class="bp__lock-actions">
                <button type="button" class="btn btn--sm" data-sfx="whoosh" @click="emit('restart')">Od początku</button>
                <Link :href="route('dashboard')" class="bp__lock-link">Moja półka</Link>
            </div>
        </div>

        <!-- Inside of the back cover -->
        <div v-else class="bp__blank" />

        <footer v-if="page || isEnd" class="bp__folio">
            <span v-if="!isEnd">{{ number }}</span>
            <span v-else>✦</span>
        </footer>

        <!-- The picture, when it did not fit beside the text -->
        <template v-if="readable && page!.image && !mediaFits && !still">
            <button type="button" class="bp__peek" title="Pokaż ilustrację" data-sfx="whoosh" @click="showMedia = true"><ImageIcon /></button>
            <Transition name="peek">
                <div v-if="showMedia" class="bp__overlay" @click="showMedia = false">
                    <img :src="page!.image" alt="" />
                    <video v-if="page!.video" :src="page!.video" autoplay muted loop playsinline />
                    <button type="button" class="bp__overlay-close" aria-label="Zamknij"><X /></button>
                </div>
            </Transition>
        </template>

        <span v-if="narrating" class="bp__narration" aria-hidden="true">
            <i /><i /><i /><i />
        </span>
        <span v-if="narrating" class="bp__narration-bar" :style="{ transform: `scaleX(${narrationProgress})` }" />
    </article>
</template>

<style scoped>
.bp {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    color: #2a2622;
    container-type: size;
}

.bp__paper {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(120% 90% at 50% 40%, #fffdf6 0%, #fbf3e1 70%, #f3e6c8 100%);
}

/* Shading towards the spine, and a faint print texture */
.bp__paper::before,
.bp__paper::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.bp--left .bp__paper::before {
    background: linear-gradient(90deg, transparent 78%, rgb(120 90 40 / 0.1) 94%, rgb(80 55 20 / 0.22) 100%);
}

.bp--right .bp__paper::before {
    background: linear-gradient(270deg, transparent 78%, rgb(120 90 40 / 0.1) 94%, rgb(80 55 20 / 0.22) 100%);
}

.bp--single .bp__paper::before {
    background: linear-gradient(90deg, rgb(80 55 20 / 0.16) 0%, transparent 6%);
}

.bp__paper::after {
    opacity: 0.5;
    mix-blend-mode: multiply;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .5 0 0 0 0 .4 0 0 0 0 .25 0 0 0 .07 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

.bp__inner {
    position: absolute;
    inset: 6.5cqh 9cqw 8cqh;
    display: flex;
    flex-direction: column;
    gap: max(10px, 2.5cqh);
    opacity: 0;
}

.bp--left .bp__inner {
    right: 11cqw;
}

.bp--right .bp__inner {
    left: 11cqw;
}

.is-fitted .bp__inner {
    opacity: 1;
}

.bp__figure {
    position: relative;
    flex: none;
    margin: 0;
    display: none;
    border-radius: 14px;
    overflow: hidden;
    background: #efe2c4;
    box-shadow:
        0 0 0 5px #fffaf0,
        0 0 0 6px rgb(120 90 40 / 0.18),
        0 10px 24px -12px rgb(60 40 10 / 0.5);
    transform: rotate(-0.6deg);
}

.bp--right .bp__figure {
    transform: rotate(0.6deg);
}

.bp__figure img,
.bp__figure video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bp__figure video {
    opacity: 0;
    transition: opacity 0.8s;
}

.bp__figure video.is-ready {
    opacity: 1;
}

.bp__text {
    flex: none;
    display: flow-root;
    font-family: var(--font-display);
    font-variation-settings:
        'SOFT' 50,
        'WONK' 0;
    font-weight: 400;
    line-height: 1.55;
    hyphens: auto;
    -webkit-hyphens: auto;
    text-wrap: pretty;
}

/* Picture across the top: it leads the page, the chapter title follows. */
.bp__text[data-media='block'] {
    display: flex;
    flex-direction: column;
}

.bp__text[data-media='block'] .bp__figure {
    display: block;
    order: -1;
    width: 100%;
    margin-bottom: 1.1em;
}

/* Picture in the outer corner, the words flowing round it. */
.bp__text[data-media='float'] .bp__figure {
    display: block;
    float: right;
    aspect-ratio: 1;
    margin: 0.35em 0 0.6em 1.1em;
}

.bp--left .bp__text[data-media='float'] .bp__figure {
    float: left;
    margin: 0.35em 1.1em 0.6em 0;
}

.bp__body :deep(p) {
    margin: 0 0 0.6em;
    text-indent: 1.2em;
}

.bp__body :deep(p:first-child) {
    text-indent: 0;
}

.bp__body :deep(p:last-child) {
    margin-bottom: 0;
}

.bp__body :deep(b) {
    font-weight: 650;
}

.bp--chapter .bp__body :deep(p:first-child::first-letter) {
    float: left;
    font-size: 3.4em;
    line-height: 0.82;
    padding: 0.06em 0.1em 0 0;
    font-weight: 600;
    font-style: italic;
    color: var(--moss);
    font-variation-settings:
        'SOFT' 100,
        'WONK' 1;
}

.bp__chapter {
    display: grid;
    justify-items: center;
    gap: 0.25em;
    margin-bottom: 1em;
    text-align: center;
    color: var(--moss);
}

.bp__chapter-no {
    font-family: var(--font-body);
    font-size: 0.62em;
    font-weight: 900;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #b07d1e;
}

.bp__chapter h2 {
    font-size: 1.55em;
    line-height: 1.1;
    font-weight: 500;
    font-style: italic;
    color: var(--night);
    font-variation-settings:
        'SOFT' 100,
        'WONK' 1;
    text-wrap: balance;
}

.bp__chapter svg {
    width: 6em;
    margin-top: 0.15em;
}

.bp__folio {
    position: absolute;
    bottom: 3cqh;
    left: 0;
    right: 0;
    text-align: center;
    font-family: var(--font-display);
    font-style: italic;
    font-size: clamp(11px, 2.2cqh, 15px);
    color: #9a8a6a;
}

.bp--left .bp__folio {
    text-align: left;
    padding-left: 9cqw;
}

.bp--right .bp__folio {
    text-align: right;
    padding-right: 9cqw;
}

/* ── Lock ──────────────────────────────────────────────────────────── */
.bp__lock,
.bp__end {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 8cqh 9cqw;
}

.bp__lock-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: blur(18px) saturate(1.2);
    opacity: 0.45;
    transform: scale(1.15);
}

.bp__teaser {
    position: relative;
    display: grid;
    justify-items: center;
    gap: 1rem;
    font-family: var(--font-display);
    font-style: italic;
    font-size: clamp(1.1rem, 3.4cqh, 1.6rem);
    color: var(--night);
}

.bp__teaser svg {
    width: 4.5rem;
    filter: drop-shadow(0 6px 14px rgb(201 138 29 / 0.45));
}

.bp__lock-card {
    position: relative;
    display: grid;
    justify-items: center;
    gap: 0.8rem;
    max-width: 22rem;
    padding: clamp(1.25rem, 5cqh, 2.25rem) clamp(1rem, 6cqw, 2rem);
    border-radius: 1.5rem;
    text-align: center;
    background: rgb(255 251 242 / 0.92);
    box-shadow: 0 20px 50px -20px rgb(60 40 10 / 0.5);
    font-size: clamp(0.82rem, 2.3cqh, 1rem);
}

.bp__lock-card h3,
.bp__end h3 {
    font-family: var(--font-display);
    font-size: 1.45em;
    line-height: 1.15;
    font-weight: 600;
    color: var(--night);
    text-wrap: balance;
}

.bp__lock-card p,
.bp__end p {
    color: var(--ink-soft);
    line-height: 1.55;
}

.bp__lock-chapter {
    font-family: var(--font-display);
    font-style: italic;
    color: var(--moss) !important;
}

.bp__padlock {
    display: grid;
    place-items: center;
    width: 3.4rem;
    height: 3.4rem;
    border-radius: 50%;
    background: var(--gold);
    color: var(--night-deep);
    box-shadow: 0 0 0 6px rgb(242 184 75 / 0.25);
    animation: lock-wobble 3s var(--ease-in-out) infinite;
}

.bp__padlock svg {
    width: 1.5rem;
    height: 1.5rem;
}

.bp__padlock.is-opening {
    animation: lock-open 0.6s var(--ease-bounce) infinite alternate;
}

@keyframes lock-wobble {
    0%,
    80%,
    100% {
        transform: rotate(0);
    }
    85% {
        transform: rotate(-12deg);
    }
    90% {
        transform: rotate(10deg);
    }
    95% {
        transform: rotate(-5deg);
    }
}

@keyframes lock-open {
    to {
        transform: scale(1.15) rotate(10deg);
    }
}

.bp__lock-actions {
    display: grid;
    justify-items: center;
    gap: 0.7rem;
    margin-top: 0.3rem;
}

.bp__lock-actions .btn svg {
    width: 1.3em;
    height: 1.3em;
}

.bp__lock-link {
    font-weight: 800;
    color: var(--moss);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.bp__auto {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.82em;
    font-weight: 700;
    color: var(--ink-soft);
    cursor: pointer;
}

.bp__auto input {
    width: 1.05rem;
    height: 1.05rem;
    accent-color: var(--moss);
}

/* ── End ───────────────────────────────────────────────────────────── */
.bp__end {
    align-content: center;
    gap: 0.9rem;
    text-align: center;
    font-size: clamp(0.85rem, 2.4cqh, 1.05rem);
}

.bp__end-dragon {
    width: min(14rem, 60cqw);
}

.bp__blank {
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(45deg, rgb(21 50 47 / 0.05) 0 2px, transparent 2px 14px),
        #efe3c6;
}

/* ── Picture on demand ─────────────────────────────────────────────── */
.bp__peek {
    position: absolute;
    top: 2.5cqh;
    right: 3cqw;
    display: grid;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 50%;
    background: var(--gold);
    color: var(--night-deep);
    box-shadow: var(--shadow-soft);
    animation: bob 2.4s var(--ease-in-out) infinite;
}

.bp--left .bp__peek {
    right: auto;
    left: 3cqw;
}

.bp__peek svg {
    width: 1.15rem;
    height: 1.15rem;
}

.bp__overlay {
    position: absolute;
    inset: 0;
    z-index: 5;
    cursor: zoom-out;
}

.bp__overlay img,
.bp__overlay video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bp__overlay-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: grid;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 50%;
    background: rgb(255 250 240 / 0.9);
    color: var(--night);
}

.bp__overlay-close svg {
    width: 1.2rem;
}

.peek-enter-active,
.peek-leave-active {
    transition:
        opacity 0.3s,
        clip-path 0.6s var(--ease-in-out);
}

.peek-enter-from,
.peek-leave-to {
    opacity: 0;
    clip-path: circle(0% at 90% 5%);
}

.peek-enter-to,
.peek-leave-from {
    clip-path: circle(150% at 90% 5%);
}

/* ── Narration ─────────────────────────────────────────────────────── */
.bp__narration {
    position: absolute;
    top: 2.2cqh;
    left: 50%;
    translate: -50% 0;
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 16px;
    padding: 4px 8px;
    box-sizing: content-box;
    border-radius: 999px;
    background: rgb(47 93 74 / 0.12);
}

.bp__narration i {
    width: 3px;
    height: 100%;
    border-radius: 2px;
    background: var(--moss);
    animation: eq 0.9s ease-in-out infinite alternate;
}

.bp__narration i:nth-child(2) {
    animation-delay: -0.3s;
}

.bp__narration i:nth-child(3) {
    animation-delay: -0.6s;
}

.bp__narration i:nth-child(4) {
    animation-delay: -0.15s;
}

@keyframes eq {
    from {
        transform: scaleY(0.25);
    }
    to {
        transform: scaleY(1);
    }
}

.bp__narration-bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--moss), var(--gold));
    transform-origin: left;
    transition: transform 0.25s linear;
}
</style>
