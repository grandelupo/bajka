<script setup lang="ts">
import DeleteUser from '@/components/DeleteUser.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type SharedData, type User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Check, LoaderCircle } from 'lucide-vue-next';

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Profil" />

        <SettingsLayout>
            <div class="grid gap-6">
                <div>
                    <h2 class="h3">Twój profil</h2>
                    <p class="mt-1 text-[var(--ink-soft)]">Imię i adres e-mail, którymi się logujesz.</p>
                </div>

                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="field">
                        <label for="name">Imię</label>
                        <input id="name" v-model="form.name" class="input" required autocomplete="name" />
                        <p v-if="form.errors.name" class="input-error">{{ form.errors.name }}</p>
                    </div>

                    <div class="field">
                        <label for="email">Adres e-mail</label>
                        <input id="email" v-model="form.email" class="input" type="email" required autocomplete="username" />
                        <p v-if="form.errors.email" class="input-error">{{ form.errors.email }}</p>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm text-[var(--ink-soft)]">
                        Twój adres nie jest jeszcze potwierdzony.
                        <Link :href="route('verification.send')" method="post" as="button" class="font-bold text-[var(--moss)] underline">
                            Wyślij link ponownie
                        </Link>
                        <p v-if="status === 'verification-link-sent'" class="mt-2 font-bold text-[var(--moss)]">Wysłaliśmy nowy link.</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="btn" :disabled="form.processing" data-sfx="sparkle">
                            <LoaderCircle v-if="form.processing" class="animate-spin" />
                            Zapisz
                        </button>
                        <Transition enter-from-class="opacity-0 scale-75" enter-active-class="transition duration-300" leave-to-class="opacity-0" leave-active-class="transition">
                            <p v-show="form.recentlySuccessful" class="flex items-center gap-1 font-bold text-[var(--moss)]"><Check class="h-4 w-4" /> Zapisane!</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
