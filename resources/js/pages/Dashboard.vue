<script setup lang="ts">
import BookCover from '@/components/story/BookCover.vue';
import CoinIcon from '@/components/story/CoinIcon.vue';
import Dragon from '@/components/story/Dragon.vue';
import NightSky from '@/components/story/NightSky.vue';
import SplitText from '@/components/story/SplitText.vue';
import Wave from '@/components/story/Wave.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BookSummary, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, BookOpenCheck, CalendarHeart, Coins, Library, Unlock } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Transaction {
    id: number;
    coins_spent: number;
    created_at: string;
    book: { id: number; title: string } | null;
    page: { id: number; page_number: number } | null;
}

interface CoinPurchase {
    id: number;
    amount: number;
    price: number | string;
    status: string;
    created_at: string;
}

const props = defineProps<{
    books: BookSummary[];
    recentTransactions: Transaction[];
    recentCoinPurchases: CoinPurchase[];
    userCoins: number;
    stats: { pages_unlocked: number; coins_spent: number; member_since: string };
}>();

const page = usePage<SharedData>();
const firstName = computed(() => (page.props.auth.user?.name ?? '').split(' ')[0]);

const hour = new Date().getHours();
const greeting = hour < 5 ? 'Dobranoc' : hour < 12 ? 'Dzień dobry' : hour < 18 ? 'Cześć' : 'Dobry wieczór';
const isNight = hour >= 19 || hour < 6;

// Where the reader left each book (kept in the browser by the reader).
const progress = ref<Record<number, number>>({});
onMounted(() => {
    for (const book of props.books) {
        try {
            const n = Number(localStorage.getItem(`bajka:progress:${book.id}`));
            if (n > 0) progress.value[book.id] = n;
        } catch {
            /* no storage, no bookmark */
        }
    }
});

const featured = computed(() => props.books[0] ?? null);
const featuredPage = computed(() => (featured.value ? (progress.value[featured.value.id] ?? 1) : 1));
const featuredPercent = computed(() => (featured.value ? Math.round((featuredPage.value / featured.value.total_pages) * 100) : 0));
const featuredChapter = computed(() => {
    if (!featured.value) return null;
    return [...featured.value.chapters].reverse().find((c) => c.page <= featuredPage.value) ?? null;
});
const pagesForCoins = computed(() => Math.floor(props.userCoins / (featured.value?.price_per_page || 5)));

const coverColors = ['#7a3b26', '#15322f', '#3d6fb6', '#9c2f58'];

const dateFmt = new Intl.DateTimeFormat('pl-PL', { day: 'numeric', month: 'short', year: 'numeric' });
const fmt = (d: string) => dateFmt.format(new Date(d));
const memberSince = computed(() => new Intl.DateTimeFormat('pl-PL', { month: 'long', year: 'numeric' }).format(new Date(props.stats.member_since)));

const statusLabel: Record<string, string> = { completed: 'opłacone', pending: 'w toku', failed: 'nieudane' };
</script>

