<template>
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold mb-8">Purchase Coins</h1>

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
                                <p class="text-3xl font-bold mb-2">${{ pkg.price }}</p>
                                <p class="text-gray-600">{{ pkg.coins }} coins</p>
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
                                {{ processing ? 'Processing...' : 'Purchase Coins' }}
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
                'X-CSRF-TOKEN': props.csrf_token,
            },
            body: JSON.stringify({
                package_id: selectedPackage.value,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to create payment intent');
        }

        const { clientSecret } = await response.json();

        if (!stripe || !clientSecret) {
            throw new Error('Stripe or client secret is missing');
        }

        elements = stripe.elements({
            clientSecret,
            appearance: {
                theme: 'stripe',
            },
        });

        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    } catch (error) {
        console.error('Error initializing payment:', error);
    }
};

const handlePayment = async () => {
    if (!stripe || !elements) {
        return;
    }

    processing.value = true;

    try {
        const { error, paymentIntent } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: `${window.location.origin}/coins/purchase/confirm`,
            },
        });

        if (error) {
            console.error('Payment error:', error);
        } else if (paymentIntent) {
            // Handle successful payment
            window.location.href = route('coins.purchase.confirm', {
                payment_intent: paymentIntent.id,
                package_id: selectedPackage.value,
            });
        }
    } catch (error) {
        console.error('Payment processing error:', error);
    } finally {
        processing.value = false;
    }
};
</script> 