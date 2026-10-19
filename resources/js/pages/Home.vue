<script setup lang="ts">
import CoinIcon from '@/components/story/CoinIcon.vue';
import Dragon from '@/components/story/Dragon.vue';
import Fireflies from '@/components/story/Fireflies.vue';
import Landscape from '@/components/story/Landscape.vue';
import Logo from '@/components/story/Logo.vue';
import NightSky from '@/components/story/NightSky.vue';
import SplitText from '@/components/story/SplitText.vue';
import Wave from '@/components/story/Wave.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { burstFrom } from '@/lib/sparkle';
import { play, type SoundName } from '@/lib/sound';
import type { BookSummary, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Headphones, Sparkles } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ book: BookSummary | null }>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const bookId = computed(() => props.book?.id ?? page.props.config.books.advertised_book_id);
const readHref = computed(() => route('pages.show', { book: bookId.value, page: 1 }));

/* ── First visit: the book cover swings open ─────────────────────────── */
const showCurtain = ref(false);
try {
    showCurtain.value = !sessionStorage.getItem('bajka:opened');
    sessionStorage.setItem('bajka:opened', '1');
} catch {
    showCurtain.value = false;
}

/* ── Hero parallax: layers lean away from the pointer ────────────────── */
const hero = ref<HTMLElement | null>(null);
let frame = 0;
function onPointer(e: PointerEvent) {
    if (!hero.value || e.pointerType !== 'mouse') return;
    const x = (e.clientX / window.innerWidth) * 2 - 1;
    const y = (e.clientY / window.innerHeight) * 2 - 1;
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
        hero.value?.style.setProperty('--mx', x.toFixed(3));
        hero.value?.style.setProperty('--my', y.toFixed(3));
    });
}

/* Masked pictures drift a little slower than the page. */
let drifters: HTMLElement[] = [];
let scrollFrame = 0;
function onScroll() {
    cancelAnimationFrame(scrollFrame);
    scrollFrame = requestAnimationFrame(() => {
        const vh = window.innerHeight;
        hero.value?.style.setProperty('--scroll-y', String(Math.min(window.scrollY, vh)));
        for (const el of drifters) {
            const r = el.parentElement!.getBoundingClientRect();
            if (r.bottom < -vh / 2 || r.top > vh * 1.5) continue;
            const centre = r.top + r.height / 2 - vh / 2;
            el.style.transform = `translate3d(0, ${(-centre * Number(el.dataset.drift ?? 0.08)).toFixed(1)}px, 0)`;
        }
    });
}

onMounted(() => {
    drifters = Array.from(document.querySelectorAll<HTMLElement>('[data-drift]'));
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    cancelAnimationFrame(frame);
    cancelAnimationFrame(scrollFrame);
});

/* ── Characters say hello when poked ─────────────────────────────────── */
interface Hero {
    name: string;
    image?: string;
    role: string;
    text: string;
    says: string;
    sound: SoundName;
    tint: string;
}

const heroes: Hero[] = [
    {
        name: 'Olga',
        image: '/images/olga.webp',
        role: 'detektywka',
        text: 'Ma tęczowe włosy, lupę w kieszeni i oko do śladów, których nikt inny nie widzi.',
        says: 'Każdy ślad coś znaczy!',
        sound: 'sparkle',
        tint: 'var(--rose)',
    },
    {
        name: 'Igor',
        image: '/images/igor.webp',
        role: 'programista',
        text: 'Nosi sweter jak rycerska kolczuga. Zbuduje drona na każdą okazję — nawet na smoka.',
        says: 'Mam na to program!',
        sound: 'whoosh',
        tint: 'var(--sky)',
    },
    {
        name: 'Kukiełka',
        image: '/images/kukielka.webp',
        role: 'kotka',
        text: 'Biała i puszysta, cicha jak mgiełka. A kiedy trzeba — odważna jak lew.',
        says: 'Miauuu…',
        sound: 'meow',
        tint: 'var(--lilac)',
    },
    {
        name: 'Apetyt',
        image: '/images/apetyt.webp',
        role: 'pies z misją',
        text: 'Ma nos, który wywęszy każdą tajemnicę, i kamizelkę pełną przydatnych drobiazgów.',
        says: 'Hau! Hau!',
        sound: 'woof',
        tint: 'var(--ember)',
    },
];

const speaking = ref<string | null>(null);
let speakTimer: ReturnType<typeof setTimeout>;
function poke(hero: Hero, event: MouseEvent) {
    void play(hero.sound);
    burstFrom(event.currentTarget as Element, { count: 10, spread: 70 });
    speaking.value = hero.name;
    clearTimeout(speakTimer);
    speakTimer = setTimeout(() => (speaking.value = null), 2200);
}