<template>
    <Head title="Moja półka" />

    <AppLayout tone="night">
        <!-- ── Greeting ─────────────────────────────────────────────────── -->
        <section class="greet night">
            <NightSky :stars="50" :moon="isNight" :seed="13" />
            <div class="wrap greet__inner">
                <div class="greet__text">
                    <p class="eyebrow" v-reveal>Moja półka</p>
                    <h1 class="h1" v-reveal:words>
                        <SplitText :text="`${greeting},`" /> <em class="italic-wonk greet__name"><SplitText :text="`${firstName}!`" :offset="2" /></em>
                    </h1>
                    <p class="lead" v-reveal="0.2">Gotowy na kolejny rozdział? Smok już przeciera oczy.</p>
                </div>

                <div class="wallet" v-reveal:pop="0.3">
                    <div class="wallet__coin spin-y"><CoinIcon /></div>
                    <div class="wallet__body">
                        <span class="wallet__label">Twoja sakiewka</span>
                        <strong class="wallet__amount">{{ userCoins }} <small>monet</small></strong>
                        <span class="wallet__hint">wystarczy na {{ pagesForCoins }} {{ pagesForCoins === 1 ? 'stronę' : 'stron' }}</span>
                    </div>
                    <Link :href="route('coins.purchase')" class="btn btn--gold btn--sm" data-sfx="coin">Doładuj</Link>
                </div>
            </div>
        </section>
        <Wave color="var(--cream)" />

        <!-- ── Continue reading ─────────────────────────────────────────── -->
        <section v-if="featured" class="wrap continue">
            <Link :href="route('pages.show', { book: featured.id, page: featuredPage })" class="continue__card card" data-sfx="bookOpen" v-reveal>
                <div class="continue__cover">
                    <BookCover :title="featured.title" :image="featured.cover" />
                </div>
                <div class="continue__body">
                    <span class="chip chip--moss"><BookOpen class="h-4 w-4" /> {{ featuredPage > 1 ? 'Czytasz teraz' : 'Zacznij czytać' }}</span>
                    <h2 class="h2">{{ featured.title }}</h2>
                    <p v-if="featuredChapter" class="continue__chapter">
                        Rozdział „{{ featuredChapter.title.replace(/[.…]+$/, '') }}” · strona {{ featuredPage }}
                    </p>
                    <div class="meter" :aria-label="`Przeczytano ${featuredPercent}%`">
                        <span :style="{ width: `${featuredPercent}%` }" />
                        <i :style="{ left: `${featuredPercent}%` }"><Dragon :interactive="false" /></i>
                    </div>
                    <div class="continue__foot">
                        <span>{{ featuredPercent }}% przeczytane · {{ featured.total_pages }} stron</span>
                        <span class="btn btn--sm">{{ featuredPage > 1 ? `Czytaj dalej od str. ${featuredPage}` : 'Otwórz książkę' }} <ArrowRight /></span>
                    </div>
                </div>
            </Link>
        </section>

        <!-- ── Stats ────────────────────────────────────────────────────── -->
        <section class="wrap stats">
            <div class="stat card" v-reveal="0">
                <span class="stat__icon" style="--c: var(--moss)"><Unlock /></span>
                <strong>{{ stats.pages_unlocked }}</strong>
                <span>odblokowanych stron</span>
            </div>
            <div class="stat card" v-reveal="0.08">
                <span class="stat__icon" style="--c: var(--gold)"><Coins /></span>
                <strong>{{ stats.coins_spent }}</strong>
                <span>wydanych monet</span>
            </div>
            <div class="stat card" v-reveal="0.16">
                <span class="stat__icon" style="--c: var(--sky)"><BookOpenCheck /></span>
                <strong>{{ featured?.chapters.length ?? 0 }}</strong>
                <span>rozdziałów do odkrycia</span>
            </div>
            <div class="stat card" v-reveal="0.24">
                <span class="stat__icon" style="--c: var(--rose)"><CalendarHeart /></span>
                <strong class="stat__small">{{ memberSince }}</strong>
                <span>czytasz z nami od</span>
            </div>
        </section>

        <!-- ── Shelf ────────────────────────────────────────────────────── -->
        <section class="wrap shelf-wrap">
            <header class="shelf-head">
                <h2 class="h2" v-reveal><Library class="inline h-8 w-8 text-[var(--moss)]" /> Biblioteczka</h2>
                <Link :href="route('books.index')" class="link">Wszystkie książki <ArrowRight class="h-4 w-4" /></Link>
            </header>
            <div class="shelf">
                <Link
                    v-for="(book, i) in books"
                    :key="book.id"
                    :href="route('pages.show', { book: book.id, page: progress[book.id] ?? 1 })"
                    class="shelf__book"
                    data-sfx="bookOpen"
                    v-reveal="i * 0.08"
                >
                    <BookCover :title="book.title" :image="book.cover" :color="coverColors[i % coverColors.length]" />
                    <span class="shelf__meta">{{ book.total_pages }} stron · {{ book.chapters.length }} rozdziałów</span>
                </Link>
                <div class="shelf__soon" v-reveal="0.2">
                    <span class="float">✦</span>
                    <p>Kolejne bajki już się piszą…</p>
                </div>
            </div>
            <div class="shelf__plank" />
        </section>

        <!-- ── Activity ─────────────────────────────────────────────────── -->
        <section class="wrap activity">
            <div class="activity__col card" v-reveal>
                <h3 class="h3">Ostatnio odblokowane</h3>
                <ul v-if="recentTransactions.length" class="feed">
                    <li v-for="t in recentTransactions" :key="t.id">
                        <span class="feed__dot" style="--c: var(--moss)"><Unlock /></span>
                        <span class="feed__what">
                            <Link v-if="t.book && t.page" :href="route('pages.show', { book: t.book.id, page: t.page.page_number })">
                                Strona {{ t.page.page_number }}
                            </Link>
                            <small>{{ t.book?.title }} · {{ fmt(t.created_at) }}</small>
                        </span>
                        <span class="feed__amount">−{{ t.coins_spent }}</span>
                    </li>
                </ul>
                <p v-else class="empty">Jeszcze nic. Pierwsze {{ featured?.registered_free_pages ?? 11 }} stron czytasz za darmo!</p>
            </div>

            <div class="activity__col card" v-reveal="0.1">
                <h3 class="h3">Zakupy monet</h3>
                <ul v-if="recentCoinPurchases.length" class="feed">
                    <li v-for="p in recentCoinPurchases" :key="p.id">
                        <span class="feed__dot" style="--c: var(--gold)"><Coins /></span>
                        <span class="feed__what">
                            {{ p.amount }} monet
                            <small>{{ Number(p.price).toFixed(2).replace('.', ',') }} zł · {{ fmt(p.created_at) }} · {{ statusLabel[p.status] ?? p.status }}</small>
                        </span>
                        <span class="feed__amount feed__amount--plus">+{{ p.amount }}</span>
                    </li>
                </ul>
                <p v-else class="empty">
                    Sakiewka czeka na pierwsze monety.
                    <Link :href="route('coins.purchase')" class="link">Zobacz pakiety <ArrowRight class="h-4 w-4" /></Link>
                </p>
                <Link v-if="recentCoinPurchases.length || recentTransactions.length" :href="route('coins.history')" class="link mt-4">
                    Cała historia <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.greet {
    position: relative;
    overflow: hidden;
    padding: calc(var(--header-h) + 3rem) 0 5rem;
}

