<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Check, LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors) => {
            if (errors.password) form.reset('password', 'password_confirmation');
            if (errors.current_password) form.reset('current_password');
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Hasło" />

        <SettingsLayout>
            <div class="grid gap-6">
                <div>
                    <h2 class="h3">Zmień hasło</h2>
                    <p class="mt-1 text-[var(--ink-soft)]">Długie hasło to najlepsza smocza zbroja.</p>
                </div>

                <form class="grid gap-5" @submit.prevent="updatePassword">
                    <div class="field">
                        <label for="current_password">Obecne hasło</label>
                        <input id="current_password" v-model="form.current_password" class="input" type="password" autocomplete="current-password" />
                        <p v-if="form.errors.current_password" class="input-error">{{ form.errors.current_password }}</p>
                    </div>

                    <div class="field">
                        <label for="password">Nowe hasło</label>
                        <input id="password" v-model="form.password" class="input" type="password" autocomplete="new-password" />
                        <p v-if="form.errors.password" class="input-error">{{ form.errors.password }}</p>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Powtórz nowe hasło</label>
                        <input id="password_confirmation" v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" />
                        <p v-if="form.errors.password_confirmation" class="input-error">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="btn" :disabled="form.processing" data-sfx="unlock">
                            <LoaderCircle v-if="form.processing" class="animate-spin" />
                            Zapisz hasło
                        </button>
                        <Transition enter-from-class="opacity-0 scale-75" enter-active-class="transition duration-300" leave-to-class="opacity-0" leave-active-class="transition">
                            <p v-show="form.recentlySuccessful" class="flex items-center gap-1 font-bold text-[var(--moss)]"><Check class="h-4 w-4" /> Zapisane!</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