function celebrate(event: MouseEvent) {
    burstFrom(event.currentTarget as Element, { count: 16, spread: 110 });
}

const chapters = computed(() => props.book?.chapters ?? []);
const gallery = computed(() => props.book?.illustrations ?? []);
const galleryA = computed(() => gallery.value.filter((_, i) => i % 2 === 0));
const galleryB = computed(() => gallery.value.filter((_, i) => i % 2 === 1));
const totalPages = computed(() => props.book?.total_pages ?? 0);
</script>

<template>
    <Head title="Interaktywna bajka do czytania i słuchania" />

    <div v-if="showCurtain" class="curtain" aria-hidden="true">
        <div class="curtain__panel curtain__panel--left" />
        <div class="curtain__panel curtain__panel--right" />
        <Logo class="curtain__logo" style="--logo-gutter: var(--night)" />
    </div>

    <AppLayout tone="night">
        <!-- ── Hero ─────────────────────────────────────────────────────── -->
        <section ref="hero" class="hero night" @pointermove="onPointer">
            <NightSky :stars="90" />
            <div class="hero__dragon">
                <Dragon flying />
            </div>
            <Landscape />
            <Fireflies :count="22" />

            <div class="hero__inner wrap">
                <p class="eyebrow hero__eyebrow" v-reveal>Interaktywna bajka · czytaj i słuchaj</p>
                <h1 class="display hero__title" v-reveal:words="0.15">
                    <SplitText text="To już są" />
                    <br />
                    <em class="italic-wonk hero__accent"><SplitText text="inne czasy" :offset="3" /></em>
                </h1>
                <p class="lead hero__lead" v-reveal="0.5">
                    O Oldze, Igorze, kotce Kukiełce, psie Apetycie — i o smoku ze starego fortu, który zgubił swój ogień.
                </p>
                <div class="hero__actions" v-reveal="0.7">
                    <Link :href="readHref" class="btn btn--gold btn--shine" data-sfx="bookOpen" @click="celebrate">
                        <BookOpen /> Zacznij czytać za darmo
                    </Link>
                    <a href="#bohaterowie" class="btn btn--ghost" data-sfx="whoosh">Poznaj bohaterów</a>
                </div>
            </div>

            <div class="hero__foot wrap">
                <span>{{ book?.free_pages ?? 6 }} stron bez logowania</span>
                <a href="#historia" class="hero__scroll" aria-label="Przewiń w dół"><i /></a>
                <span>{{ totalPages }} stron · {{ chapters.length }} rozdziałów</span>
            </div>
        </section>

        <Wave color="var(--cream)" />

        <!-- ── Intro ────────────────────────────────────────────────────── -->
        <section id="historia" class="section intro">
            <div class="wrap intro__grid">
                <div class="intro__text">
                    <p class="eyebrow" v-reveal>Dawno, dawno temu…</p>
                    <h2 class="h1" v-reveal:words>
                        <SplitText text="…a całkiem niedawno zdarzyło się coś" />
                        <span class="scribble">
                            <SplitText text="zupełnie nowego" :offset="5" />
                            <svg viewBox="0 0 200 20" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M2 14 C 40 4, 90 18, 130 8 S 190 10, 198 6" pathLength="1" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" />
                            </svg>
                        </span>
                    </h2>
                    <p class="lead" v-reveal="0.15">
                        W bajce brała kiedyś udział piękna królewna, groźny smok i dzielny szewczyk. Dziś w starych fortach mieszka smok, który
                        nie potrafi już ziać ogniem — a mała detektywka właśnie znalazła pod lupą bardzo dziwny ślad.
                    </p>
                    <p v-reveal="0.25">
                        <Link :href="readHref" class="link" data-sfx="bookOpen">Przeczytaj pierwszą stronę <ArrowRight class="h-4 w-4" /></Link>
                    </p>
                </div>

                <div class="intro__art">
                    <div class="intro__arch mask mask--arch" v-reveal:mask>
                        <div class="intro__drift" data-drift="0.07">
                            <img src="/images/fort-preview.webp" alt="Stary fort nocą, rozświetlony przez świetliki" loading="lazy" />
                        </div>
                    </div>
                    <div class="intro__circle mask mask--circle" v-reveal:mask="0.3">
                        <div><img src="/images/2-olga-kukielka.webp" alt="Olga z lupą i kotka Kukiełka" loading="lazy" /></div>
                    </div>
                    <svg class="intro__stamp spin" viewBox="0 0 200 200" aria-hidden="true">
                        <defs>
                            <path id="stamp" d="M100 100m-76 0a76 76 0 1 1 152 0a76 76 0 1 1-152 0" />
                        </defs>
                        <circle cx="100" cy="100" r="96" fill="var(--gold)" />
                        <text>
                            <textPath href="#stamp" textLength="470">CZYTAJ ✦ SŁUCHAJ ✦ ODKRYWAJ ✦ MARZ ✦</textPath>
                        </text>
                        <path d="M100 78 l6 14 15 2 -11 10 3 15 -13 -7 -13 7 3 -15 -11 -10 15 -2 Z" fill="var(--night)" />
                    </svg>
                    <Sparkles class="intro__spark float" />
                </div>
            </div>
        </section>

        <!-- ── Chapter marquee ──────────────────────────────────────────── -->
        <div v-if="chapters.length" class="band" aria-hidden="true">
            <div class="marquee">
                <div v-for="copy in 2" :key="copy" class="marquee__track">
                    <span v-for="c in chapters" :key="c.page" class="band__item">
                        <span class="h2 italic-wonk">{{ c.title.replace(/[.…]+$/, '') }}</span>
                        <svg viewBox="0 0 24 24" class="band__star"><path d="M12 1l2.6 8.4L23 12l-8.4 2.6L12 23l-2.6-8.4L1 12l8.4-2.6z" /></svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- ── What makes it alive ──────────────────────────────────────── -->
        <section class="section features grain">
            <div class="wrap">
                <header class="section-head">
                    <p class="eyebrow" v-reveal>Książka, która żyje</p>
                    <h2 class="h1" v-reveal:words><SplitText text="Przewracaj, słuchaj, zaglądaj między kartki" /></h2>
                </header>

                <div class="features__grid">
                    <article class="feature card card--lift" v-reveal="0">
                        <div class="feature__art feature__art--book">
                            <svg viewBox="0 0 160 110" aria-hidden="true">
                                <path d="M10 22 q35 -12 70 4 v78 q-35 -14 -70 -4 Z" fill="#fff" stroke="var(--night)" stroke-width="3" stroke-linejoin="round" />
                                <path d="M150 22 q-35 -12 -70 4 v78 q35 -14 70 -4 Z" fill="#fff" stroke="var(--night)" stroke-width="3" stroke-linejoin="round" />
                                <path class="feature__leaf" d="M80 26 q30 -14 62 -4 v78 q-32 -10 -62 4 Z" fill="var(--gold-soft)" stroke="var(--night)" stroke-width="3" stroke-linejoin="round" />
                                <path d="M22 40 q22 -6 46 2 M22 54 q22 -6 46 2 M22 68 q22 -6 46 2" stroke="var(--line-strong)" stroke-width="3" fill="none" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h3 class="h3">Przewracaj kartki</h3>
                        <p>Dwie strony naraz, jak w prawdziwej książce. Kartki szeleszczą, gdy je przewracasz — strzałkami, palcem albo myszką.</p>
                    </article>

                    <article class="feature card card--lift" v-reveal="0.12">
                        <div class="feature__art feature__art--listen">
                            <svg viewBox="0 0 160 110" aria-hidden="true">
                                <path d="M44 66 v-10 a36 36 0 0 1 72 0 v10" fill="none" stroke="var(--night)" stroke-width="7" stroke-linecap="round" />
                                <rect x="32" y="60" width="22" height="34" rx="9" fill="var(--rose)" stroke="var(--night)" stroke-width="3" />
                                <rect x="106" y="60" width="22" height="34" rx="9" fill="var(--rose)" stroke="var(--night)" stroke-width="3" />
                                <g class="feature__waves" stroke="var(--moss)" stroke-width="4" fill="none" stroke-linecap="round">
                                    <path d="M140 64 q8 13 0 26" />
                                    <path d="M150 56 q14 21 0 42" />
                                    <path d="M20 64 q-8 13 0 26" />
                                    <path d="M10 56 q-14 21 0 42" />
                                </g>
                            </svg>
                        </div>
                        <h3 class="h3">Posłuchaj lektora</h3>
                        <p>Każdą stronę można usłyszeć. Włącz „Czytaj mi”, a kartki same będą się przewracać, gdy lektor skończy.</p>
                    </article>

                    <article class="feature card card--lift" v-reveal="0.24">
                        <div class="feature__art feature__art--magic">
                            <svg viewBox="0 0 160 110" aria-hidden="true">
                                <rect x="34" y="14" width="92" height="80" rx="10" fill="#fff" stroke="var(--night)" stroke-width="3" />
                                <path d="M42 84 l24 -30 18 20 12 -12 22 22 Z" fill="var(--moss)" />
                                <circle cx="100" cy="36" r="9" fill="var(--gold)" />
                                <path class="twinkle" d="M24 20 l3 7 7 3 -7 3 -3 7 -3 -7 -7 -3 7 -3 Z" fill="var(--gold)" />
                                <path class="twinkle" style="animation-delay: -1.2s" d="M138 70 l3 7 7 3 -7 3 -3 7 -3 -7 -7 -3 7 -3 Z" fill="var(--rose)" />
                                <path class="twinkle" style="animation-delay: -2.2s" d="M140 12 l2 5 5 2 -5 2 -2 5 -2 -5 -5 -2 5 -2 Z" fill="var(--lilac)" />
                            </svg>
                        </div>
                        <h3 class="h3">Ilustracje ożywają</h3>
                        <p>Obrazki na stronach poruszają się jak w filmie, a przy kominku czyta się jeszcze przyjemniej — z prawdziwym trzaskiem drewna.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ── Characters ───────────────────────────────────────────────── -->
        <section id="bohaterowie" class="section cast">
            <div class="wrap">
                <header class="section-head section-head--center">
                    <p class="eyebrow" v-reveal>Poznaj bohaterów</p>
                    <h2 class="h1" v-reveal:words><SplitText text="Paczka przyjaciół" /> <em class="italic-wonk"><SplitText text="i jeden smok" :offset="2" /></em></h2>
                    <p class="lead" v-reveal="0.15">Kliknij kogoś — może się przywita.</p>
                </header>

                <div class="cast__grid">
                    <button
                        v-for="(h, i) in heroes"
                        :key="h.name"
                        type="button"
                        class="hero-card"
                        :class="{ 'is-speaking': speaking === h.name }"
                        :style="{ '--tint': h.tint, '--tilt': `${i % 2 ? 2 : -2}deg` }"
                        data-sfx="none"
                        v-reveal="i * 0.08"
                        @click="poke(h, $event)"
                    >
                        <span class="hero-card__bubble" aria-live="polite">{{ speaking === h.name ? h.says : '' }}</span>
                        <span class="hero-card__photo mask" :class="i % 2 ? 'mask--blob-2' : 'mask--blob'">
                            <img :src="h.image" :alt="h.name" loading="lazy" />
                        </span>
                        <span class="hero-card__role">{{ h.role }}</span>
                        <span class="hero-card__name h3">{{ h.name }}</span>
                        <span class="hero-card__text">{{ h.text }}</span>
                    </button>

                    <div class="hero-card hero-card--dragon night" v-reveal="0.32">
                        <NightSky :stars="24" :moon="false" :seed="11" />
                        <div class="hero-card__dragon"><Dragon /></div>
                        <span class="hero-card__role">mieszkaniec fortu</span>
                        <span class="hero-card__name h3">Smok</span>
                        <span class="hero-card__text">Nie jadł od stu lat i zgubił swój ogień. Pogłaszcz go — może spróbuje zionąć?</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Gallery ──────────────────────────────────────────────────── -->
        <section v-if="gallery.length > 3" class="gallery">
            <div class="wrap section-head section-head--center">
                <p class="eyebrow" v-reveal>Zajrzyj między kartki</p>
                <h2 class="h1" v-reveal:words><SplitText text="Każda strona to mały obraz" /></h2>
            </div>
            <div class="marquee gallery__row" style="--marquee-speed: 70s">
                <div v-for="copy in 2" :key="copy" class="marquee__track">
                    <Link v-for="img in galleryA" :key="img.image" :href="route('pages.show', { book: bookId, page: img.page })" class="gallery__tile">
                        <img :src="img.image" alt="" loading="lazy" />
                        <span>str. {{ img.page }}</span>
                    </Link>
                </div>
            </div>
            <div class="marquee marquee--reverse gallery__row" style="--marquee-speed: 80s">
                <div v-for="copy in 2" :key="copy" class="marquee__track">
                    <Link v-for="img in galleryB" :key="img.image" :href="route('pages.show', { book: bookId, page: img.page })" class="gallery__tile">
                        <img :src="img.image" alt="" loading="lazy" />
                        <span>str. {{ img.page }}</span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ── How reading works ────────────────────────────────────────── -->
        <section class="section path">
            <div class="wrap">
                <header class="section-head section-head--center">
                    <p class="eyebrow" v-reveal>Jak czytać</p>
                    <h2 class="h1" v-reveal:words><SplitText text="Zacznij od razu," /> <em class="italic-wonk"><SplitText text="bez logowania" :offset="3" /></em></h2>
                </header>

                <div class="path__stops">
                    <svg class="path__line" viewBox="0 0 1000 120" preserveAspectRatio="none" aria-hidden="true" v-reveal:draw>
                        <path d="M20 60 C 180 -10, 320 130, 500 60 S 820 -10, 980 60" pathLength="1" fill="none" stroke="var(--gold)" stroke-width="4" stroke-dasharray="1" stroke-linecap="round" />
                    </svg>

                    <div class="stop" v-reveal="0.1">
                        <span class="stop__badge" style="--c: var(--moss)">1–{{ book?.free_pages ?? 6 }}</span>
                        <h3 class="h3">Od razu</h3>
                        <p>Pierwsze strony czytasz bez zakładania konta. Po prostu otwórz książkę.</p>
                    </div>
                    <div class="stop" v-reveal="0.3">
                        <span class="stop__badge" style="--c: var(--sky)">do {{ book?.registered_free_pages ?? 11 }}</span>
                        <h3 class="h3">Z kontem</h3>
                        <p>Załóż darmowe konto, a odblokujesz kolejne strony i zapamiętamy, gdzie skończyłeś.</p>
                    </div>
                    <div class="stop" v-reveal="0.5">
                        <span class="stop__badge stop__badge--coin" style="--c: var(--gold)"><CoinIcon /> {{ book?.price_per_page ?? 5 }}</span>
                        <h3 class="h3">Dalej z monetami</h3>
                        <p>Każda kolejna strona to {{ book?.price_per_page ?? 5 }} monet. Raz odblokowana zostaje Twoja na zawsze.</p>
                    </div>
                </div>

                <div class="path__cta" v-reveal="0.2">
                    <Link :href="readHref" class="btn btn--shine" data-sfx="bookOpen" @click="celebrate"><BookOpen /> Otwórz książkę</Link>
                    <Link v-if="!user" :href="route('register')" class="link">albo załóż darmowe konto <ArrowRight class="h-4 w-4" /></Link>
                </div>
            </div>
        </section>

        <!-- ── Quote ────────────────────────────────────────────────────── -->
        <section class="quote">
            <Wave color="var(--cream)" flip :animated="false" class="quote__edge" />
            <div class="quote__media" data-drift="0.12">
                <img src="/images/kukielka.webp" alt="" loading="lazy" />
            </div>
            <div class="quote__shade" />
            <blockquote class="quote__text wrap">
                <Headphones class="quote__icon" aria-hidden="true" />
                <p class="h1 italic-wonk" v-reveal:words>
                    <SplitText text="„Odwaga to nie brak strachu. To decyzja, że coś innego jest ważniejsze niż strach.”" />
                </p>
                <footer v-reveal="0.4">— Kocica w Szpilkach, rozdział „Lustra bywają błękitne jak niebo”</footer>
            </blockquote>
            <Wave color="var(--night)" :animated="false" class="quote__edge quote__edge--bottom" />
        </section>

        <!-- ── Final call ───────────────────────────────────────────────── -->
        <section class="finale night">
            <NightSky :stars="50" :moon="false" :seed="21" />
            <Fireflies :count="12" />
            <div class="wrap finale__inner">
                <div class="finale__dragon float float--slow" v-reveal:pop><Dragon /></div>
                <div class="finale__text">
                    <h2 class="h1" v-reveal:words><SplitText text="Smok czeka." /> <em class="italic-wonk"><SplitText text="Zaczynamy?" :offset="2" /></em></h2>
                    <p class="lead" v-reveal="0.2">Usiądź wygodnie, włącz kominek i przewróć pierwszą kartkę.</p>
                    <div class="finale__actions" v-reveal="0.35">
                        <Link :href="readHref" class="btn btn--gold btn--shine" data-sfx="bookOpen" @click="celebrate"><BookOpen /> Czytaj teraz</Link>
                        <Link v-if="!user" :href="route('register')" class="btn btn--ghost">Załóż konto</Link>
                        <Link v-else :href="route('dashboard')" class="btn btn--ghost">Moja półka</Link>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
