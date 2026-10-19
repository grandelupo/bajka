<script setup lang="ts">
import GoogleButton from '@/components/story/GoogleButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout eyebrow="Witaj z powrotem" title="Wracamy do bajki?" description="Zaloguj się, a otworzymy książkę dokładnie tam, gdzie ją zostawiłeś.">
        <Head title="Logowanie" />

        <div v-if="status" class="mb-4 rounded-2xl bg-[#dcebdf] px-4 py-3 text-sm font-bold text-[var(--moss)]">
            {{ status }}
        </div>

        <GoogleButton />

        <form class="grid gap-5" @submit.prevent="submit">
            <div class="field">
                <label for="email">Adres e-mail</label>
                <input id="email" v-model="form.email" class="input" type="email" required autofocus autocomplete="email" placeholder="ty@przyklad.pl" />
                <p v-if="form.errors.email" class="input-error">{{ form.errors.email }}</p>
            </div>

            <div class="field">
                <div class="flex items-center justify-between">
                    <label for="password" class="field-label">Hasło</label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-bold text-[var(--moss)] hover:underline">
                        Nie pamiętasz?
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        class="input pr-12"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Twoje hasło"
                    />
                    <button
                        type="button"
                        class="absolute top-1/2 right-3 -translate-y-1/2 rounded-full p-1.5 text-[var(--ink-soft)] hover:bg-[var(--sand)]"
                        :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="h-5 w-5" />
                        <Eye v-else class="h-5 w-5" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="input-error">{{ form.errors.password }}</p>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm font-bold text-[var(--ink-soft)]">
                <input v-model="form.remember" type="checkbox" class="h-5 w-5 accent-[var(--moss)]" />
                Zapamiętaj mnie na tym urządzeniu
            </label>

            <button type="submit" class="btn btn--block btn--shine mt-2" :disabled="form.processing" data-sfx="bookOpen">
                <LoaderCircle v-if="form.processing" class="animate-spin" />
                Zaloguj się
            </button>

            <p class="text-center text-[var(--ink-soft)]">
                Pierwszy raz tutaj?
                <Link :href="route('register')" class="font-extrabold text-[var(--moss)] underline underline-offset-4">Załóż darmowe konto</Link>
            </p>
        </form>
    </AuthLayout>
</template>
