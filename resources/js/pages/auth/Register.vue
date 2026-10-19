<script setup lang="ts">
import GoogleButton from '@/components/story/GoogleButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const strength = computed(() => {
    const p = form.password;
    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
    if (/\d/.test(p) || /[^A-Za-z0-9]/.test(p)) score++;
    return score;
});
const strengthLabel = computed(() => ['Za krótkie', 'Słabe', 'Niezłe', 'Mocne', 'Smoczo mocne!'][strength.value]);
const matches = computed(() => form.password_confirmation.length > 0 && form.password === form.password_confirmation);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        eyebrow="Darmowe konto"
        title="Dołącz do przygody"
        description="Z kontem przeczytasz za darmo strony aż do jedenastej, a my zapamiętamy, gdzie skończyłeś."
        aside="Olga już szuka śladów, Igor programuje drona. Brakuje tylko Ciebie!"
    >
        <Head title="Rejestracja" />

        <GoogleButton />

        <form class="grid gap-5" @submit.prevent="submit">
            <div class="field">
                <label for="name">Jak masz na imię?</label>
                <input id="name" v-model="form.name" class="input" type="text" required autofocus autocomplete="name" placeholder="Imię" />
                <p v-if="form.errors.name" class="input-error">{{ form.errors.name }}</p>
            </div>

            <div class="field">
                <label for="email">Adres e-mail</label>
                <input id="email" v-model="form.email" class="input" type="email" required autocomplete="email" placeholder="ty@przyklad.pl" />
                <p v-if="form.errors.email" class="input-error">{{ form.errors.email }}</p>
            </div>

            <div class="field">
                <label for="password">Hasło</label>
                <input id="password" v-model="form.password" class="input" type="password" required autocomplete="new-password" placeholder="Co najmniej 8 znaków" />
                <div v-if="form.password" class="strength" :data-score="strength">
                    <span v-for="i in 4" :key="i" :class="{ on: i <= strength }" />
                    <em>{{ strengthLabel }}</em>
                </div>
                <p v-if="form.errors.password" class="input-error">{{ form.errors.password }}</p>
            </div>

            <div class="field">
                <label for="password_confirmation">Powtórz hasło</label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        class="input pr-12"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Jeszcze raz to samo"
                    />
                    <Transition name="pop">
                        <Check v-if="matches" class="absolute top-1/2 right-4 h-5 w-5 -translate-y-1/2 text-[var(--moss)]" />
                    </Transition>
                </div>
                <p v-if="form.errors.password_confirmation" class="input-error">{{ form.errors.password_confirmation }}</p>
            </div>

            <button type="submit" class="btn btn--gold btn--block btn--shine mt-2" :disabled="form.processing" data-sfx="sparkle">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Załóż konto i czytaj
            </button>

            <p class="text-center text-[var(--ink-soft)]">
                Masz już konto?
                <Link :href="route('login')" class="font-extrabold text-[var(--moss)] underline underline-offset-4">Zaloguj się</Link>
            </p>
        </form>
    </AuthLayout>
</template>

<style scoped>
.strength {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.15rem;
}

.strength span {
    flex: 1;
    height: 6px;
    border-radius: 6px;
    background: var(--sand);
    transition: background 0.3s;
}

.strength[data-score='1'] span.on {
    background: #e8643a;
}

.strength[data-score='2'] span.on {
    background: var(--gold);
}

.strength[data-score='3'] span.on,
.strength[data-score='4'] span.on {
    background: var(--moss);
}

.strength em {
    min-width: 7.5rem;
    text-align: right;
    font-style: normal;
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--ink-soft);
}

.pop-enter-active {
    transition: transform 0.4s var(--ease-bounce);
}

.pop-enter-from {
    transform: translateY(-50%) scale(0) !important;
}
</style>
