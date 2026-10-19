import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { vReveal } from './lib/reveal';
import { play, preload, type SoundName } from './lib/sound';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Bajka';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .directive('reveal', vReveal)
            .mount(el);
    },
    progress: {
        color: '#f2b84b',
    },
});

// Warm up the common sounds on the first touch, which is also the moment
// browsers let audio start.
window.addEventListener('pointerdown', () => preload('pop', 'flip', 'sparkle', 'coin', 'whoosh'), { once: true, capture: true });

// Anything marked data-sfx="name" plays that sound when clicked; buttons
// and links styled as .btn give a soft pop unless they say otherwise.
document.addEventListener(
    'click',
    (event) => {
        const el = (event.target as Element | null)?.closest<HTMLElement>('[data-sfx], .btn');
        if (!el) return;
        const name = (el.dataset.sfx ?? 'pop') as SoundName | 'none';
        if (name !== 'none') void play(name);
    },
    { capture: true },
);
