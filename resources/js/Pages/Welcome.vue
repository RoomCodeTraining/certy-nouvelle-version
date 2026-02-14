<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import FlashNotifications from '@/Components/FlashNotifications.vue';

onMounted(() => {
    const els = document.querySelectorAll('.scroll-reveal');
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        },
        { rootMargin: '0px 0px -60px 0px', threshold: 0.05 }
    );
    els.forEach((el) => observer.observe(el));
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white text-slate-900">
        <FlashNotifications />

        <!-- Header (Nuxt style: minimal, nav + CTA) -->
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="text-xl font-bold text-slate-900 tracking-tight">Baaro</Link>
                <nav class="hidden sm:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Fonctionnalités</a>
                    <a href="#steps" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Comment ça marche</a>
                    <a href="#testimonials" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Témoignages</a>
                </nav>
                <div class="flex items-center gap-3">
                    <Link href="/login" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Connexion</Link>
                    <Link
                        href="/register"
                        class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors"
                    >
                        Essai gratuit
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <!-- Hero (Nuxt style: big title, accent word, 2 CTAs) -->
            <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-slate-50/80 to-white" aria-hidden="true" />
                <div class="scroll-reveal relative max-w-6xl mx-auto px-6 lg:px-8 text-center">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-slate-900 tracking-tight leading-[1.1]">
                        Archivage intelligent<br />
                        <span class="text-emerald-600">pour vos documents</span>
                    </h1>
                    <p class="mt-6 text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                        Centralisez vos PDF et Word. Posez des questions en langage naturel. L'assistant IA parcourt vos contenus et répond avec les sources.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            href="/register"
                            class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-500/25"
                        >
                            Commencer gratuitement
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                        <Link
                            href="#features"
                            class="inline-flex items-center justify-center gap-2 px-8 py-4 border border-slate-300 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            Découvrir
                        </Link>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Aucune carte bancaire requise</p>
                </div>
            </section>

            <!-- Features intro (Nuxt: "Revolutionize your experience") -->
            <section id="features" class="py-20 lg:py-28 scroll-mt-20">
                <div class="max-w-6xl mx-auto px-6 lg:px-8">
                    <div class="scroll-reveal max-w-3xl">
                        <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider">Fonctionnalités</p>
                        <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight">
                            Transformez votre façon de gérer les archives
                        </h2>
                        <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                            Une base documentaire centralisée et un assistant qui répond à vos questions en s'appuyant sur vos fichiers.
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="shrink-0 w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                </span>
                                <div>
                                    <span class="font-semibold text-slate-900">Import simple</span>
                                    <span class="text-slate-600"> — Glissez vos PDF et Word. L'extraction du texte est automatique.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="shrink-0 w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <span class="font-semibold text-slate-900">Questions en langage naturel</span>
                                    <span class="text-slate-600"> — Posez vos questions comme à un collègue. Réponses avec sources.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="shrink-0 w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </span>
                                <div>
                                    <span class="font-semibold text-slate-900">Par organisation</span>
                                    <span class="text-slate-600"> — Centralisez par cabinet, école ou ONG. Invitez votre équipe.</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 6 cards grid (Nuxt: "Take control") -->
            <section class="py-20 lg:py-28 bg-slate-50/50">
                <div class="max-w-6xl mx-auto px-6 lg:px-8">
                    <div class="scroll-reveal text-center max-w-2xl mx-auto mb-14">
                        <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider">Tout-en-un</p>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
                            Tout ce dont vous avez besoin
                        </h2>
                        <p class="mt-4 text-slate-600">
                            Import, recherche sémantique, assistant IA et centralisation en un seul outil.
                        </p>
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="0">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Import PDF & Word</h3>
                            <p class="mt-2 text-sm text-slate-600">Glissez-déposez ou importez en lot. Extraction du texte automatique.</p>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="50">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Recherche sémantique</h3>
                            <p class="mt-2 text-sm text-slate-600">Trouvez l'info par sens, pas seulement par mot-clé.</p>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="100">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Assistant IA</h3>
                            <p class="mt-2 text-sm text-slate-600">Posez des questions. Réponses basées sur vos documents, avec citations.</p>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="150">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Réponses sourcées</h3>
                            <p class="mt-2 text-sm text-slate-600">Chaque réponse pointe vers le document et l'extrait concerné.</p>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="200">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Organisations</h3>
                            <p class="mt-2 text-sm text-slate-600">Séparez cabinets, écoles, ONG. Chacun sa base documentaire.</p>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all" data-delay="250">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">Sans engagement</h3>
                            <p class="mt-2 text-sm text-slate-600">Essai gratuit, facturation au mois. Aucune carte requise pour démarrer.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3 steps (Nuxt: "Start in 3 steps") -->
            <section id="steps" class="py-20 lg:py-28 scroll-mt-20">
                <div class="max-w-6xl mx-auto px-6 lg:px-8">
                    <div class="scroll-reveal text-center max-w-2xl mx-auto mb-14">
                        <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider">Comment ça marche</p>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
                            En 3 étapes, sans prise de tête
                        </h2>
                        <p class="mt-4 text-slate-600">
                            De l'upload à la réponse en quelques clics.
                        </p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                        <div class="scroll-reveal relative text-center md:text-left" data-delay="0">
                            <div class="inline-flex w-14 h-14 rounded-2xl bg-emerald-600 text-white font-bold text-xl items-center justify-center shadow-lg shadow-emerald-500/30">1</div>
                            <h3 class="mt-5 text-xl font-semibold text-slate-900">Importez vos documents</h3>
                            <p class="mt-2 text-slate-600">
                                Glissez vos PDF et Word. L'extraction du texte est automatique, prête pour l'assistant.
                            </p>
                            <div class="hidden md:block absolute top-7 left-[calc(50%+2rem)] lg:left-[calc(50%+3rem)] w-full h-0.5 bg-slate-200 -z-10" aria-hidden="true" />
                        </div>
                        <div class="scroll-reveal relative text-center md:text-left" data-delay="80">
                            <div class="inline-flex w-14 h-14 rounded-2xl bg-emerald-600 text-white font-bold text-xl items-center justify-center shadow-lg shadow-emerald-500/30">2</div>
                            <h3 class="mt-5 text-xl font-semibold text-slate-900">Posez vos questions</h3>
                            <p class="mt-2 text-slate-600">
                                En français, comme à un collègue. L'assistant parcourt vos documents et cite ses sources.
                            </p>
                            <div class="hidden md:block absolute top-7 left-[calc(50%+2rem)] lg:left-[calc(50%+3rem)] w-full h-0.5 bg-slate-200 -z-10" aria-hidden="true" />
                        </div>
                        <div class="scroll-reveal relative text-center md:text-left" data-delay="160">
                            <div class="inline-flex w-14 h-14 rounded-2xl bg-emerald-600 text-white font-bold text-xl items-center justify-center shadow-lg shadow-emerald-500/30">3</div>
                            <h3 class="mt-5 text-xl font-semibold text-slate-900">Réutilisez les réponses</h3>
                            <p class="mt-2 text-slate-600">
                                Réponses sourcées, résumés, archives centralisées par organisation et par équipe.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dashboard preview -->
            <section class="py-12 lg:py-16">
                <div class="max-w-5xl mx-auto px-6 lg:px-8">
                    <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xl shadow-slate-200/50">
                        <div class="bg-slate-100 px-4 py-3 flex items-center gap-2 border-b border-slate-200">
                            <span class="w-3 h-3 rounded-full bg-red-400" />
                            <span class="w-3 h-3 rounded-full bg-amber-400" />
                            <span class="w-3 h-3 rounded-full bg-emerald-400" />
                            <span class="ml-4 text-slate-400 text-sm">app.baaro.digital</span>
                        </div>
                        <img src="/dashboard.png" alt="Tableau de bord Baaro" class="w-full h-auto" />
                    </div>
                </div>
            </section>

            <!-- Testimonials (Nuxt style: 3 cards, quote + name + role) -->
            <section id="testimonials" class="py-20 lg:py-28 bg-slate-50/50 scroll-mt-20">
                <div class="max-w-6xl mx-auto px-6 lg:px-8">
                    <div class="scroll-reveal text-center max-w-2xl mx-auto mb-14">
                        <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider">Témoignages</p>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
                            Ils nous font confiance
                        </h2>
                        <p class="mt-4 text-slate-600">
                            Cabinets, écoles et ONG en Afrique de l'Ouest.
                        </p>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-8">
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-delay="0">
                            <p class="text-slate-600 leading-relaxed">
                                « Retrouver une clause dans nos contrats prenait des heures. Maintenant : une question, la réponse en secondes. »
                            </p>
                            <div class="mt-5 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-sm font-bold">AK</span>
                                <div>
                                    <p class="font-semibold text-slate-900">Amadou Koné</p>
                                    <p class="text-sm text-slate-500">Cabinet Koné & Associés, Abidjan</p>
                                </div>
                            </div>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-delay="80">
                            <p class="text-slate-600 leading-relaxed">
                                « Les archives de l'école centralisées. Plus de fouilles dans les armoires pour un procès-verbal. »
                            </p>
                            <div class="mt-5 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-sm font-bold">MT</span>
                                <div>
                                    <p class="font-semibold text-slate-900">Mariama Touré</p>
                                    <p class="text-sm text-slate-500">Directrice, École Sainte-Marie, Dakar</p>
                                </div>
                            </div>
                        </div>
                        <div class="scroll-reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-delay="160">
                            <p class="text-slate-600 leading-relaxed">
                                « Rapports d'activité, conventions partenaires : tout à portée de main. L'assistant nous fait gagner un temps fou. »
                            </p>
                            <div class="mt-5 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-sm font-bold">KO</span>
                                <div>
                                    <p class="font-semibold text-slate-900">Koffi Ouedraogo</p>
                                    <p class="text-sm text-slate-500">Coordinateur, ONG Développement Sahel</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA final (Nuxt: "Ready to build...") -->
            <section class="py-20 lg:py-28">
                <div class="scroll-reveal max-w-4xl mx-auto px-6 lg:px-8 text-center">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight">
                        Prêt à simplifier vos archives ?
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Créez votre compte en 2 minutes. PDF, Word. Sans carte bancaire.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            href="/register"
                            class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-500/25"
                        >
                            Commencer gratuitement
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                        <Link href="/login" class="inline-flex items-center justify-center gap-2 px-8 py-4 border border-slate-300 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                            J'ai déjà un compte
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Footer (Nuxt: columns + copyright) -->
            <footer class="border-t border-slate-200 bg-slate-900 text-slate-300">
                <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
                        <div class="col-span-2 md:col-span-1">
                            <span class="font-bold text-white text-lg">Baaro</span>
                            <p class="mt-3 text-sm text-slate-400 leading-relaxed">
                                Archivage intelligent. Import, assistant IA, centralisation par organisation.
                            </p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm uppercase tracking-wider mb-4">Produit</h4>
                            <ul class="space-y-3 text-sm">
                                <li><a href="#features" class="hover:text-white transition-colors">Fonctionnalités</a></li>
                                <li><a href="#steps" class="hover:text-white transition-colors">Comment ça marche</a></li>
                                <li><span class="text-slate-500">PDF, Word</span></li>
                                <li><span class="text-slate-500">Assistant IA</span></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm uppercase tracking-wider mb-4">Compte</h4>
                            <ul class="space-y-3 text-sm">
                                <li><Link href="/register" class="hover:text-white transition-colors">Créer un compte</Link></li>
                                <li><Link href="/login" class="hover:text-white transition-colors">Connexion</Link></li>
                            </ul>
                        </div>
                    </div>
                    <div class="pt-8 border-t border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-slate-500">© {{ new Date().getFullYear() }} Baaro</p>
                        <p class="text-sm text-slate-500">Conçu pour les PME · Cabinet · École · ONG</p>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.scroll-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition:
        opacity 0.6s ease-out,
        transform 0.6s ease-out;
}

.scroll-reveal.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.scroll-reveal[data-delay='0'].is-visible { transition-delay: 0ms; }
.scroll-reveal[data-delay='50'].is-visible { transition-delay: 50ms; }
.scroll-reveal[data-delay='80'].is-visible { transition-delay: 80ms; }
.scroll-reveal[data-delay='100'].is-visible { transition-delay: 100ms; }
.scroll-reveal[data-delay='150'].is-visible { transition-delay: 150ms; }
.scroll-reveal[data-delay='160'].is-visible { transition-delay: 160ms; }
.scroll-reveal[data-delay='200'].is-visible { transition-delay: 200ms; }
.scroll-reveal[data-delay='250'].is-visible { transition-delay: 250ms; }

@media (prefers-reduced-motion: reduce) {
    .scroll-reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
