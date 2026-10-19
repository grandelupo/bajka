<script setup lang="ts">
import Dragon from '@/components/story/Dragon.vue';
import FlashToast from '@/components/story/FlashToast.vue';
import Fireflies from '@/components/story/Fireflies.vue';
import Landscape from '@/components/story/Landscape.vue';
import Logo from '@/components/story/Logo.vue';
import NightSky from '@/components/story/NightSky.vue';
import SoundToggle from '@/components/story/SoundToggle.vue';
import { Link } from '@inertiajs/vue3';

/*
 * Sign-in and sign-up: the night over the fort on one side, the form on a
 * sheet of paper on the other. `eyebrow`, `title` and `description` head the
 * form; `aside` is the line under the dragon.
 */
withDefaults(defineProps<{ title?: string; description?: string; eyebrow?: string; aside?: string }>(), {
    aside: 'Smok czeka w starym forcie. Usiądź wygodnie — zaraz przewracamy kartki.',
});
</script>

<template>
    <div class="auth">
        <aside class="auth__night night">
            <NightSky :stars="60" :seed="9" />
            <Fireflies :count="14" />
            <Landscape />
            <div class="auth__brand">
                <Link :href="route('home')" aria-label="Bajka — strona główna" style="--logo-gutter: var(--night)">
                    <Logo />
                </Link>
                <SoundToggle class="auth__sound" />
            </div>
            <div class="auth__tale">
                <div class="auth__dragon float"><Dragon /></div>
                <p class="auth__aside">{{ aside }}</p>
            </div>
        </aside>

        <main class="auth__paper grain">
            <div class="auth__card">
                <p v-if="eyebrow" class="eyebrow" v-reveal>{{ eyebrow }}</p>
                <h1 class="h2" v-reveal="0.05">{{ title }}</h1>
                <p v-if="description" class="auth__desc" v-reveal="0.1">{{ description }}</p>
                <div class="auth__form" v-reveal="0.15">
                    <slot />
                </div>
            </div>
        </main>
        <FlashToast />
    </div>
</template>

<style scoped>
.auth {
    display: grid;
    min-height: 100svh;
    background: var(--cream);
}

@media (min-width: 960px) {
    .auth {
        grid-template-columns: 1fr 1fr;
    }
}

.auth__night {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    display: flex;
    flex-direction: column;
    min-height: 15rem;
}

@media (min-width: 960px) {
    .auth__night {
        position: sticky;
        top: 0;
        height: 100svh;
        border-radius: 0 2.5rem 2.5rem 0;
    }
}

.auth__brand {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem clamp(1.25rem, 4vw, 3rem);
}

.auth__sound {
    color: var(--cream);
}

.auth__tale {
    position: relative;
    z-index: 3;
    flex: 1;
    display: grid;
    align-content: center;
    justify-items: center;
    gap: 1.5rem;
    padding: 0 2rem 9rem;
    text-align: center;
}

.auth__dragon {
    width: min(18rem, 50vw);
}

.auth__aside {
    max-width: 24rem;
    font-family: var(--font-display);
    font-style: italic;
    font-size: clamp(1.15rem, 1.8vw, 1.45rem);
    line-height: 1.4;
    color: rgb(251 244 230 / 0.85);
}

@media (max-width: 959px) {
    .auth__tale {
        padding: 0 1.5rem 5.5rem;
        grid-template-columns: auto 1fr;
        text-align: left;
        gap: 1rem;
    }
    .auth__dragon {
        width: 7rem;
    }
    .auth__aside {
        font-size: 1rem;
    }
}

.auth__paper {
    display: grid;
    place-items: center;
    padding: clamp(2rem, 6vw, 5rem) clamp(1.25rem, 5vw, 4rem);
}

.auth__card {
    width: 100%;
    max-width: 27rem;
    display: grid;
    gap: 0.9rem;
}

.auth__desc {
    color: var(--ink-soft);
    line-height: 1.6;
}

.auth__form {
    margin-top: 1rem;
}
</style>
