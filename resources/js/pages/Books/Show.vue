<template>
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg text-black">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold mb-4">{{ book.title }}</h1>
                        
                        <!-- Book Reader -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Left Page -->
                            <div class="relative">
                                <div v-if="currentPage" class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden">
                                    <img
                                        :src="currentPage.image_url"
                                        :alt="`Page ${currentPage.page_number}`"
                                        class="w-full h-full object-cover"
                                    />
                                    <video
                                        v-if="currentPage.video_url"
                                        :src="currentPage.video_url"
                                        class="absolute inset-0 w-full h-full object-cover"
                                        controls
                                        preload="none"
                                    ></video>
                                    <!-- Text Content -->
                                    <div class="absolute inset-0 bg-white bg-opacity-90 p-6 overflow-y-auto">
                                        <div class="prose max-w-none" v-html="formatContent(currentPage.content)"></div>
                                    </div>
                                </div>
                                <div v-else class="aspect-[3/4] bg-gray-100 rounded-lg flex items-center justify-center">
                                    <p class="text-gray-500">No page content available</p>
                                </div>
                            </div>

                            <!-- Right Page - Hidden on Mobile -->
                            <div class="relative hidden md:block">
                                <div v-if="nextPage" class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden">
                                    <img
                                        :src="nextPage.image_url"
                                        :alt="`Page ${nextPage.page_number}`"
                                        class="w-full h-full object-cover"
                                    />
                                    <video
                                        v-if="nextPage.video_url"
                                        :src="nextPage.video_url"
                                        class="absolute inset-0 w-full h-full object-cover"
                                        controls
                                        preload="none"
                                    ></video>
                                    <!-- Text Content -->
                                    <div class="absolute inset-0 bg-white bg-opacity-90 p-6 overflow-y-auto">
                                        <div class="prose max-w-none" v-html="formatContent(nextPage.content)"></div>
                                    </div>
                                </div>
                                <div v-else class="aspect-[3/4] bg-gray-100 rounded-lg flex items-center justify-center">
                                    <p class="text-gray-500">No more pages</p>
                                </div>
                            </div>
                        </div>

                        <!-- Audio Player -->
                        <div v-if="currentPage?.audio_url" class="mt-8">
                            <audio
                                ref="audioPlayer"
                                :src="currentPage.audio_url"
                                class="w-full"
                                controls
                                preload="none"
                            ></audio>
                        </div>

                        <!-- Navigation Controls -->
                        <div class="mt-8 flex justify-between items-center">
                            <button
                                v-if="prevPage"
                                @click="navigateToPage(prevPage.page_number)"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                            >
                                <span class="hidden md:inline">Previous Page</span>
                                <span class="md:hidden">←</span>
                            </button>
                            <div class="text-gray-600">
                                Page {{ currentPage?.page_number || 0 }} of {{ book.total_pages }}
                            </div>
                            <button
                                v-if="nextPage"
                                @click="navigateToPage(nextPage.page_number)"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                            >
                                <span class="hidden md:inline">Next Page</span>
                                <span class="md:hidden">→</span>
                            </button>
                        </div>

                        <!-- Coin Balance -->
                        <div class="mt-4 text-right text-gray-600">
                            Your Coins: {{ userCoins }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    book: Object,
    currentPageNumber: Number,
    accessConfig: Object,
    userCoins: Number,
});

const currentPage = ref(null);
const nextPage = ref(null);
const prevPage = ref(null);
const audioPlayer = ref(null);

const formatContent = (content) => {
    if (!content) return '';
    
    // Convert video links to embedded players with image preview
    const videoRegex = /\[video\](.*?)\[\/video\]/g;
    let formattedContent = content.replace(videoRegex, (match, filename) => {
        const videoId = `video-${Math.random().toString(36).substr(2, 9)}`;
        const imageUrl = `/images/${filename}.png`;
        const videoUrl = `/videos/${filename}.mp4`;
        
        return `<div class="my-4 relative">
            <img 
                :id="'img-' + '${videoId}'"
                src="${imageUrl}"
                class="w-full rounded-lg"
                alt="Video preview"
            />
            <video 
                :id="'${videoId}'"
                class="w-full rounded-lg absolute inset-0 transition-opacity duration-300"
                loop
                muted
                playsinline
                autoplay
                @loadeddata="handleVideoLoaded('${videoId}')"
            >
                <source src="${videoUrl}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>`;
    });

    // Convert regular URLs to clickable links
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    formattedContent = formattedContent.replace(urlRegex, (url) => {
        return `<a href="${url}" target="_blank" class="text-blue-600 hover:text-blue-800">${url}</a>`;
    });

    // Convert line breaks to paragraphs
    formattedContent = formattedContent.split('\n\n').map(paragraph => {
        return `<p class="mb-4">${paragraph}</p>`;
    }).join('');

    return formattedContent;
};

