<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout eyebrow="Jeszcze tylko jedno" title="Sprawdź skrzynkę" description="Wysłaliśmy Ci e-mail z linkiem. Kliknij go, a potwierdzisz swój adres.">
        <Head title="Potwierdź e-mail" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 rounded-2xl bg-[#dcebdf] px-4 py-3 text-sm font-bold text-[var(--moss)]">
            Wysłaliśmy nowy link na adres podany przy rejestracji.
        </div>

        <form class="grid gap-4" @submit.prevent="submit">
            <button type="submit" class="btn btn--block" :disabled="form.processing" data-sfx="whoosh">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Wyślij link ponownie
            </button>
            <Link :href="route('logout')" method="post" as="button" class="mx-auto font-extrabold text-[var(--moss)] underline underline-offset-4">
                Wyloguj się
            </Link>
        </form>
    </AuthLayout>
</template>
