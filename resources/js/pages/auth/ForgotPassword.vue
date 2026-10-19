<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout
        eyebrow="Zgubione hasło"
        title="Nawet smokom się zdarza"
        description="Podaj swój e-mail, a wyślemy link do ustawienia nowego hasła."
        aside="Smok też kiedyś zgubił swój ogień. Hasło znajdziemy dużo szybciej!"
    >
        <Head title="Nie pamiętam hasła" />

        <div v-if="status" class="mb-4 rounded-2xl bg-[#dcebdf] px-4 py-3 text-sm font-bold text-[var(--moss)]">
            {{ status }}
        </div>

        <form class="grid gap-5" @submit.prevent="submit">
            <div class="field">
                <label for="email">Adres e-mail</label>
                <input id="email" v-model="form.email" class="input" type="email" required autofocus autocomplete="off" placeholder="ty@przyklad.pl" />
                <p v-if="form.errors.email" class="input-error">{{ form.errors.email }}</p>
            </div>

            <button type="submit" class="btn btn--block" :disabled="form.processing" data-sfx="whoosh">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Wyślij link
            </button>

            <p class="text-center text-[var(--ink-soft)]">
                Jednak pamiętasz?
                <Link :href="route('login')" class="font-extrabold text-[var(--moss)] underline underline-offset-4">Wróć do logowania</Link>
            </p>
        </form>
    </AuthLayout>
</template>