/* ── Hero ───────────────────────────────────────────────────────────── */
.hero {
    position: relative;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
    isolation: isolate;
    padding-top: var(--header-h);
}

.hero__inner {
    position: relative;
    z-index: 3;
    text-align: center;
    padding-block: 4rem 12rem;
    transform: translate3d(calc(var(--mx, 0) * 8px), calc(var(--scroll-y, 0) * 0.25px), 0);
    transition: transform 0.6s var(--ease-out);
}

.hero__eyebrow {
    justify-content: center;
}

.hero__eyebrow::before {
    display: none;
}

.hero__title {
    margin-top: 1.25rem;
    text-shadow: 0 6px 40px rgb(0 0 0 / 0.35);
}

.hero__accent {
    color: var(--gold);
    font-weight: 400;
}

.hero__lead {
    max-width: 36rem;
    margin: 1.75rem auto 0;
}

.hero__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.9rem;
    margin-top: 2.25rem;
}

.hero__dragon {
    position: absolute;
    z-index: 2;
    top: 18%;
    left: 0;
    width: clamp(7rem, 13vw, 11rem);
    animation: dragon-fly 26s linear infinite;
}

@keyframes dragon-fly {
    0% {
        transform: translate3d(-20vw, 8vh, 0) rotate(-4deg);
    }
    25% {
        transform: translate3d(22vw, -2vh, 0) rotate(3deg);
    }
    50% {
        transform: translate3d(55vw, 6vh, 0) rotate(-3deg);
    }
    75% {
        transform: translate3d(85vw, -4vh, 0) rotate(4deg);
    }
    100% {
        transform: translate3d(120vw, 4vh, 0) rotate(-2deg);
    }
}

