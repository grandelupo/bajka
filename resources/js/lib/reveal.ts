import type { Directive } from 'vue';

/*
 * v-reveal — elements rise into view once, as they scroll in.
 *
 *   v-reveal              rise and fade
 *   v-reveal:words        each word of a <SplitText> rises in turn
 *   v-reveal:mask         a picture unveils from below
 *   v-reveal:pop          springs up, for stickers and badges
 *   v-reveal:draw         SVG paths draw themselves
 *   v-reveal="0.2"        any of the above, delayed by 0.2s
 *
 * The hidden states live in motion.css behind `html.js`, so a page whose
 * scripts fail still reads fine.
 */

let observer: IntersectionObserver | null = null;

const reduced = () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function getObserver() {
    observer ??= new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                entry.target.classList.add('in');
                observer?.unobserve(entry.target);
            }
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
    );
    return observer;
}

export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        el.setAttribute('data-reveal', binding.arg ?? '');
        if (binding.value) el.style.setProperty('--d', String(binding.value));

        if (reduced()) {
            el.classList.add('in');
            return;
        }
        getObserver().observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
