<template>
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold mb-8">Kup monety</h1>

                        <!-- Coin Packages -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div
                                v-for="pkg in packages"
                                :key="pkg.id"
                                class="border rounded-lg p-6 hover:border-blue-500 cursor-pointer"
                                :class="{ 'border-blue-500': selectedPackage === pkg.id }"
                                @click="selectPackage(pkg)"
                            >
                                <h3 class="text-xl font-semibold mb-2">{{ pkg.name }}</h3>
                                <p class="text-3xl font-bold mb-2">{{ pkg.price }} zł</p>
                                <p class="text-gray-600">{{ pkg.coins }} monet</p>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div v-if="selectedPackage" class="mt-8">
                            <div id="payment-element" class="mb-4"></div>
                            <button
                                @click="handlePayment"
                                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                                :disabled="processing"
                            >
                                {{ processing ? 'Przetwarzanie...' : 'Kup monety' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    packages: Array,
    stripeKey: String,
    csrf_token: String,
});

const selectedPackage = ref(null);
const processing = ref(false);
let stripe = null;
let elements = null;

onMounted(async () => {
    if (!props.stripeKey) {
        console.error('Stripe key is missing');
        return;
    }
    stripe = await loadStripe(props.stripeKey);
});

const selectPackage = (pkg) => {
    selectedPackage.value = pkg.id;
    initializePayment();
};

const initializePayment = async () => {
    try {
        const response = await fetch('/transactions/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                package_id: selectedPackage.value,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to initialize payment');
        }

        const data = await response.json();
        
        if (!data.clientSecret) {
            throw new Error('Invalid response from server: missing client secret');
        }

        elements = stripe.elements({
            clientSecret: data.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#3B82F6',
                    colorBackground: '#ffffff',
                    colorText: '#1F2937',
                },
            },
        });

        // Clear any existing payment element
        const paymentElementContainer = document.getElementById('payment-element');
        if (paymentElementContainer) {
            paymentElementContainer.innerHTML = '';
        }

        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    } catch (error) {
        console.error('Error initializing payment:', error);
        alert('Failed to initialize payment. Please try again.');
    }
};

const handlePayment = async () => {
    if (!stripe || !elements) {
        alert('Payment system not initialized. Please try again.');
        return;
    }

    processing.value = true;

    try {
        const { error, paymentIntent } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: `${window.location.origin}/coins/purchase/confirm`,
            },
            redirect: 'if_required',
        });

        if (error) {
            console.error('Payment error:', error);
            alert(error.message || 'Payment failed. Please try again.');
        } else if (paymentIntent) {
            // Handle successful payment
            window.location.href = route('coins.purchase.confirm', {
                payment_intent: paymentIntent.id,
                package_id: selectedPackage.value,
            });
        }
    } catch (error) {
        console.error('Payment processing error:', error);
        alert('An unexpected error occurred. Please try again.');
    } finally {
        processing.value = false;
    }
};
</script>