.hero__foot {
    position: absolute;
    z-index: 3;
    left: 50%;
    bottom: 3.5rem;
    translate: -50% 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgb(251 244 230 / 0.7);
}

.hero__scroll {
    display: grid;
    place-items: center;
    width: 2.2rem;
    height: 3.4rem;
    border: 2px solid rgb(251 244 230 / 0.5);
    border-radius: 999px;
}

.hero__scroll i {
    width: 4px;
    height: 10px;
    border-radius: 4px;
    background: var(--gold);
    animation: scroll-dot 1.8s var(--ease-in-out) infinite;
}

@keyframes scroll-dot {
    0% {
        transform: translateY(-8px);
        opacity: 0;
    }
    40% {
        opacity: 1;
    }
    100% {
        transform: translateY(10px);
        opacity: 0;
    }
}

@media (max-width: 640px) {
    .hero__foot span {
        display: none;
    }
    .hero__foot {
        justify-content: center;
    }
}

/* ── Intro ──────────────────────────────────────────────────────────── */
.intro__grid {
    display: grid;
    gap: 4rem;
    align-items: center;
}

@media (min-width: 960px) {
    .intro__grid {
        grid-template-columns: 1.05fr 1fr;
    }
}

.intro__text {
    display: grid;
    gap: 1.6rem;
}

