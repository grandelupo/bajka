<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout eyebrow="Bezpieczeństwo" title="Potwierdź hasło" description="To chronione miejsce. Wpisz hasło, zanim pójdziemy dalej.">
        <Head title="Potwierdź hasło" />

        <form class="grid gap-5" @submit.prevent="submit">
            <div class="field">
                <label for="password">Hasło</label>
                <input id="password" v-model="form.password" class="input" type="password" required autocomplete="current-password" autofocus />
                <p v-if="form.errors.password" class="input-error">{{ form.errors.password }}</p>
            </div>

            <button type="submit" class="btn btn--block" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Potwierdź
            </button>
        </form>
    </AuthLayout>
</template>
