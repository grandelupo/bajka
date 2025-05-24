<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('home')" class="text-xl font-bold text-gray-800">
                                Czytnik Bajek
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <template v-if="$page.props.auth.user">
                            <div class="ml-3 relative">
                                <div class="flex items-center space-x-4">
                                    <span class="text-gray-700">
                                        Monety: {{ $page.props.auth.user.coins }}
                                    </span>
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="text-gray-700 hover:text-gray-900"
                                    >
                                        Wyloguj
                                    </Link>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-gray-700 hover:text-gray-900"
                            >
                                Zaloguj
                            </Link>
                            <Link
                                :href="route('register')"
                                class="ml-4 text-gray-700 hover:text-gray-900"
                            >
                                Zarejestruj
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <slot />
        </main>
    </div>
</template>