.intro__art {
    position: relative;
    max-width: 34rem;
    width: 100%;
    margin-inline: auto;
    padding: 0 2rem 4rem 0;
}

.intro__arch {
    aspect-ratio: 4 / 5;
    box-shadow: var(--shadow-lift);
}

.intro__drift {
    position: absolute;
    inset: -9% 0;
    will-change: transform;
}

.intro__circle > div {
    height: 100%;
}

.intro__circle {
    position: absolute;
    right: -1rem;
    bottom: 0;
    width: 44%;
    aspect-ratio: 1;
    border: 8px solid var(--cream);
    box-shadow: var(--shadow-lift);
}

.intro__stamp {
    position: absolute;
    left: -2.5rem;
    bottom: 1.5rem;
    width: clamp(7rem, 13vw, 9.5rem);
    font-family: var(--font-body);
    font-size: 15px;
    font-weight: 900;
    letter-spacing: 0.12em;
    fill: var(--night);
}

.intro__spark {
    position: absolute;
    top: -1rem;
    right: 2rem;
    width: 2.5rem;
    height: 2.5rem;
    color: var(--gold);
}

/* ── Band ───────────────────────────────────────────────────────────── */
.band {
    padding-block: 1.75rem;
    background: var(--night);
    color: var(--cream);
    rotate: -1.5deg;
    margin-inline: -2rem;
    box-shadow: var(--shadow-lift);
    position: relative;
    z-index: 3;
}

