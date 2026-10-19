<script setup lang="ts">
import CoinIcon from '@/components/story/CoinIcon.vue';
import NightSky from '@/components/story/NightSky.vue';
import SplitText from '@/components/story/SplitText.vue';
import Wave from '@/components/story/Wave.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { postJson } from '@/lib/http';
import { play } from '@/lib/sound';
import { burstFrom } from '@/lib/sparkle';
import type { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js';
import { ArrowLeft, Check, CircleAlert, Infinity as InfinityIcon, LoaderCircle, ShieldCheck, Sparkles } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

interface Package {
    id: string;
    name: string;
    coins: number;
    price: number;
}

const props = defineProps<{
    packages: Package[];
    stripeKey: string | null;
    returnTo?: string | null;
    pricePerPage: number;
}>();

const page = usePage<SharedData>();
const coins = computed(() => page.props.auth.user?.coins ?? 0);

const badges: Record<string, string> = { standard: 'Najczęściej wybierany', ultimate: 'Najlepsza cena' };
const cheapest = computed(() => Math.max(...props.packages.map((p) => p.price / p.coins)));
const pages = (p: Package) => Math.floor(p.coins / props.pricePerPage);
const perPage = (p: Package) => ((p.price / p.coins) * props.pricePerPage).toFixed(2).replace('.', ',');
const saving = (p: Package) => Math.round((1 - p.price / p.coins / cheapest.value) * 100);
const zl = (n: number) => (Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ','));

const selected = ref<Package | null>(null);
const preparing = ref(false);
const processing = ref(false);
const error = ref<string | null>(null);
const paymentPanel = ref<HTMLElement | null>(null);

let stripe: Stripe | null = null;
let elements: StripeElements | null = null;

onMounted(async () => {
    if (props.stripeKey) stripe = await loadStripe(props.stripeKey);
});

async function choose(pkg: Package, event: MouseEvent) {
    if (preparing.value || processing.value) return;
    selected.value = pkg;
    error.value = null;
    void play('coin');
    burstFrom(event.currentTarget as Element, { count: 12, spread: 90 });

    if (!stripe) {
        error.value = 'Płatności są chwilowo niedostępne. Spróbuj ponownie za moment.';
        return;
    }

    preparing.value = true;
    await nextTick();
    paymentPanel.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const { ok, data } = await postJson<{ clientSecret?: string; message?: string }>(route('transactions.create-payment-intent'), { package_id: pkg.id });
    if (!ok || !data.clientSecret) {
        preparing.value = false;
        error.value = data.message ?? 'Nie udało się rozpocząć płatności.';
        return;
    }

    elements = stripe.elements({
        clientSecret: data.clientSecret,
        locale: 'pl',
        fonts: [{ cssSrc: 'https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap' }],
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#15322f',
                colorBackground: '#ffffff',
                colorText: '#1d2a2c',
                colorDanger: '#c2410c',
                fontFamily: 'Nunito, system-ui, sans-serif',
                borderRadius: '14px',
                spacingUnit: '5px',
            },
            rules: {
                '.Input': { border: '2px solid rgba(29,42,44,.12)', boxShadow: 'none' },
                '.Input:focus': { border: '2px solid #f2b84b', boxShadow: '0 0 0 4px rgba(242,184,75,.25)' },
                '.Tab': { border: '2px solid rgba(29,42,44,.12)' },
                '.Tab--selected': { border: '2px solid #15322f' },
            },
        },
    });

    const container = document.getElementById('payment-element');
    if (container) container.innerHTML = '';
    elements.create('payment', { layout: 'tabs' }).mount('#payment-element');
    preparing.value = false;
}

async function pay() {
    if (!stripe || !elements || !selected.value) return;
    processing.value = true;
    error.value = null;

    const { error: failure, paymentIntent } = await stripe.confirmPayment({
        elements,
        confirmParams: { return_url: `${window.location.origin}${route('coins.purchase.confirm', undefined, false)}` },
        redirect: 'if_required',
    });

    if (failure) {
        error.value = failure.message ?? 'Płatność nie powiodła się.';
        processing.value = false;
        return;
    }

    if (paymentIntent) router.visit(route('coins.purchase.confirm', { payment_intent: paymentIntent.id }));
}
</script>

