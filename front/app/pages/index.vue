<template>
  <div class="min-h-screen bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 selection:bg-primary-500 selection:text-white">
    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-white/85 dark:bg-gray-950/85 border-b border-gray-200/80 dark:border-gray-800/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white shadow-md shadow-primary-500/20 group-hover:scale-105 transition-transform duration-200">
            <UIcon name="i-heroicons-command-line" class="w-6 h-6" />
          </div>
          <div class="flex flex-col">
            <span class="font-bold text-lg leading-tight tracking-tight text-gray-900 dark:text-white">Project Manager</span>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-primary-600 dark:text-primary-400">DevOps & Cloud Orchestrator</span>
          </div>
        </NuxtLink>

        <!-- Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600 dark:text-gray-300">
          <a href="#features" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Fonctionnalités</a>
          <a href="#architecture" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Architecture</a>
          <a href="#security" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Sécurité</a>
          <a href="/docs" target="_blank" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center gap-1">
            Documentation API
            <UIcon name="i-heroicons-arrow-top-right-on-square" class="w-4 h-4" />
          </a>
          <NuxtLink to="/guide" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center gap-1">
            Guide
          </NuxtLink>
          <NuxtLink to="/changelog" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center gap-1">
            Changelog
          </NuxtLink>
        </nav>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
          <UButton
            to="/login"
            color="primary"
            variant="solid"
            label="Connexion"
            icon="i-heroicons-arrow-right-on-rectangle"
            class="font-semibold shadow-sm"
          />
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-28">
      <!-- Background Ambient Glow -->
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[450px] bg-gradient-to-tr from-primary-500/20 to-emerald-400/10 blur-3xl rounded-full pointer-events-none -z-10" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-950/70 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/60 shadow-xs">
          <UIcon name="i-heroicons-sparkles" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
          <span>Plateforme nouvelle génération pour vos projets & serveurs</span>
        </div>

        <!-- Main Headline -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-white max-w-4xl mx-auto leading-tight">
          Pilotez vos projets, vos équipes et vos serveurs
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-emerald-500 dark:from-primary-400 dark:to-emerald-300">
            au même endroit
          </span>
        </h1>

        <!-- Subtitle -->
        <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
          Centralisez la gestion de vos organisations, orchestrez vos environnements d'infrastructure et accélérez vos déploiements avec une interface moderne et sécurisée.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <UButton
            to="/login"
            size="lg"
            color="primary"
            variant="solid"
            label="Accéder à l'application"
            icon="i-heroicons-arrow-right"
            trailing
            class="w-full sm:w-auto font-semibold px-8 py-3.5 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 transition-all"
          />
          <UButton
            to="/docs"
            target="_blank"
            size="lg"
            variant="outline"
            color="neutral"
            label="Explorer l'API Swagger"
            icon="i-heroicons-code-bracket"
            class="w-full sm:w-auto font-semibold px-6 py-3.5"
          />
        </div>

        <!-- Hero Preview Mockup -->
        <div class="pt-10 max-w-5xl mx-auto">
          <div class="relative rounded-2xl p-2 sm:p-4 bg-gradient-to-b from-gray-200/60 to-gray-100/30 dark:from-gray-800/60 dark:to-gray-900/40 border border-gray-200/80 dark:border-gray-800 shadow-2xl backdrop-blur-xs">
            <div class="rounded-xl overflow-hidden bg-white dark:bg-gray-900 border border-gray-200/60 dark:border-gray-800/80 text-left">
              <!-- Mock Window Bar -->
              <div class="px-4 py-3 bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 rounded-full bg-red-400/80" />
                  <div class="w-3 h-3 rounded-full bg-amber-400/80" />
                  <div class="w-3 h-3 rounded-full bg-green-400/80" />
                  <span class="ml-2 text-xs text-gray-500 font-mono">project-manager.internal/dashboard</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse" />
                    En ligne
                  </span>
                </div>
              </div>

              <!-- Mock Content Body -->
              <div class="p-4 sm:p-6 space-y-6">
                <!-- Mini Stats in Mockup -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div class="p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Projets supervisés</p>
                    <p class="text-xl sm:text-2xl font-bold mt-1 text-gray-900 dark:text-white">18</p>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Tous opérationnels</p>
                  </div>
                  <div class="p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Serveurs connectés</p>
                    <p class="text-xl sm:text-2xl font-bold mt-1 text-gray-900 dark:text-white">12</p>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">99.98% disponibilité</p>
                  </div>
                  <div class="p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Organisations</p>
                    <p class="text-xl sm:text-2xl font-bold mt-1 text-gray-900 dark:text-white">5</p>
                    <p class="text-[11px] text-primary-600 dark:text-primary-400 mt-0.5 font-medium">Espaces isolés</p>
                  </div>
                  <div class="p-3 sm:p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Authentification</p>
                    <p class="text-xl sm:text-2xl font-bold mt-1 text-primary-600">LDAP & SSO</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-medium">Sécurisé</p>
                  </div>
                </div>

                <!-- Mockup List of Entities -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                  <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/60 font-semibold text-xs text-gray-700 dark:text-gray-300 flex justify-between">
                    <span>Derniers projets & serveurs actifs</span>
                    <span class="text-primary-600 dark:text-primary-400 cursor-pointer">Voir tout</span>
                  </div>
                  <div class="divide-y divide-gray-100 dark:divide-gray-800 text-xs sm:text-sm">
                    <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/60 dark:hover:bg-gray-800/30">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center font-bold">
                          PM
                        </div>
                        <div>
                          <p class="font-semibold text-gray-900 dark:text-white">Plateforme E-Commerce Production</p>
                          <p class="text-gray-500 text-xs">Organisation: RetailCorp • 3 serveurs</p>
                        </div>
                      </div>
                      <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300">
                        Déployé
                      </span>
                    </div>

                    <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/60 dark:hover:bg-gray-800/30">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center font-bold">
                          API
                        </div>
                        <div>
                          <p class="font-semibold text-gray-900 dark:text-white">Microservice Auth & Gateway</p>
                          <p class="text-gray-500 text-xs">Organisation: TechCore • 2 serveurs</p>
                        </div>
                      </div>
                      <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300">
                        Synchronisé
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Metrics Ribbon -->
    <section class="border-y border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/50 py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div>
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">100%</p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Multi-Tenant & Cloisonné</p>
          </div>
          <div>
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">&lt; 50ms</p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Latence de réponse API</p>
          </div>
          <div>
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">OpenAPI</p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Standards REST & GraphQL</p>
          </div>
          <div>
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">LDAP & SSO</p>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">Prêt pour l'entreprise</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 md:py-28">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
        <div class="text-center max-w-3xl mx-auto space-y-4">
          <h2 class="text-xs font-bold tracking-widest uppercase text-primary-600 dark:text-primary-400">
            Fonctionnalités Clés
          </h2>
          <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
            Tout ce dont vous avez besoin pour maîtriser vos environnements
          </h3>
          <p class="text-gray-600 dark:text-gray-400 text-base sm:text-lg">
            Découvrez une suite d'outils puissants conçus pour les développeurs, lead techs et administrateurs d'infrastructures.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950/70 text-primary-600 dark:text-primary-400 flex items-center justify-center">
              <UIcon name="i-heroicons-user-group" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Multi-Organisations & Équipes</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Structurez vos équipes en organisations distinctes avec gestion des rôles (Admin, Utilisateur) et isolation stricte des données et projets.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <UIcon name="i-heroicons-server" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Inventaire des Serveurs</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Associez vos machines virtuelles, serveurs physiques ou instances cloud à vos projets par type de serveur et supervisez leur état.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <UIcon name="i-heroicons-shield-check" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Sécurité & Annuaire LDAP</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Connectez en toute transparence votre annuaire d'entreprise LDAP pour centraliser les comptes utilisateurs tout en assurant une authentification forte.
            </p>
          </div>

          <!-- Feature 4 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center">
              <UIcon name="i-heroicons-code-bracket" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">API Platform Native</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Bénéficiez d'une API complète au standard Hydra/JSON-LD et OpenAPI, documentée et facilement automatisable avec vos scripts ou bots.
            </p>
          </div>

          <!-- Feature 5 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <UIcon name="i-heroicons-cube" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Architecture Cloud-Native</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Pensée pour les déploiements conteneurisés Docker & Kubernetes avec FrankenPHP et Caddy pour des performances exceptionnelles.
            </p>
          </div>

          <!-- Feature 6 -->
          <div class="p-8 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-primary-500/50 transition-all space-y-4">
            <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 flex items-center justify-center">
              <UIcon name="i-heroicons-bolt" class="w-6 h-6" />
            </div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Temps Réel avec Mercure</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              Soyez notifié instantanément des mises à jour, déploiements et modifications d'état grâce au protocole Server-Sent Events intégré.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Architecture Section -->
    <section id="architecture" class="py-20 bg-gray-50 dark:bg-gray-900/60 border-t border-gray-200 dark:border-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-3xl mx-auto space-y-4">
          <h2 class="text-xs font-bold tracking-widest uppercase text-primary-600 dark:text-primary-400">
            Architecture Moderne
          </h2>
          <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white">
            Propulsé par les meilleures technologies open-source
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            Une stack technique robuste, maintenable et rapide conçue pour durer.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 rounded-xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 space-y-3">
            <span class="text-xs font-bold px-2.5 py-1 rounded bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300">Backend API</span>
            <h4 class="text-lg font-bold text-gray-900 dark:text-white">Symfony 7 & API Platform</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Serveur PHP haute performance avec FrankenPHP, ORM Doctrine et documentation Swagger interactive intégrée.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 space-y-3">
            <span class="text-xs font-bold px-2.5 py-1 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">Frontend UI</span>
            <h4 class="text-lg font-bold text-gray-900 dark:text-white">Nuxt 4 & Nuxt UI</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Interface réactive basée sur Vue 3, Tailwind CSS et des composants d'une grande fluidité visuelle.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 space-y-3">
            <span class="text-xs font-bold px-2.5 py-1 rounded bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">Données & Hub</span>
            <h4 class="text-lg font-bold text-gray-900 dark:text-white">PostgreSQL & Mercure</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Persistance relationnelle transactionnelle et diffusion d'événements en direct vers vos clients web.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Security Section -->
    <section id="security" class="py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-br from-gray-900 via-gray-900 to-gray-950 text-white p-8 sm:p-14 border border-gray-800 shadow-2xl relative overflow-hidden">
          <div class="absolute right-0 bottom-0 translate-x-12 translate-y-12 w-96 h-96 bg-primary-600/10 rounded-full blur-3xl pointer-events-none" />

          <div class="max-w-2xl space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-primary-950/80 text-primary-400 border border-primary-800/60">
              <UIcon name="i-heroicons-shield-check" class="w-4 h-4" />
              <span>Normes et sécurité d'entreprise</span>
            </div>

            <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
              La sécurité au cœur de chaque couche
            </h3>

            <p class="text-gray-300 leading-relaxed text-base sm:text-lg">
              Protection contre les attaques courantes, hashage de mots de passe conforme aux derniers standards, gestion fine des privilèges RBAC et prise en charge des annuaires d'entreprise.
            </p>

            <ul class="space-y-3 pt-2 text-sm text-gray-300">
              <li class="flex items-center gap-3">
                <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-primary-400 shrink-0" />
                <span>Synchronisation et authentification LDAP configurable</span>
              </li>
              <li class="flex items-center gap-3">
                <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-primary-400 shrink-0" />
                <span>Sécurité des échanges avec HTTPS et certificats automatisés</span>
              </li>
              <li class="flex items-center gap-3">
                <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-primary-400 shrink-0" />
                <span>Verrou d'installation et contrôle strict des accès administrateur</span>
              </li>
            </ul>

            <div class="pt-4">
              <UButton
                to="/login"
                color="primary"
                size="lg"
                label="Se connecter à la plateforme"
                icon="i-heroicons-arrow-right"
                class="font-semibold px-6 py-3"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Bottom Call To Action Banner -->
    <section class="py-16 bg-primary-50 dark:bg-primary-950/40 border-t border-primary-200/60 dark:border-primary-900/40 text-center">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white">
          Prêt à démarrer avec Project Manager ?
        </h3>
        <p class="text-gray-600 dark:text-gray-300 text-lg max-w-xl mx-auto">
          Accédez dès maintenant à votre tableau de bord pour organiser vos équipes et administrer vos infrastructures.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
          <UButton
            to="/login"
            size="lg"
            color="primary"
            variant="solid"
            label="Accéder au formulaire de connexion"
            icon="i-heroicons-arrow-right"
            trailing
            class="font-semibold px-8 py-3.5 shadow-md"
          />
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white">
            <UIcon name="i-heroicons-command-line" class="w-5 h-5" />
          </div>
          <span class="font-bold text-gray-900 dark:text-white">Project Manager</span>
        </div>

        <div class="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
          <NuxtLink to="/login" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Connexion</NuxtLink>
          <a href="/docs" target="_blank" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentation API</a>
          <a href="/admin" target="_blank" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">API Admin</a>
          <NuxtLink to="/setup" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Configuration</NuxtLink>
        </div>

        <p class="text-xs text-gray-400 dark:text-gray-500">
          © 2026 Project Manager. Tous droits réservés.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
useHead({
  title: 'Project Manager - Orchestration de projets et d\'infrastructures',
  meta: [
    {
      name: 'description',
      content: 'Plateforme moderne de gestion de projets, serveurs et organisations pour les équipes techniques.'
    }
  ]
});
</script>
