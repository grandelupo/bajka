<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps({
    books: Array,
    recentTransactions: Array,
    recentCoinPurchases: Array,
    userCoins: Number,
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12 text-gray-900">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Coin Balance -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold mb-4">Your Coin Balance</h2>
                        <p class="text-4xl font-bold text-blue-600">{{ userCoins }} coins</p>
                        <Link
                            :href="route('coins.purchase')"
                            class="inline-block mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                        >
                            Purchase More Coins
                        </Link>
                    </div>
                </div>

                <!-- Books -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold mb-4">All Books</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <div
                                v-for="book in books"
                                :key="book.id"
                                class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200"
                            >
                                <img
                                    :src="book.cover_image"
                                    :alt="book.title"
                                    class="w-full h-48 object-cover"
                                />
                                <div class="p-4">
                                    <h3 class="text-xl font-semibold mb-2">{{ book.title }}</h3>
                                    <p class="text-gray-600 mb-4 line-clamp-2">{{ book.description }}</p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500">{{ book.total_pages }} pages</span>
                                        <Link
                                            :href="route('books.show', book.id)"
                                            class="text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            Start Reading
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold mb-4">Recent Page Purchases</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Book
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Page
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Coins Spent
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transaction in recentTransactions" :key="transaction.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ transaction.book.title }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            Page {{ transaction.page.page_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ transaction.coins_spent }} coins
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ new Date(transaction.created_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Coin Purchases -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold mb-4">Recent Coin Purchases</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="purchase in recentCoinPurchases" :key="purchase.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ purchase.amount }} coins
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            ${{ purchase.price }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800': purchase.status === 'completed',
                                                    'bg-yellow-100 text-yellow-800': purchase.status === 'pending',
                                                    'bg-red-100 text-red-800': purchase.status === 'failed'
                                                }"
                                            >
                                                {{ purchase.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ new Date(purchase.created_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