<template>
    <Head title="Monety" />

    <AppLayout tone="night">
        <section class="shop-hero night">
            <NightSky :stars="50" :seed="17" :moon="false" />
            <div class="wrap shop-hero__inner">
                <Link v-if="returnTo" :href="returnTo" class="shop-hero__back" v-reveal><ArrowLeft class="h-4 w-4" /> Wróć do czytania</Link>
                <p class="eyebrow" v-reveal>Sakiewka</p>
                <h1 class="h1" v-reveal:words><SplitText text="Monety na" /> <em class="italic-wonk"><SplitText text="kolejne strony" :offset="2" /></em></h1>
                <p class="lead" v-reveal="0.2">
                    Jedna strona to {{ pricePerPage }} monet. Raz odblokowana zostaje Twoja na zawsze — możesz do niej wracać ile chcesz.
                </p>
                <div class="shop-hero__balance" v-reveal:pop="0.3"><CoinIcon /> Masz teraz <strong>{{ coins }}</strong> monet</div>
            </div>
            <div class="shop-hero__pile" aria-hidden="true">
                <CoinIcon v-for="i in 7" :key="i" class="pile-coin" :style="{ '--i': i }" />
            </div>
        </section>
        <Wave color="var(--cream)" />

        <section class="wrap shop">
            <div class="packs">
                <button
                    v-for="(pkg, i) in packages"
                    :key="pkg.id"
                    type="button"
                    class="pack card"
                    :class="{ 'is-selected': selected?.id === pkg.id, 'is-featured': pkg.id === 'standard' }"
                    data-sfx="none"
                    v-reveal="i * 0.08"
                    @click="choose(pkg, $event)"
                >
                    <span v-if="badges[pkg.id]" class="pack__badge">{{ badges[pkg.id] }}</span>
                    <span class="pack__stack" :data-size="i">
                        <CoinIcon v-for="n in i + 2" :key="n" :style="{ '--n': n }" />
                    </span>
                    <span class="pack__name">{{ pkg.name }}</span>
                    <span class="pack__coins">{{ pkg.coins }} <small>monet</small></span>
                    <span class="pack__pages">≈ {{ pages(pkg) }} stron · {{ perPage(pkg) }} zł / str.</span>
                    <span class="pack__price">{{ zl(pkg.price) }} zł</span>
                    <span v-if="saving(pkg) > 0" class="chip chip--moss">taniej o {{ saving(pkg) }}%</span>
                    <span class="pack__check"><Check /></span>
                </button>
            </div>

            <Transition name="panel">
                <div v-if="selected" ref="paymentPanel" class="pay card">
                    <header class="pay__head">
                        <div>
                            <p class="eyebrow">Płatność</p>
                            <h2 class="h3">{{ selected.name }} — {{ selected.coins }} monet</h2>
                        </div>
                        <strong class="pay__total">{{ zl(selected.price) }} zł</strong>
                    </header>

                    <div v-if="preparing" class="pay__loading"><LoaderCircle class="animate-spin" /> Przygotowujemy bezpieczną płatność…</div>
                    <div id="payment-element" class="pay__element" :class="{ 'is-hidden': preparing }" />

                    <p v-if="error" class="pay__error"><CircleAlert class="h-5 w-5 flex-none" /> {{ error }}</p>

                    <button type="button" class="btn btn--gold btn--block btn--shine" :disabled="preparing || processing || !!error && !stripe" data-sfx="none" @click="pay">
                        <LoaderCircle v-if="processing" class="animate-spin" />
                        <Sparkles v-else />
                        Zapłać {{ zl(selected.price) }} zł
                    </button>
                    <p v-if="returnTo" class="pay__note">Po zakupie wrócisz prosto do swojej strony.</p>
                </div>
            </Transition>

            <ul class="perks">
                <li v-reveal><ShieldCheck /> <span><strong>Bezpieczne płatności</strong> obsługuje Stripe — nie widzimy danych Twojej karty.</span></li>
                <li v-reveal="0.08"><InfinityIcon /> <span><strong>Monety nie wygasają.</strong> Wydajesz je, kiedy chcesz.</span></li>
                <li v-reveal="0.16"><Check /> <span><strong>Strona zostaje Twoja</strong> — za odblokowaną stronę nie płacisz drugi raz.</span></li>
            </ul>
        </section>
    </AppLayout>
</template>

<style scoped>
.shop-hero {
    position: relative;
    overflow: hidden;
    padding: calc(var(--header-h) + 3rem) 0 6rem;
}

.shop-hero__inner {
    position: relative;
    z-index: 2;
    display: grid;
    gap: 1.1rem;
    justify-items: start;
    max-width: 52rem;
    margin-left: 0;
}

@media (min-width: 1360px) {
    .shop-hero__inner {
        margin-left: calc((100vw - var(--max)) / 2);
    }
}

.shop-hero__inner em {
    color: var(--gold);
}

.shop-hero__back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 800;
    color: var(--gold);
}

.shop-hero__balance {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 1.1rem 0.55rem 0.6rem;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.08);
    font-weight: 700;
}

.shop-hero__balance svg {
    width: 1.8rem;
    height: 1.8rem;
}

.shop-hero__pile {
    position: absolute;
    right: 6%;
    bottom: 3.5rem;
    width: 16rem;
    height: 14rem;
    display: none;
}

@media (min-width: 1000px) {
    .shop-hero__pile {
        display: block;
    }
}