.greet__inner {
    position: relative;
    display: grid;
    gap: 2.5rem;
    align-items: end;
}

@media (min-width: 900px) {
    .greet__inner {
        grid-template-columns: 1fr auto;
    }
}

.greet__text {
    display: grid;
    gap: 1rem;
}

.greet__name {
    color: var(--gold);
}

.wallet {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.25rem 1.5rem 1.25rem 1.25rem;
    border-radius: 1.75rem;
    background: rgb(255 255 255 / 0.07);
    border: 1px solid var(--line-light);
    backdrop-filter: blur(8px);
}

.wallet__coin {
    width: 4.25rem;
    height: 4.25rem;
    filter: drop-shadow(0 8px 18px rgb(242 184 75 / 0.45));
}

.spin-y {
    animation: spin-y 5s var(--ease-in-out) infinite;
}

@keyframes spin-y {
    0%,
    70% {
        transform: rotateY(0);
    }
    85% {
        transform: rotateY(180deg) translateY(-6px);
    }
    100% {
        transform: rotateY(360deg);
    }
}

.wallet__body {
    display: grid;
    line-height: 1.2;
}

.wallet__label {
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--gold);
}

.wallet__amount {
    font-family: var(--font-display);
    font-size: 2.4rem;
    font-weight: 600;
}

.wallet__amount small {
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.7;
}

.wallet__hint {
    font-size: 0.85rem;
    color: rgb(251 244 230 / 0.65);
}

/* ── Continue ───────────────────────────────────────────────────────── */
.continue {
    position: relative;
    z-index: 3;
    margin-top: -2.5rem;
}

.continue__card {
    display: grid;
    gap: 2rem;
    padding: clamp(1.25rem, 3vw, 2.25rem);
    align-items: center;
    transition:
        transform 0.5s var(--ease-bounce),
        box-shadow 0.3s;
}

.continue__card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lift);
}

@media (min-width: 760px) {
    .continue__card {
        grid-template-columns: 11rem 1fr;
    }
}

.continue__cover {
    width: 11rem;
    margin-inline: auto;
}

.continue__body {
    display: grid;
    gap: 0.8rem;
    justify-items: start;
}

.continue__chapter {
    font-family: var(--font-display);
    font-style: italic;
    color: var(--ink-soft);
}

.meter {
    position: relative;
    width: 100%;
    height: 0.75rem;
    margin-block: 1.25rem 0.4rem;
    border-radius: 999px;
    background: var(--sand);
}