.band__item {
    display: inline-flex;
    align-items: center;
    gap: clamp(1.5rem, 3vw, 3rem);
    white-space: nowrap;
}

.band__star {
    width: 1.6rem;
    height: 1.6rem;
    fill: var(--gold);
    animation: spin 8s linear infinite;
}

/* ── Section heads ──────────────────────────────────────────────────── */
.section-head {
    display: grid;
    gap: 1.1rem;
    max-width: 46rem;
    margin-bottom: clamp(2.5rem, 5vw, 4.5rem);
}

.section-head--center {
    margin-inline: auto;
    text-align: center;
    justify-items: center;
}

.section-head--center .eyebrow::before {
    display: none;
}

/* ── Features ───────────────────────────────────────────────────────── */
.features {
    background: var(--sand);
}

.features__grid {
    display: grid;
    gap: 1.5rem;
}

@media (min-width: 900px) {
    .features__grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .features__grid > :nth-child(2) {
        margin-top: 3rem;
    }
}

.feature {
    padding: 2rem;
    display: grid;
    gap: 0.9rem;
    align-content: start;
}

.feature p {
    color: var(--ink-soft);
    line-height: 1.65;
}

.feature__art {
    display: grid;
    place-items: center;
    height: 9.5rem;
    border-radius: 1.25rem;
    margin-bottom: 0.5rem;
}