.pile-coin {
    position: absolute;
    width: 5rem;
    left: calc(var(--i) * 1.9rem);
    bottom: calc(mod(var(--i), 3) * 2.2rem);
    animation: coin-hop 3.4s var(--ease-in-out) infinite;
    animation-delay: calc(var(--i) * -0.45s);
    filter: drop-shadow(0 10px 16px rgb(0 0 0 / 0.35));
}

@keyframes coin-hop {
    0%,
    100% {
        transform: translateY(0) rotate(-8deg);
    }
    50% {
        transform: translateY(-16px) rotate(8deg);
    }
}

.shop {
    position: relative;
    z-index: 3;
    margin-top: -3rem;
    padding-bottom: var(--section);
}

.packs {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
}

.pack {
    position: relative;
    display: grid;
    justify-items: center;
    gap: 0.35rem;
    padding: 2rem 1.25rem 1.6rem;
    text-align: center;
    cursor: pointer;
    border: 3px solid transparent;
    transition:
        transform 0.5s var(--ease-bounce),
        box-shadow 0.3s,
        border-color 0.2s;
}

.pack:hover {
    transform: translateY(-8px) rotate(-1deg);
    box-shadow: var(--shadow-lift);
}

.pack.is-featured {
    background: #fff6dc;
}

.pack.is-selected {
    border-color: var(--night);
    transform: translateY(-8px);
    box-shadow: var(--shadow-lift);
}

.pack__badge {
    position: absolute;
    top: -0.85rem;
    padding: 0.3rem 0.9rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: var(--ember);
    color: #fff;
    box-shadow: 0 6px 14px -6px rgb(181 68 31 / 0.6);
    rotate: -3deg;
}

.pack__stack {
    position: relative;
    width: 7rem;
    height: 5.5rem;
    margin-bottom: 0.6rem;
}

.pack__stack svg {
    position: absolute;
    left: 50%;
    bottom: calc((var(--n) - 1) * 0.6rem);
    width: 3.6rem;
    margin-left: calc(-1.8rem + (mod(var(--n), 2) * 2 - 1) * 0.35rem);
    transform: scaleY(0.55);
    transform-origin: bottom;
    filter: drop-shadow(0 2px 0 #a0681a);
    transition: transform 0.4s var(--ease-bounce);
    transition-delay: calc(var(--n) * 30ms);
}

.pack__stack svg:last-child {
    transform: none;
    bottom: calc((var(--n) - 1) * 0.6rem + 0.2rem);
}

.pack:hover .pack__stack svg {
    transform: scaleY(0.55) translateY(-10px);
}

.pack:hover .pack__stack svg:last-child {
    transform: translateY(-14px) rotate(14deg);
}

.pack__name {
    font-weight: 800;
    color: var(--ink-soft);
}

.pack__coins {
    font-family: var(--font-display);
    font-size: 2.6rem;
    font-weight: 600;
    line-height: 1.05;
}

.pack__coins small {
    font-size: 1rem;
    font-weight: 500;
    color: var(--ink-soft);
}

.pack__pages {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink-soft);
}

.pack__price {
    margin: 0.7rem 0 0.4rem;
    padding: 0.45rem 1.3rem;
    border-radius: 999px;
    font-weight: 900;
    font-size: 1.15rem;
    background: var(--night);
    color: var(--cream);
}

.pack__check {
    position: absolute;
    top: 0.9rem;
    right: 0.9rem;
    display: grid;
    place-items: center;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 50%;
    background: var(--night);
    color: var(--gold);
    transform: scale(0);
    transition: transform 0.4s var(--ease-bounce);
}

.pack__check svg {
    width: 1.1rem;
}

.pack.is-selected .pack__check {
    transform: scale(1);
}

/* ── Payment ───────────────────────────────────────────────────────── */
.pay {
    max-width: 36rem;
    margin: 2.5rem auto 0;
    padding: clamp(1.25rem, 4vw, 2.25rem);
    display: grid;
    gap: 1.25rem;
}

.pay__head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.pay__total {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 600;
}

.pay__loading {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1.5rem;
    border-radius: 1rem;
    background: var(--sand);
    font-weight: 700;
    color: var(--ink-soft);
}

.pay__element.is-hidden {
    display: none;
}

.pay__error {
    display: flex;
    gap: 0.6rem;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    background: #fde7dc;
    color: #9a3412;
    font-weight: 700;
}

.pay__note {
    text-align: center;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--ink-soft);
}

.panel-enter-active {
    transition:
        opacity 0.4s,
        transform 0.6s var(--ease-bounce);
}

.panel-enter-from {
    opacity: 0;
    transform: translateY(30px) scale(0.96);
}

.perks {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    margin-top: 3.5rem;
}

.perks li {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    padding: 1.1rem 1.25rem;
    border-radius: 1.25rem;
    background: var(--sand);
    line-height: 1.5;
    color: var(--ink-soft);
}

.perks svg {
    flex: none;
    width: 1.4rem;
    height: 1.4rem;
    color: var(--moss);
}

.perks strong {
    color: var(--ink);
}
</style>
