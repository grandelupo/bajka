<template>
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Hero Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 text-gray-900">
                        <h1 class="text-4xl font-bold text-center mb-4">
                            Witaj w Czytniku Bajek
                        </h1>
                        <p class="text-xl text-center text-gray-600 mb-8">
                            Odkryj magiczną historię o smoku, który mieszka w starym forcie, i o przyjaźni, która pokonuje wszystkie przeszkody
                        </p>
                        <div class="flex justify-center space-x-4">
                            <Link
                                :href="route('pages.show', { book: advertisedBookId, page: 1 })"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                            >
                                Rozpocznij przygodę
                            </Link>
                            <Link
                                :href="route('register')"
                                class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300"
                            >
                                Zaloguj się
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Story Preview Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                            <div>
                                <h2 class="text-3xl font-bold mb-4">Magiczna Historia</h2>
                                <p class="text-gray-600 mb-4">
                                    W starym forcie mieszka smok, który stracił swój ogień. Ale to nie koniec historii - to dopiero początek niezwykłej przygody, w której dziecięca wyobraźnia i przyjaźń pokonują wszystkie przeszkody.
                                </p>
                                <p class="text-gray-600 mb-4">
                                    Poznaj Olgę, młodego detektywa z kolorowymi włosami, Igora, programistę w rycerskim swetrze, i ich niezwykłych przyjaciół: Kukiełkę, białą kotkę z wyobraźnią, i Apetyta, psa z misją.
                                </p>
                                <Link
                                    :href="route('pages.show', { book: advertisedBookId, page: 1 })"
                                    class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                                >
                                    Czytaj dalej
                                </Link>
                            </div>
                            <div class="relative aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden">
                                <img
                                    src="/images/fort-preview.png"
                                    alt="Stary fort z magicznym światłem"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Magiczna Przygoda</h3>
                            <p class="text-gray-600">
                                Poznaj historię smoka, który mieszka w starym forcie, i jego niezwykłych przyjaciół - Olgę, Igora i ich zwierzęta
                            </p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Przyjaźń i Wyobraźnia</h3>
                            <p class="text-gray-600">
                                Odkryj, jak dziecięca wyobraźnia i przyjaźń mogą zmienić świat, nawet gdy spotykają się z wyzwaniami
                            </p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">Interaktywne Czytanie</h3>
                            <p class="text-gray-600">
                                Doświadcz historii w nowy sposób dzięki interaktywnym elementom, filmom i narracji audio
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Characters Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h2 class="text-3xl font-bold mb-6 text-center">Poznaj Bohaterów</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="aspect-square bg-gray-100 rounded-full overflow-hidden mb-4">
                                    <img
                                        src="/images/olga.jpg"
                                        alt="Olga"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <h3 class="font-semibold mb-2">Olga</h3>
                                <p class="text-sm text-gray-600">Młoda detektyw z kolorowymi włosami i wielką wyobraźnią</p>
                            </div>
                            <div class="text-center">
                                <div class="aspect-square bg-gray-100 rounded-full overflow-hidden mb-4">
                                    <img
                                        src="/images/igor.jpg"
                                        alt="Igor"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <h3 class="font-semibold mb-2">Igor</h3>
                                <p class="text-sm text-gray-600">Programista w rycerskim swetrze z głową pełną pomysłów</p>
                            </div>
                            <div class="text-center">
                                <div class="aspect-square bg-gray-100 rounded-full overflow-hidden mb-4">
                                    <img
                                        src="/images/kukielka.png"
                                        alt="Kukiełka"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <h3 class="font-semibold mb-2">Kukiełka</h3>
                                <p class="text-sm text-gray-600">Biała kotka z wyobraźnią i talentem do przygód</p>
                            </div>
                            <div class="text-center">
                                <div class="aspect-square bg-gray-100 rounded-full overflow-hidden mb-4">
                                    <img
                                        src="/images/apetyt.png"
                                        alt="Apetyt"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <h3 class="font-semibold mb-2">Apetyt</h3>
                                <p class="text-sm text-gray-600">Pies z misją i niezwykłym nosem do przygód</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="bg-blue-600 text-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <h2 class="text-3xl font-bold mb-4">Gotowy na przygodę?</h2>
                        <p class="text-xl mb-6">Dołącz do Olgi, Igora i ich przyjaciół w tej magicznej historii</p>
                        <Link
                            :href="route('register')"
                            class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg hover:bg-gray-100"
                        >
                            Rozpocznij teraz
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const advertisedBookId = usePage().props.config.books.advertised_book_id;
</script>