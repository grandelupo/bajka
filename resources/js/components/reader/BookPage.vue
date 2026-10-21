<script setup lang="ts">
import CoinIcon from '@/components/story/CoinIcon.vue';
import Dragon from '@/components/story/Dragon.vue';
import type { PageSplit, ReaderPage } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle, Lock } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/*
 * One page of the book. Whatever is on it — text, an illustration, a lock or
 * the last page — has to fit the paper exactly: the reader never scrolls.
 * The picture is never hidden or cropped: it sits whole above the words, at
 * a comfortable size. A page that will not fit is cut by the reader, between
 * sentences, into parts (26A, 26B) — or, with two pages side by side, its
 * picture gets a whole page of its own facing the words (a plate).
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
        /** Where this page is cut into parts (measured by the reader); null: shown whole. */
        split?: PageSplit | null;
        /** Which part of the page to show. */
        part?: number;
        /** A full-page illustration, facing the page's words. */
        plate?: boolean;
        /** The picture has a page of its own, so the words come without it. */
        noFigure?: boolean;
        /** The page number to print, e.g. "26B"; none on a plate. */
        folio?: string | null;
        /** Never shown: only lends its layout to `measure()`. */
        measurer?: boolean;
    }>(),
    {
        isEnd: false,
        still: false,
        narrating: false,
        narrationProgress: 0,
        chapterNumber: null,
        split: null,
        part: 0,
        plate: false,
        noFigure: false,
        folio: null,
        measurer: false,
    },
);

const emit = defineEmits<{ unlock: []; 'update:autoUnlock': [value: boolean]; restart: [] }>();

const root = ref<HTMLElement | null>(null);
const inner = ref<HTMLElement | null>(null);
const text = ref<HTMLElement | null>(null);
const figure = ref<HTMLElement | null>(null);
const bodyEl = ref<HTMLElement | null>(null);

const fitted = ref(false);
const videoReady = ref(false);

const readable = computed(() => !!props.page && !props.page.locked && props.page.content !== null);

/* The words, as sentences: a page can only be cut between them. */
interface Sentence {
    para: number;
    first: boolean;
    text: string;
}

