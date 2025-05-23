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
                                <span class="hidden md:inline">
                                    Next Page
                                    <span v-if="willCostCoins(nextPageNumber())" class="ml-2 text-yellow-300">
                                        ({{ calculateTotalCost(nextPageNumber()) }} coins)
                                    </span>
                                </span>
                                <span class="md:hidden">
                                    →
                                    <span v-if="willCostCoins(nextPageNumber())" class="ml-2 text-yellow-300">
                                        ({{ calculateTotalCost(nextPageNumber()) }})
                                    </span>
                                </span>
                            </button>
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

// Debug utility
const debug = {
    log: (message, data = null) => {
        if (import.meta.env.DEV) {
            console.log(`[DEBUG] ${message}`, data || '');
        }
    },
    error: (message, error = null) => {
        if (import.meta.env.DEV) {
            console.error(`[ERROR] ${message}`, error || '');
        }
    },
    warn: (message, data = null) => {
        if (import.meta.env.DEV) {
            console.warn(`[WARN] ${message}`, data || '');
        }
    }
};

const props = defineProps({
    book: Object,
    currentPageNumber: Number,
    accessConfig: Object,
    userCoins: Number,
    csrf_token: String,
});

// Debug props changes
watch(() => props, (newProps) => {
    debug.log('Props updated:', {
        currentPageNumber: newProps.currentPageNumber,
        userCoins: newProps.userCoins,
        accessConfig: newProps.accessConfig,
        hasCsrfToken: !!newProps.csrf_token
    });
}, { deep: true });

const currentPage = ref(null);
const nextPage = ref(null);
const prevPage = ref(null);
const audioPlayer = ref(null);

// Debug page state changes
watch([currentPage, nextPage, prevPage], ([newCurrent, newNext, newPrev]) => {
    debug.log('Page state updated:', {
        current: newCurrent?.page_number,
        next: newNext?.page_number,
        prev: newPrev?.page_number
    });
});

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

const nextPageNumber = () => {
    const isMobile = window.innerWidth < 768;
    const currentPageNum = currentPage.value.page_number;
    
    if (isMobile) {
        // On mobile, just increment by 1
        return currentPageNum + 1;
    } else {
        // On desktop, increment by 2 for two-page spread
        return currentPageNum + 2;
    }
};

const willCostCoins = (pageNumber) => {
    // If page is within free pages, no cost
    if (pageNumber <= props.accessConfig.registeredFreePages) {
        return false;
    }

    // Find the page by page number
    const page = props.book.pages.find(p => p.page_number === pageNumber);
    
    // If page doesn't exist or has already been purchased, no cost
    if (!page || props.accessConfig.purchasedPageIds.includes(page.id)) {
        return false;
    }

    // Page exists, is beyond free pages, and hasn't been purchased
    return true;
};

const calculateTotalCost = (pageNumber) => {

    // For desktop view, calculate cost for both pages
    const currentPage = props.book.pages.find(p => p.page_number === pageNumber);
    const nextPage = props.book.pages.find(p => p.page_number === pageNumber + 1);
    
    let totalCost = 0;

    // Add cost for current page if it needs to be purchased
    if (currentPage && !props.accessConfig.purchasedPageIds.includes(currentPage.id)) {
        totalCost += props.book.price_per_page;
    }

    // Add cost for next page if it exists and needs to be purchased
    if (nextPage && !props.accessConfig.purchasedPageIds.includes(nextPage.id)) {
        totalCost += props.book.price_per_page;
    }

    return totalCost;
};

