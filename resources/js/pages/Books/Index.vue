<script setup lang="ts">
import BookCover from '@/components/story/BookCover.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BookSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps<{ books: BookSummary[] }>();

const progress = ref<Record<number, number>>({});
onMounted(() => {
    for (const book of props.books) {
        try {
            const n = Number(localStorage.getItem(`bajka:progress:${book.id}`));
            if (n > 0) progress.value[book.id] = n;
        } catch {
            /* ignore */
        }
    }
});

const colors = ['#7a3b26', '#15322f', '#3d6fb6', '#9c2f58'];
</script>

<template>
    <Head title="Biblioteczka" />

    <AppLayout>
        <section class="wrap library">
            <p class="eyebrow" v-reveal>Biblioteczka</p>
            <h1 class="h1" v-reveal="0.05">Wszystkie bajki</h1>

            <div class="library__list">
                <article v-for="(book, i) in books" :key="book.id" class="entry card" v-reveal="i * 0.08">
                    <Link :href="route('pages.show', { book: book.id, page: progress[book.id] ?? 1 })" class="entry__cover" data-sfx="bookOpen">
                        <BookCover :title="book.title" :image="book.cover" :color="colors[i % colors.length]" />
                    </Link>
                    <div class="entry__body">
                        <h2 class="h2">{{ book.title }}</h2>
                        <p class="entry__meta">{{ book.total_pages }} stron · {{ book.chapters.length }} rozdziałów · {{ book.price_per_page }} monet za stronę</p>
                        <ol class="entry__chapters">
                            <li v-for="c in book.chapters" :key="c.page">
                                <Link :href="route('pages.show', { book: book.id, page: c.page })">{{ c.title }}</Link>
                            </li>
                        </ol>
                        <Link :href="route('pages.show', { book: book.id, page: progress[book.id] ?? 1 })" class="btn" data-sfx="bookOpen">
                            <BookOpen /> {{ progress[book.id] ? `Czytaj dalej od str. ${progress[book.id]}` : 'Zacznij czytać' }}
                        </Link>
                    </div>
                </article>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.library {
    padding-block: 3rem var(--section);
}

.library h1 {
    margin: 0.75rem 0 2.5rem;
}

.library__list {
    display: grid;
    gap: 1.5rem;
}

.entry {
    display: grid;
    gap: 2rem;
    padding: clamp(1.25rem, 3vw, 2.25rem);
}

@media (min-width: 760px) {
    .entry {
        grid-template-columns: 12rem 1fr;
    }
}

.entry__cover {
    width: 12rem;
    margin-inline: auto;
}

.entry__body {
    display: grid;
    gap: 1rem;
    align-content: start;
    justify-items: start;
}

.entry__meta {
    font-weight: 700;
    color: var(--ink-soft);
}

.entry__chapters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    counter-reset: ch;
}

.entry__chapters a {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    background: var(--sand);
    font-family: var(--font-display);
    font-style: italic;
    font-size: 0.95rem;
    transition:
        background 0.2s,
        transform 0.3s var(--ease-bounce);
}

.entry__chapters a:hover {
    background: var(--gold-soft);
    transform: translateY(-2px) rotate(-1deg);
}
</style>