.meter span {
    position: absolute;
    inset: 0 auto 0 0;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--moss), var(--gold));
    transition: width 1.2s var(--ease-out);
}

.meter i {
    position: absolute;
    top: 50%;
    width: 3.2rem;
    translate: -50% -78%;
    transition: left 1.2s var(--ease-out);
}

.continue__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    font-weight: 700;
    color: var(--ink-soft);
}

/* ── Stats ──────────────────────────────────────────────────────────── */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.stat {
    display: grid;
    gap: 0.2rem;
    padding: 1.25rem 1.4rem;
    border-radius: 1.5rem;
}

.stat__icon {
    display: grid;
    place-items: center;
    width: 2.6rem;
    height: 2.6rem;
    margin-bottom: 0.5rem;
    border-radius: 0.9rem;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 16%, transparent);
}

.stat__icon svg {
    width: 1.3rem;
    height: 1.3rem;
}

.stat strong {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 600;
    line-height: 1.1;
}

.stat strong.stat__small {
    font-size: 1.35rem;
}

.stat > span:last-child {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--ink-soft);
}

/* ── Shelf ──────────────────────────────────────────────────────────── */
.shelf-wrap {
    margin-top: 4.5rem;
}

.shelf-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
}

.shelf {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(9.5rem, 11rem));
    gap: 2.5rem;
    align-items: end;
    padding: 0 1.5rem;
}

.shelf__book {
    position: relative;
    display: block;
    transition: transform 0.5s var(--ease-bounce);
}

.shelf__book:hover {
    transform: translateY(-10px) rotate(-2deg);
}

/* Lift the book, not its label */
.shelf__book:hover .shelf__meta {
    transform: translateY(10px) rotate(2deg);
}

.shelf__meta {
    /* A little label pinned under the plank, not squashed onto it */
    position: absolute;
    left: 50%;
    top: calc(100% + 1.5rem);
    translate: -50% 0;
    white-space: nowrap;
    padding: 0.2rem 0.7rem;
    border-radius: 999px;
    background: var(--sand);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--ink-soft);
    text-align: center;
}

.shelf__soon {
    display: grid;
    place-items: center;
    align-content: center;
    gap: 0.5rem;
    aspect-ratio: 3 / 4;
    padding: 1rem;
    border: 2px dashed var(--line-strong);
    border-radius: 8px;
    text-align: center;
    font-family: var(--font-display);
    font-style: italic;
    color: var(--ink-soft);
}

.shelf__soon span {
    font-size: 2rem;
    color: var(--gold);
}

.shelf__plank {
    height: 1.1rem;
    margin-top: -0.3rem;
    margin-bottom: 3rem;
    border-radius: 6px;
    background: linear-gradient(180deg, #b07a4a, #8a5a33);
    box-shadow:
        0 14px 22px -10px rgb(60 30 10 / 0.5),
        inset 0 2px 0 rgb(255 255 255 / 0.25);
}

/* ── Activity ───────────────────────────────────────────────────────── */
.activity {
    display: grid;
    gap: 1.5rem;
    margin-block: 4.5rem var(--section);
}

@media (min-width: 860px) {
    .activity {
        grid-template-columns: 1fr 1fr;
    }
}

.activity__col {
    padding: 1.75rem;
    display: grid;
    align-content: start;
    justify-items: start;
    gap: 1rem;
}

.feed {
    width: 100%;
    display: grid;
}

.feed li {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 0.75rem 0;
    border-bottom: 1px dashed var(--line-strong);
}

.feed li:last-child {
    border-bottom: 0;
}

.feed__dot {
    display: grid;
    place-items: center;
    width: 2.3rem;
    height: 2.3rem;
    flex: none;
    border-radius: 50%;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 15%, transparent);
}

.feed__dot svg {
    width: 1.05rem;
    height: 1.05rem;
}

.feed__what {
    flex: 1;
    display: grid;
    font-weight: 800;
}

.feed__what a:hover {
    color: var(--moss);
    text-decoration: underline;
}

.feed__what small {
    font-weight: 600;
    color: var(--ink-soft);
}

.feed__amount {
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    color: #b5441f;
}

.feed__amount--plus {
    color: var(--moss);
}

.empty {
    display: grid;
    gap: 0.75rem;
    justify-items: start;
    color: var(--ink-soft);
}
</style>
