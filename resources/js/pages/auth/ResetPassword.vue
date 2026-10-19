<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout eyebrow="Nowe hasło" title="Ustaw nowe hasło" description="Wybierz hasło, które łatwo zapamiętasz, a trudno zgadnąć.">
        <Head title="Nowe hasło" />

        <form class="grid gap-5" @submit.prevent="submit">
            <div class="field">
                <label for="email">Adres e-mail</label>
                <input id="email" v-model="form.email" class="input" type="email" autocomplete="email" readonly />
                <p v-if="form.errors.email" class="input-error">{{ form.errors.email }}</p>
            </div>

            <div class="field">
                <label for="password">Nowe hasło</label>
                <input id="password" v-model="form.password" class="input" type="password" required autofocus autocomplete="new-password" placeholder="Co najmniej 8 znaków" />
                <p v-if="form.errors.password" class="input-error">{{ form.errors.password }}</p>
            </div>

            <div class="field">
                <label for="password_confirmation">Powtórz hasło</label>
                <input id="password_confirmation" v-model="form.password_confirmation" class="input" type="password" required autocomplete="new-password" />
                <p v-if="form.errors.password_confirmation" class="input-error">{{ form.errors.password_confirmation }}</p>
            </div>

            <button type="submit" class="btn btn--block" :disabled="form.processing" data-sfx="unlock">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Zapisz nowe hasło
            </button>
        </form>
    </AuthLayout>
</template>
