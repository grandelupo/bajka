<script setup lang="ts">
import CoinIcon from '@/components/story/CoinIcon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Coins, Unlock } from 'lucide-vue-next';
import { computed } from 'vue';

interface Purchase {
    id: number;
    amount: number;
    price: number | string;
    status: string;
    created_at: string;
}

interface Transaction {
    id: number;
    coins_spent: number;
    created_at: string;
    book: { id: number; title: string } | null;
    page: { id: number; page_number: number } | null;
}

const props = defineProps<{ purchases: Purchase[]; transactions: Transaction[] }>();

const page = usePage<SharedData>();
const coins = computed(() => page.props.auth.user?.coins ?? 0);

// One timeline, newest first: coins in and coins out.
const entries = computed(() =>
    [
        ...props.purchases.map((p) => ({ kind: 'in' as const, id: `p${p.id}`, at: p.created_at, amount: p.amount, purchase: p })),
        ...props.transactions.map((t) => ({ kind: 'out' as const, id: `t${t.id}`, at: t.created_at, amount: t.coins_spent, tx: t })),
    ].sort((a, b) => +new Date(b.at) - +new Date(a.at)),
);

const fmt = new Intl.DateTimeFormat('pl-PL', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
const statusLabel: Record<string, string> = { completed: 'opłacone', pending: 'w toku', failed: 'nieudane' };
</script>

<template>
    <Head title="Historia monet" />

    <AppLayout>
        <section class="wrap history">
            <header class="history__head">
                <div>
                    <p class="eyebrow" v-reveal>Sakiewka</p>
                    <h1 class="h1" v-reveal="0.05">Historia monet</h1>
                </div>
                <div class="history__balance" v-reveal:pop="0.15">
                    <CoinIcon />
                    <span><strong>{{ coins }}</strong> monet</span>
                    <Link :href="route('coins.purchase')" class="btn btn--gold btn--sm" data-sfx="coin">Doładuj</Link>
                </div>
            </header>

            <ol v-if="entries.length" class="timeline card">
                <li v-for="(e, i) in entries" :key="e.id" :style="{ '--i': Math.min(i, 12) }">
                    <span class="timeline__dot" :class="e.kind === 'in' ? 'is-in' : 'is-out'">
                        <Coins v-if="e.kind === 'in'" />
                        <Unlock v-else />
                    </span>
                    <span class="timeline__what">
                        <template v-if="e.kind === 'in'">
                            Zakup {{ e.purchase.amount }} monet
                            <small>{{ Number(e.purchase.price).toFixed(2).replace('.', ',') }} zł · {{ statusLabel[e.purchase.status] ?? e.purchase.status }}</small>
                        </template>
                        <template v-else>
                            <Link v-if="e.tx.book && e.tx.page" :href="route('pages.show', { book: e.tx.book.id, page: e.tx.page.page_number })">
                                Strona {{ e.tx.page.page_number }}
                            </Link>
                            <span v-else>Strona</span>
                            <small>{{ e.tx.book?.title }}</small>
                        </template>
                    </span>
                    <time class="timeline__when">{{ fmt.format(new Date(e.at)) }}</time>
                    <span class="timeline__amount" :class="e.kind === 'in' ? 'is-in' : 'is-out'">{{ e.kind === 'in' ? '+' : '−' }}{{ e.amount }}</span>
                </li>
            </ol>

            <div v-else class="empty card">
                <CoinIcon class="empty__coin float" />
                <h2 class="h3">Tu jeszcze pusto</h2>
                <p>Gdy kupisz monety albo odblokujesz stronę, zobaczysz to tutaj.</p>
                <Link :href="route('coins.purchase')" class="link">Zobacz pakiety <ArrowRight class="h-4 w-4" /></Link>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.history {
    padding-block: 3rem var(--section);
    max-width: 58rem;
}

.history__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.history__balance {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0.6rem 0.6rem 0.9rem;
    border-radius: 999px;
    background: var(--paper);
    border: 1px solid var(--line);
    box-shadow: var(--shadow-soft);
    font-weight: 700;
}

.history__balance > svg {
    width: 2rem;
    height: 2rem;
}

.timeline {
    padding: 0.75rem 1.5rem;
}

.timeline li {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px dashed var(--line-strong);
    animation: row-in 0.6s var(--ease-out) backwards;
    animation-delay: calc(var(--i) * 40ms);
}

.timeline li:last-child {
    border-bottom: 0;
}

@keyframes row-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
}

.timeline__dot {
    display: grid;
    place-items: center;
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 50%;
}

.timeline__dot svg {
    width: 1.15rem;
}

.timeline__dot.is-in {
    background: #fdf0c9;
    color: #a0681a;
}

.timeline__dot.is-out {
    background: #dcebdf;
    color: var(--moss);
}

.timeline__what {
    display: grid;
    font-weight: 800;
}

.timeline__what a:hover {
    color: var(--moss);
    text-decoration: underline;
}

.timeline__what small {
    font-weight: 600;
    color: var(--ink-soft);
}

.timeline__when {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--mute);
}

.timeline__amount {
    min-width: 3.5rem;
    text-align: right;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.timeline__amount.is-in {
    color: var(--moss);
}

.timeline__amount.is-out {
    color: #b5441f;
}

@media (max-width: 640px) {
    .timeline li {
        grid-template-columns: auto 1fr auto;
    }
    .timeline__when {
        display: none;
    }
}

.empty {
    display: grid;
    justify-items: center;
    gap: 0.8rem;
    padding: 3rem 1.5rem;
    text-align: center;
    color: var(--ink-soft);
}

.empty__coin {
    width: 4rem;
}
</style>
