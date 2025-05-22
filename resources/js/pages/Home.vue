<template>
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Hero Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 text-gray-900">
                        <h1 class="text-4xl font-bold text-center mb-4">
                            Welcome to Bajka Reader
                        </h1>
                        <p class="text-xl text-center text-gray-600 mb-8">
                            Discover a new way to experience stories with interactive videos and audio narration
                        </p>
                        <div class="flex justify-center space-x-4">
                            <Link
                                :href="route('register')"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                            >
                                Get Started
                            </Link>
                            <Link
                                :href="route('login')"
                                class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300"
                            >
                                Sign In
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Interactive Videos</h3>
                            <p class="text-gray-600">
                                Experience stories come to life with embedded videos that enhance the reading experience
                            </p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Audio Narration</h3>
                            <p class="text-gray-600">
                                Listen to professional narration while reading, perfect for learning and entertainment
                            </p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Flexible Reading</h3>
                            <p class="text-gray-600">
                                Read on any device with responsive design and optimized layout for desktop and mobile
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script> 