const handleVideoLoaded = (videoId) => {
    console.log('handleVideoLoaded', videoId);
    const video = document.getElementById(videoId);
    const image = document.getElementById(`img-${videoId}`);
    
    if (video && image) {
        // Start playing the video
        video.play();
        
        // Fade in the video and fade out the image
        video.style.opacity = '1';
        image.style.opacity = '0';
        
        // Remove the image after the transition
        setTimeout(() => {
            image.remove();
        }, 300);
    }
};

const canAccessPage = (pageNumber) => {
    if (pageNumber <= props.accessConfig.freePages) {
        return true;
    }

    if (!props.accessConfig.isAuthenticated) {
        return false;
    }

    if (pageNumber <= props.accessConfig.registeredFreePages) {
        return true;
    }

    // Check if the page is in purchased pages
    const page = props.book.pages.find(p => p.page_number === pageNumber);
    return page && props.accessConfig.purchasedPageIds.includes(page.id);
};

const navigateToPage = (pageNumber) => {
    // Check if we're on mobile (screen width < 768px)
    const isMobile = window.innerWidth < 768;

    if (!isMobile) {
        // Desktop navigation logic (two-page spread)
        if (pageNumber % 2 === 0 && pageNumber < currentPage.value.page_number) {
            pageNumber -= 1;
        }
        if (pageNumber % 2 === 0 && pageNumber > currentPage.value.page_number) {
            pageNumber += 1;
        }
        if (pageNumber <= 0) {
            pageNumber = 1;
        }
    } else {
        // Mobile navigation logic (single page)
        if (pageNumber <= 0) {
            pageNumber = 1;
        }
        if (pageNumber > props.book.total_pages) {
            pageNumber = props.book.total_pages;
        }
    }

    if (!canAccessPage(pageNumber)) {
        if (pageNumber > props.accessConfig.registeredFreePages) {
            router.visit(route('coins.purchase'));
        } else {
            router.visit(route('login'));
        }
        return;
    }

    // Find the page by page number
    const page = props.book.pages.find(p => p.page_number === pageNumber);
    if (!page) {
        console.error('Page not found:', pageNumber);
        return;
    }

    // Update the current page state immediately
    const currentIndex = props.book.pages.findIndex(p => p.page_number === pageNumber);
    currentPage.value = props.book.pages[currentIndex];
    
    if (!isMobile) {
        // Desktop: Set next and previous pages for two-page spread
        nextPage.value = props.book.pages[currentIndex + 1] || null;
        prevPage.value = props.book.pages[currentIndex - 1] || null;
    } else {
        // Mobile: Set next and previous pages for single-page view
        nextPage.value = props.book.pages[currentIndex + 1] || null;
        prevPage.value = props.book.pages[currentIndex - 1] || null;
    }

    // Update the URL without a full page reload
    history.pushState(
        {},
        '',
        route('pages.show', { book: props.book.id, page: page.id })
    );
};

// Add window resize handler to update navigation when switching between mobile and desktop
onMounted(() => {
    if (props.book.pages.length > 0) {
        // Find the current page by page number
        const currentIndex = props.book.pages.findIndex(p => p.page_number === props.currentPageNumber);
        currentPage.value = props.book.pages[currentIndex] || props.book.pages[0];
        
        // Set next and previous pages
        nextPage.value = props.book.pages[currentIndex + 1] || null;
        prevPage.value = props.book.pages[currentIndex - 1] || null;
    }

    // Add resize handler
    window.addEventListener('resize', handleResize);
});

// Clean up event listener
onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

// Handle window resize
const handleResize = () => {
    const isMobile = window.innerWidth < 768;
    const currentPageNumber = currentPage.value?.page_number || 1;
    
    // If switching to mobile, ensure we're on a valid page
    if (isMobile && currentPageNumber % 2 === 0) {
        navigateToPage(currentPageNumber - 1);
    }
};

watch(() => props.book.pages, (newPages) => {
    if (newPages.length > 0) {
        // Find the current page by page number
        const currentIndex = newPages.findIndex(p => p.page_number === props.currentPageNumber);
        
        // Set current page
        currentPage.value = newPages[currentIndex] || newPages[0];
        
        // Set next page
        nextPage.value = newPages[currentIndex + 1] || null;
        
        // Set previous page
        prevPage.value = newPages[currentIndex - 1] || null;
    }
}, { immediate: true });
</script> 