.feature__art svg {
    width: 11rem;
    overflow: visible;
}

.feature__art--book {
    background: #dcebdf;
}

.feature__art--listen {
    background: #fbe1ea;
}

.feature__art--magic {
    background: #fdf0c9;
}

.feature__leaf {
    transform-box: view-box;
    transform-origin: 80px 60px;
    animation: leaf 3.6s var(--ease-in-out) infinite;
}

@keyframes leaf {
    0%,
    15% {
        transform: rotateY(0deg);
    }
    50%,
    65% {
        transform: rotateY(180deg);
    }
    100% {
        transform: rotateY(0deg);
    }
}

.feature__waves path {
    animation: waves 1.6s var(--ease-in-out) infinite;
}

.feature__waves path:nth-child(2),
.feature__waves path:nth-child(4) {
    animation-delay: 0.25s;
}

@keyframes waves {
    0%,
    100% {
        opacity: 0.2;
    }
    50% {
        opacity: 1;
    }
}

/* ── Cast ───────────────────────────────────────────────────────────── */
.cast__grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
}

.hero-card {
    position: relative;
    display: grid;
    gap: 0.35rem;
    justify-items: start;
    padding: 1rem 1rem 1.6rem;
    border-radius: 2rem;
    text-align: left;
    background: var(--paper);
    border: 1px solid var(--line);
    box-shadow: var(--shadow-soft);
    cursor: pointer;
    transition:
        transform 0.5s var(--ease-bounce),
        box-shadow 0.3s;
}

.hero-card:hover {
    transform: translateY(-8px) rotate(var(--tilt));
    box-shadow: var(--shadow-lift);
}

.hero-card__photo {
    width: 100%;
    aspect-ratio: 1;
    margin-bottom: 0.8rem;
    background: color-mix(in srgb, var(--tint) 30%, var(--sand));
    transition: border-radius 0.8s var(--ease-in-out);
}

.hero-card:hover .hero-card__photo {
    border-radius: 50%;
}

.hero-card__photo img {
    transition: transform 0.8s var(--ease-out);
}

.hero-card:hover .hero-card__photo img {
    transform: scale(1.07);
}

.hero-card__role {
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background: color-mix(in srgb, var(--tint, var(--gold)) 20%, transparent);
    color: color-mix(in srgb, var(--tint, var(--gold)) 70%, var(--ink));
    margin-inline: 0.5rem;
}

.hero-card__name {
    margin-inline: 0.5rem;
}

.hero-card__text {
    margin-inline: 0.5rem;
    color: var(--ink-soft);
    font-size: 0.95rem;
    line-height: 1.55;
}

.hero-card__bubble {
    position: absolute;
    z-index: 2;
    top: 1.25rem;
    right: 1.25rem;
    padding: 0.55rem 0.9rem;
    border-radius: 1.2rem 1.2rem 0.3rem 1.2rem;
    background: var(--paper);
    font-family: var(--font-display);
    font-style: italic;
    font-weight: 600;
    font-size: 1.05rem;
    box-shadow: var(--shadow-lift);
    opacity: 0;
    transform: scale(0.3) translateY(10px);
    transform-origin: bottom right;
    transition:
        opacity 0.2s,
        transform 0.45s var(--ease-bounce);
    pointer-events: none;
}

.hero-card.is-speaking .hero-card__bubble {
    opacity: 1;
    transform: none;
}

.hero-card.is-speaking .hero-card__photo {
    animation: wiggle 0.7s var(--ease-in-out);
}