const navigateToPage = async (pageNumber) => {
    debug.log('Navigating to page:', {
        pageNumber,
        hasCsrfToken: !!props.csrf_token
    });
    
    // Check if we're on mobile (screen width < 768px)
    const isMobile = window.innerWidth < 768;
    debug.log('Is mobile:', isMobile);

    if (!isMobile) {
        // Desktop navigation logic (two-page spread)
        if (pageNumber % 2 === 0 && pageNumber < currentPage.value.page_number) {
            pageNumber -= 1;
            debug.log('Adjusted page number for desktop:', pageNumber);
        }
        if (pageNumber % 2 === 0 && pageNumber > currentPage.value.page_number) {
            pageNumber += 1;
            debug.log('Adjusted page number for desktop:', pageNumber);
        }
        if (pageNumber <= 0) {
            pageNumber = 1;
            debug.log('Adjusted page number to minimum:', pageNumber);
        }
    } else {
        // Mobile navigation logic (single page)
        if (pageNumber <= 0) {
            pageNumber = 1;
            debug.log('Adjusted page number to minimum:', pageNumber);
        }
        if (pageNumber > props.book.total_pages) {
            pageNumber = props.book.total_pages;
            debug.log('Adjusted page number to maximum:', pageNumber);
        }
    }

    debug.log('Access check:', {
        pageNumber,
        registeredFreePages: props.accessConfig.registeredFreePages,
        canAccess: canAccessPage(pageNumber)
    });

    if (!canAccessPage(pageNumber)) {
        if (pageNumber > props.accessConfig.registeredFreePages) {
            debug.log('Page requires purchase:', {
                pageNumber,
                registeredFreePages: props.accessConfig.registeredFreePages,
                canAccess: canAccessPage(pageNumber),
                userCoins: props.userCoins,
                bookPricePerPage: props.book.price_per_page,
                hasCsrfToken: !!props.csrf_token
            });

            // Calculate total cost (both pages in desktop view)
            const totalCost = pageNumber % 2 === 1 && pageNumber < props.book.total_pages
                ? props.book.price_per_page * 2
                : props.book.price_per_page;

            // Check if user has enough coins
            if (props.userCoins >= totalCost) {
                try {
                    debug.log('Attempting to purchase page(s) with CSRF token:', !!props.csrf_token);
                    // Make API call to purchase the page(s)
                    const response = await fetch('/api/transactions/purchase-page', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.csrf_token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            book_id: props.book.id,
                            page_number: pageNumber,
                            purchase_next: pageNumber % 2 === 1 && pageNumber < props.book.total_pages
                        }),
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        debug.error('Failed to purchase page:', {
                            status: response.status,
                            error: errorData,
                            hasCsrfToken: !!props.csrf_token
                        });
                        throw new Error('Failed to purchase page');
                    }

                    const result = await response.json();
                    debug.log('Page(s) purchased successfully:', result);
                    
                    // Update the purchased pages list
                    props.accessConfig.purchasedPageIds.push(...result.purchased_page_ids);
                    
                    // Update user's coin balance
                    props.userCoins -= totalCost;

                    // Update the global user state
                    const page = usePage();
                    page.props.auth.user.coins = result.remaining_coins;
                } catch (error) {
                    debug.error('Error purchasing page:', error);
                    router.visit(route('coins.purchase'));
                    return;
                }
            } else {
                debug.warn('Not enough coins for purchase');
                router.visit(route('coins.purchase'));
                return;
            }
        } else {
            debug.warn('User not authenticated');
            router.visit(route('login'));
            return;
        }
    }

    // Find the page by page number
    const page = props.book.pages.find(p => p.page_number === pageNumber);
    if (!page) {
        debug.error('Page not found:', pageNumber);
        return;
    }

    debug.log('Updating page state');
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

    debug.log('Navigation complete:', {
        currentPage: currentPage.value?.page_number,
        nextPage: nextPage.value?.page_number,
        prevPage: prevPage.value?.page_number
    });

    // Update the URL without a full page reload
    history.pushState(
        {},
        '',
        route('pages.show', { book: props.book.id, page: page.id })
    );
};

// Add window resize handler to update navigation when switching between mobile and desktop
onMounted(() => {
    debug.log('Component mounted');
    if (props.book.pages.length > 0) {
        // Find the current page by page number
        const currentIndex = props.book.pages.findIndex(p => p.page_number === props.currentPageNumber);
        currentPage.value = props.book.pages[currentIndex] || props.book.pages[0];
        
        // Set next and previous pages
        nextPage.value = props.book.pages[currentIndex + 1] || null;
        prevPage.value = props.book.pages[currentIndex - 1] || null;

        debug.log('Initial page state:', {
            currentPage: currentPage.value?.page_number,
            nextPage: nextPage.value?.page_number,
            prevPage: prevPage.value?.page_number
        });
    }

    // Add resize handler
    window.addEventListener('resize', handleResize);
});

// Clean up event listener
onUnmounted(() => {
    debug.log('Component unmounting');
    window.removeEventListener('resize', handleResize);
});

// Handle window resize
const handleResize = () => {
    const isMobile = window.innerWidth < 768;
    debug.log('Window resized:', { isMobile, width: window.innerWidth });
    
    // If switching to mobile, ensure we're on a valid page
    if (isMobile && currentPage.value?.page_number % 2 === 0) {
        navigateToPage(currentPage.value.page_number - 1);
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