function sentencesOf(paragraph: string): string[] {
    const out: string[] = [];
    for (const piece of paragraph.split(/(?<=[.!?…]["”»)]?\s+)/)) {
        // never cut inside <b>…</b> or <i>…</i>, nor before "– zapytał"
        const open = (s: string) => (s.match(/<[bi]>/g)?.length ?? 0) - (s.match(/<\/[bi]>/g)?.length ?? 0);
        const said = /^[–-]\s*\p{Ll}/u.test(piece);
        if (out.length && (open(out[out.length - 1]) > 0 || said)) out[out.length - 1] += piece;
        else out.push(piece);
    }
    return out;
}

const textOf = (content: string | null | undefined): Sentence[] =>
    (content ?? '')
        .replace(/\[chapter\][\s\S]*?\[\/chapter\]/, '')
        .trim()
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean)
        .flatMap((p, para) => sentencesOf(p).map((text, i) => ({ para, first: i === 0, text })));

const sentences = computed(() => textOf(props.page?.content));

/** Sentences [from, to) as paragraphs; one carried over from the part before is not indented. */
function html(all: Sentence[], from: number, to: number) {
    let out = '';
    let open = -1;
    for (const s of all.slice(from, to)) {
        if (s.para !== open) {
            if (open >= 0) out += '</p>';
            out += s.first ? '<p>' : '<p class="cont">';
            open = s.para;
        }
        out += s.text.replace(/\n/g, '<br>');
    }
    return open >= 0 ? `${out}</p>` : '';
}

const partCount = computed(() => props.split?.starts.length ?? 1);
const partIndex = computed(() => Math.min(Math.max(props.part, 0), partCount.value - 1));
const firstPart = computed(() => partIndex.value === 0);
const body = computed(() => {
    const starts = props.split?.starts ?? [0];
    const k = partIndex.value;
    return html(sentences.value, starts[k] ?? 0, starts[k + 1] ?? sentences.value.length);
});
const showFigure = computed(() => !!props.page?.image && firstPart.value && !props.noFigure);
const folioText = computed(() => props.folio ?? String(props.number));

/* Sizes, the same for laying out and for measuring. */
function metrics(box: HTMLElement) {
    const H = box.clientHeight;
    const W = box.clientWidth;
    const maxSize = Math.min(21, Math.max(15, W / 22));
    return { H, W, maxSize, minSize: Math.max(15, maxSize - 3), side: Math.floor(Math.min(W, H * 0.5)) };
}

/** The picture whole, square, as wide as the page allows — or no picture. */
function dress(t: HTMLElement, fig: HTMLElement | null, side: number, withFigure: boolean) {
    if (fig) fig.style.display = withFigure ? '' : 'none';
    if (fig && withFigure) {
        t.dataset.media = 'square';
        fig.style.width = fig.style.height = `${side}px`;
    } else t.dataset.media = 'none';
}

function fit() {
    const box = inner.value;
    const t = text.value;
    if (props.measurer) return;
    if (!box || !t || !readable.value || props.plate) {
        fitted.value = true;
        return;
    }

    const { H, W, maxSize, minSize, side } = metrics(box);
    if (!H || !W) return;

    const fig = figure.value;
    dress(t, fig, side, !!fig);
    let size = maxSize;
    t.style.fontSize = `${size}px`;
    while (t.offsetHeight > H && size > 12) {
        // step down, never skipping the size the page was measured at
        size = size > minSize && size - 0.5 < minSize ? minSize : size - 0.5;
        t.style.fontSize = `${size}px`;
    }
    fitted.value = true;
    // Show it in this very frame, not after Vue's next render.
    root.value?.classList.add('is-fitted');
}

/**
 * Where to cut a page: fill the first part (title, picture, words) and each
 * next one (words only) with as many sentences as fit at a comfortable size.
 * Called on the hidden measuring page, dressed up as `page` for the occasion.
 */
function measure(page: ReaderPage, chapterNumber: number | null, withFigure: boolean): PageSplit {
    const box = inner.value!;
    const t = text.value!;
    const el = bodyEl.value!;
    const fig = figure.value;
    const head = t.querySelector<HTMLElement>('.bp__chapter');
    const all = textOf(page.content);
    const { H, minSize, side } = metrics(box);
    const fits = () => t.offsetHeight <= H;

    root.value!.classList.toggle('bp--chapter', !!page.chapter);
    if (head) {
        head.querySelector('h2')!.textContent = page.chapter ?? '';
        const no = head.querySelector<HTMLElement>('.bp__chapter-no');
        if (no) no.textContent = chapterNumber ? `Rozdział ${chapterNumber}` : '';
    }
    const picture = withFigure && !!page.image;

    const cutAt = (size: number) => {
        t.style.fontSize = `${size}px`;
        const starts = [0];
        for (let from = 0, part = 0; from < all.length; part++) {
            // later parts have no title and no picture
            if (head) head.style.display = part === 0 && page.chapter ? '' : 'none';
            dress(t, fig, side, part === 0 && picture);

            const fitsUpTo = (to: number) => {
                el.innerHTML = html(all, from, to);
                return fits();
            };
            if (fitsUpTo(all.length)) break;
            // the most sentences that fit; on a tiny page the picture may have its
            // part to itself, otherwise at least one sentence, so the page moves on
            let lo = from + 1;
            let hi = all.length - 1;
            if (!fitsUpTo(lo)) {
                if (part === 0 && (picture || page.chapter)) lo = from;
            } else {
                while (lo < hi) {
                    const mid = Math.ceil((lo + hi) / 2);
                    if (fitsUpTo(mid)) lo = mid;
                    else hi = mid - 1;
                }
            }
            starts.push(lo);
            from = lo;
        }
        return starts;
    };

    // A last part of a line or two is a sad page: a little smaller type may spare it.
    let starts = cutAt(minSize);
    if (starts.length > 1 && all.length - starts[starts.length - 1] <= 2) {
        const tighter = cutAt(minSize - 1.5);
        if (tighter.length < starts.length) starts = tighter;
    }

    const lengths = all.map((s) => s.text.replace(/<[^>]+>/g, '').length);
    const total = lengths.reduce((a, b) => a + b, 0) || 1;
    const at = starts.map((start) => lengths.slice(0, start).reduce((a, b) => a + b, 0) / total);
    return { starts, at };
}

defineExpose({ measure });

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

// Another part of the same page, or new cuts.
watch(
    () => [props.split, partIndex.value, props.noFigure, props.plate],
    async () => {
        fitted.value = false;
        await nextTick();
        fit();
    },
);

watch(
    () => [props.page?.id, props.page?.content],
    async () => {
        fitted.value = false;
        videoReady.value = false;
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
        :class="[
            `bp--${side}`,
            {
                'bp--chapter': page?.chapter && readable && firstPart,
                'bp--locked': lockKind,
                'bp--end': isEnd,
                'is-fitted': fitted,
                'is-narrating': narrating,
            },
        ]"
    >
        <div class="bp__paper" />

        <!-- ── A full-page illustration, facing its words ───────────── -->
        <div v-if="readable && plate" class="bp__plate">
            <img :src="page!.image!" alt="" draggable="false" />
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
        </div>

        <!-- ── Readable page ───────────────────────────────────────── -->
        <div v-else-if="readable" ref="inner" class="bp__inner">
            <div ref="text" class="bp__text">
                <header v-if="page!.chapter && firstPart" class="bp__chapter">
                    <span v-if="chapterNumber" class="bp__chapter-no">Rozdział {{ chapterNumber }}</span>
                    <h2>{{ page!.chapter }}</h2>
                    <svg viewBox="0 0 120 12" aria-hidden="true">
                        <path d="M2 6 H48 M72 6 H118" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                        <path d="M60 1 l3 5 -3 5 -3 -5 Z" fill="currentColor" />
                    </svg>
                </header>
                <figure v-if="showFigure" ref="figure" class="bp__figure">
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
                <div ref="bodyEl" class="bp__body" v-html="body" />
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
                        Każda strona to <strong>{{ pricePerPage }} monet</strong> i zostaje Twoja na zawsze. Masz <strong>{{ coins }}</strong
                        >.
                    </p>
                    <div v-if="!still" class="bp__lock-actions">
                        <button
                            v-if="canAfford"
                            type="button"
                            class="btn btn--gold btn--sm"
                            data-sfx="none"
                            :disabled="unlocking"
                            @click="emit('unlock')"
                        >
                            <LoaderCircle v-if="unlocking" class="animate-spin" />
                            <CoinIcon v-else />
                            Odblokuj za {{ lockCost }}
                        </button>
                        <Link v-else :href="coinsHref" class="btn btn--gold btn--sm"><CoinIcon /> Zdobądź monety</Link>
                        <label class="bp__auto">
                            <input
                                type="checkbox"
                                :checked="autoUnlock"
                                @change="emit('update:autoUnlock', ($event.target as HTMLInputElement).checked)"
                            />
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

        <footer v-if="(page || isEnd) && !plate" class="bp__folio">
            <span v-if="!isEnd">{{ folioText }}</span>
            <span v-else>✦</span>
        </footer>

        <span v-if="narrating" class="bp__narration" aria-hidden="true"> <i /><i /><i /><i /> </span>
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
    background: radial-gradient(120% 90% at 50% 40%, #fffdf6 0%, #fbf3e1 70%, #f3e6c8 100%);
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

/* The whole (square) picture, centred above the words: a phone's page. */
.bp__text[data-media='square'] {
    display: flex;
    flex-direction: column;
}

.bp__text[data-media='square'] .bp__figure {
    display: block;
    order: -1;
    align-self: center;
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

.bp__body :deep(p:first-child),
.bp__body :deep(p.cont) {
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

/* ── Plate: the picture across the whole page ───────────────────────── */
.bp__plate {
    position: absolute;
    inset: 0;
    background: #2a2622;
}

.bp__plate img,
.bp__plate video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bp__plate video {
    opacity: 0;
    transition: opacity 0.8s;
}

.bp__plate video.is-ready {
    opacity: 1;
}

/* the paper's curve toward the spine, over the picture */
.bp--left .bp__plate::after,
.bp--right .bp__plate::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.bp--left .bp__plate::after {
    background: linear-gradient(90deg, transparent 82%, rgb(0 0 0 / 0.28));
}

.bp--right .bp__plate::after {
    background: linear-gradient(270deg, transparent 82%, rgb(0 0 0 / 0.28));
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
    background: repeating-linear-gradient(45deg, rgb(21 50 47 / 0.05) 0 2px, transparent 2px 14px), #efe3c6;
}

/* ── Picture on demand ─────────────────────────────────────────────── */

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