.hero-card--dragon {
    overflow: hidden;
    background: linear-gradient(180deg, #1f4642, var(--night-deep));
    color: var(--cream);
    border: 0;
    cursor: default;
    --tint: var(--gold);
}

.hero-card--dragon .hero-card__text {
    color: rgb(251 244 230 / 0.75);
}

.hero-card--dragon .hero-card__role {
    color: var(--gold);
}

.hero-card__dragon {
    position: relative;
    display: grid;
    place-items: center;
    width: 100%;
    aspect-ratio: 1;
    margin-bottom: 0.8rem;
    padding: 12%;
}

/* ── Gallery ────────────────────────────────────────────────────────── */
.gallery {
    padding-block: var(--section);
    background: var(--sand);
    overflow: hidden;
}

.gallery__row {
    padding-block: 0.75rem;
}

.gallery__tile {
    position: relative;
    flex: none;
    width: clamp(11rem, 18vw, 15rem);
    aspect-ratio: 1;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: var(--shadow-soft);
    transition:
        transform 0.5s var(--ease-bounce),
        box-shadow 0.3s;
}

.gallery__tile:nth-child(odd) {
    rotate: -2deg;
}

.gallery__tile:nth-child(even) {
    rotate: 2deg;
}

.gallery__tile:hover {
    transform: scale(1.06);
    box-shadow: var(--shadow-lift);
    z-index: 2;
}

.gallery__tile img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery__tile span {
    position: absolute;
    left: 0.75rem;
    bottom: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 900;
    background: rgb(255 250 240 / 0.9);
    color: var(--night);
    opacity: 0;
    transform: translateY(6px);
    transition: all 0.3s var(--ease-bounce);
}

.gallery__tile:hover span {
    opacity: 1;
    transform: none;
}

/* ── Path ───────────────────────────────────────────────────────────── */
.path__stops {
    position: relative;
    display: grid;
    gap: 2.5rem;
}

@media (min-width: 860px) {
    .path__stops {
        grid-template-columns: repeat(3, 1fr);
        gap: 3rem;
    }
}

.path__line {
    position: absolute;
    top: 1rem;
    left: 8%;
    width: 84%;
    height: 7rem;
    z-index: 0;
    display: none;
}

@media (min-width: 860px) {
    .path__line {
        display: block;
    }
}

.stop {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 0.6rem;
    justify-items: center;
    text-align: center;
}

.stop p {
    max-width: 19rem;
    color: var(--ink-soft);
    line-height: 1.6;
}

.stop__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-width: 6.5rem;
    height: 6.5rem;
    padding: 0 1rem;
    border-radius: 50%;
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.6rem;
    color: #fff;
    background: var(--c);
    box-shadow:
        0 0 0 8px var(--cream),
        0 0 0 10px color-mix(in srgb, var(--c) 40%, transparent),
        var(--shadow-lift);
    margin-bottom: 0.8rem;
    transition: transform 0.5s var(--ease-bounce);
}

.stop:hover .stop__badge {
    transform: scale(1.08) rotate(-8deg);
}

.stop__badge--coin {
    color: var(--night-deep);
}

.stop__badge--coin svg {
    width: 2rem;
    height: 2rem;
}

.path__cta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 4rem;
}

/* ── Quote ──────────────────────────────────────────────────────────── */
.quote {
    position: relative;
    min-height: 90svh;
    display: grid;
    place-items: center;
    overflow: hidden;
    color: var(--cream);
    isolation: isolate;
}

.quote__edge {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    margin: 0;
    z-index: 3;
}

.quote__edge--bottom {
    top: auto;
    bottom: 0;
}

.quote__media {
    position: absolute;
    inset: -12% 0;
    z-index: -2;
    will-change: transform;
}

.quote__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 30%;
}

.quote__shade {
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        radial-gradient(70% 60% at 50% 50%, rgb(11 28 29 / 0.55), rgb(11 28 29 / 0.85)),
        linear-gradient(180deg, transparent 60%, rgb(11 28 29 / 0.6));
}

.quote__text {
    max-width: 58rem;
    text-align: center;
    padding-block: 7rem;
}

.quote__icon {
    width: 2.5rem;
    height: 2.5rem;
    margin: 0 auto 1.5rem;
    color: var(--gold);
}

.quote__text footer {
    margin-top: 2rem;
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgb(251 244 230 / 0.7);
}

/* ── Finale ─────────────────────────────────────────────────────────── */
.finale {
    background: linear-gradient(180deg, var(--night) 0%, var(--night-deep) 100%);
    position: relative;
    overflow: hidden;
    padding-block: clamp(4rem, 9vw, 8rem) clamp(5rem, 10vw, 9rem);
}

.finale__inner {
    position: relative;
    display: grid;
    gap: 2rem;
    align-items: center;
}

@media (min-width: 860px) {
    .finale__inner {
        grid-template-columns: 0.8fr 1.2fr;
    }
}

.finale__dragon {
    width: min(22rem, 70vw);
    margin-inline: auto;
}

.finale__text {
    display: grid;
    gap: 1.4rem;
}

.finale__text em {
    color: var(--gold);
}

.finale__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
    margin-top: 0.5rem;
}
</style>
