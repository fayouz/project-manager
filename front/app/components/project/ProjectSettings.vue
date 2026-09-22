<template>
  <div class="flex flex-col md:flex-row gap-6 w-full items-start">
    <!-- Menu vertical (Sidebar des Paramètres) -->
    <aside class="w-full md:w-64 shrink-0">
      <UCard :ui="{ body: 'p-3 space-y-4' }">
        <!-- Section Projet -->
        <div>
          <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-1.5">
            Projet
          </p>
          <div class="space-y-0.5">
            <UButton
              :variant="activeSubTab === 'general' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'general' ? 'primary' : 'neutral'"
              size="sm"
              icon="i-heroicons-information-circle"
              class="w-full justify-start text-xs font-medium"
              label="Informations générales"
              @click="selectSubTab('general')"
            />
          </div>
        </div>

        <!-- Section Équipe & Membres -->
        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800">
          <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-1.5">
            Équipe & Membres
          </p>
          <div class="space-y-0.5">
            <UButton
              :variant="activeSubTab === 'members' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'members' ? 'primary' : 'neutral'"
              size="sm"
              icon="i-heroicons-users"
              class="w-full justify-start text-xs font-medium"
              label="Membres"
              @click="selectSubTab('members')"
            />
            <UButton
              :variant="activeSubTab === 'teams' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'teams' ? 'primary' : 'neutral'"
              size="sm"
              icon="i-heroicons-user-group"
              class="w-full justify-start text-xs font-medium"
              label="Équipes"
              @click="selectSubTab('teams')"
            />
          </div>
        </div>

        <!-- Section Services DevOps & Intégrations -->
        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800">
          <div class="flex items-center justify-between px-2 mb-1.5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
              Intégrations
            </p>
            <UBadge color="neutral" variant="subtle" size="xs" class="text-[10px]">
              {{ projectIntegrations.length }}
            </UBadge>
          </div>

          <div class="space-y-0.5">
            <!-- Vue d'ensemble de toutes les intégrations -->
            <UButton
              :variant="activeSubTab === 'integrations' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'integrations' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('integrations')"
            >
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-squares-plus" class="w-4 h-4 text-primary-500" />
                <span>Vue d'ensemble</span>
              </div>
              <UBadge
                v-if="projectIntegrations.length > 0"
                :color="activeSubTab === 'integrations' ? 'neutral' : 'primary'"
                variant="subtle"
                size="xs"
                class="text-[10px]"
              >
                {{ projectIntegrations.length }}
              </UBadge>
            </UButton>

            <!-- Gitea -->
            <UButton
              :variant="activeSubTab === 'gitea' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'gitea' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('gitea')"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon name="i-heroicons-code-bracket" class="w-4 h-4 text-amber-500 shrink-0" />
                <span class="truncate">Gitea</span>
              </div>
              <UBadge
                :color="giteaPi ? 'warning' : 'neutral'"
                variant="subtle"
                size="xs"
                class="text-[10px] shrink-0"
              >
                {{ giteaPi ? 'Lié' : 'Non lié' }}
              </UBadge>
            </UButton>

            <!-- SonarQube -->
            <UButton
              :variant="activeSubTab === 'sonarqube' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'sonarqube' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('sonarqube')"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon name="i-heroicons-shield-check" class="w-4 h-4 text-blue-500 shrink-0" />
                <span class="truncate">SonarQube</span>
              </div>
              <UBadge
                :color="sonarPi ? 'primary' : 'neutral'"
                variant="subtle"
                size="xs"
                class="text-[10px] shrink-0"
              >
                {{ sonarPi ? 'Lié' : 'Non lié' }}
              </UBadge>
            </UButton>

            <!-- Mantis BT -->
            <UButton
              :variant="activeSubTab === 'mantis' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'mantis' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('mantis')"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon name="i-heroicons-bug-ant" class="w-4 h-4 text-emerald-500 shrink-0" />
                <span class="truncate">Mantis BT</span>
              </div>
              <UBadge
                :color="mantisPi ? 'success' : 'neutral'"
                variant="subtle"
                size="xs"
                class="text-[10px] shrink-0"
              >
                {{ mantisPi ? 'Lié' : 'Non lié' }}
              </UBadge>
            </UButton>

            <!-- Jenkins CI -->
            <UButton
              :variant="activeSubTab === 'jenkins' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'jenkins' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('jenkins')"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-4 h-4 text-sky-500 shrink-0" />
                <span class="truncate">Jenkins CI</span>
              </div>
              <UBadge
                :color="jenkinsPi ? 'info' : 'neutral'"
                variant="subtle"
                size="xs"
                class="text-[10px] shrink-0"
              >
                {{ jenkinsPi ? 'Lié' : 'Non lié' }}
              </UBadge>
            </UButton>

            <!-- Nexus Repository -->
            <UButton
              :variant="activeSubTab === 'nexus' ? 'solid' : 'ghost'"
              :color="activeSubTab === 'nexus' ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab('nexus')"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon name="i-heroicons-cube" class="w-4 h-4 text-teal-500 shrink-0" />
                <span class="truncate">Nexus Repository</span>
              </div>
              <UBadge
                :color="nexusPi ? 'info' : 'neutral'"
                variant="subtle"
                size="xs"
                class="text-[10px] shrink-0"
              >
                {{ nexusPi ? 'Lié' : 'Non lié' }}
              </UBadge>
            </UButton>

            <!-- Autres intégrations personnalisées -->
            <UButton
              v-for="other in otherIntegrations"
              :key="other['@id'] || other.id"
              :variant="activeSubTab === `custom_${other.id}` ? 'solid' : 'ghost'"
              :color="activeSubTab === `custom_${other.id}` ? 'primary' : 'neutral'"
              size="sm"
              class="w-full justify-between text-xs font-medium"
              @click="selectSubTab(`custom_${other.id}`)"
            >
              <div class="flex items-center gap-2 truncate">
                <UIcon :name="getTypeIcon(getIntegrationType(other))" class="w-4 h-4 text-primary-500 shrink-0" />
                <span class="truncate">{{ getIntegrationName(other) }}</span>
              </div>
              <UBadge color="neutral" variant="subtle" size="xs" class="text-[10px] shrink-0">
                Lié
              </UBadge>
            </UButton>
          </div>

          <!-- Bouton d'ajout rapide -->
          <div class="pt-3 mt-2 border-t border-neutral-100 dark:border-neutral-800">
            <UButton
              variant="soft"
              color="primary"
              size="xs"
              icon="i-heroicons-plus"
              class="w-full justify-center"
              label="Associer un service"
              @click="emit('create-integration')"
            />
          </div>
        </div>
      </UCard>
    </aside>

    <!-- Zone de contenu selon la sous-rubrique sélectionnée -->
    <main class="flex-1 min-w-0 w-full space-y-6">
      <!-- 1. Informations générales du projet -->
      <div v-show="activeSubTab === 'general'">
        <ProjectDetails
          :project="project"
          :project-integrations="projectIntegrations"
          @edit="emit('edit-project')"
        />
      </div>

      <!-- Membres du projet -->
      <div v-show="activeSubTab === 'members'">
        <UCard :ui="{ body: 'p-5 sm:p-6' }">
          <ProjectMembers v-if="project?.['@id']" :project-iri="project['@id']" />
        </UCard>
      </div>

      <!-- Équipes du projet -->
      <div v-show="activeSubTab === 'teams'">
        <ProjectTeams v-if="project?.['@id']" :project-iri="project['@id']" />
      </div>

      <!-- 2. Vue d'ensemble de toutes les intégrations -->
      <div v-show="activeSubTab === 'integrations'">
        <ProjectIntegrations
          :project="project"
          :project-integrations="projectIntegrations"
          :all-integrations="allIntegrations"
          :is-loading="isLoading"
          @create="emit('create-integration')"
          @edit="(pi) => emit('edit-integration', pi)"
          @unlink="(pi) => emit('unlink-integration', pi)"
          @tested="emit('tested')"
        />
      </div>

      <!-- 3. Paramètres Gitea -->
      <div v-show="activeSubTab === 'gitea'" class="space-y-6">
        <!-- Si Gitea est lié -->
        <div v-if="giteaPi" class="space-y-6">
          <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
            <!-- En-tête du service -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-500/20 shrink-0">
                  <UIcon name="i-heroicons-code-bracket" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                      Connecteur Gitea
                    </h3>
                    <UBadge color="warning" variant="subtle" size="xs">
                      Forge Git
                    </UBadge>
                    <UBadge
                      :color="getHealthColor(giteaPi)"
                      variant="soft"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(giteaHealthStatus)"></span>
                      {{ getStatusLabel(giteaHealthStatus) }}
                    </UBadge>
                  </div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Gestion de la liaison entre le projet et le dépôt de code source Gitea.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <UButton
                  v-if="giteaExternalUrl"
                  :to="giteaExternalUrl"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Ouvrir Gitea"
                />
                <UButton
                  color="warning"
                  variant="soft"
                  size="sm"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingGitea"
                  label="Tester la santé"
                  @click="testServiceHealth(giteaPi, 'gitea')"
                />
                <UButton
                  color="primary"
                  variant="solid"
                  size="sm"
                  icon="i-heroicons-pencil-square"
                  label="Modifier"
                  @click="emit('edit-integration', giteaPi)"
                />
                <UButton
                  color="error"
                  variant="ghost"
                  size="sm"
                  icon="i-heroicons-trash"
                  title="Dissocier cette intégration"
                  @click="emit('unlink-integration', giteaPi)"
                />
              </div>
            </div>

            <!-- Retour du test de connectivité -->
            <div v-if="giteaTestFeedback">
              <UAlert
                :color="giteaTestFeedback.success ? 'success' : 'error'"
                :title="giteaTestFeedback.success ? 'Intégration Gitea opérationnelle' : 'Échec du test de santé Gitea'"
                :description="giteaTestFeedback.message"
                :icon="giteaTestFeedback.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
                size="sm"
                close
                @close="giteaTestFeedback = null"
              />
            </div>

            <!-- Grille des paramètres configurés -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Paramètres de liaison
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Dépôt cible</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ giteaPi.parameters?.repository || 'Non spécifié' }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Branche par défaut</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ giteaPi.parameters?.branch || 'main' }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Dernier contrôle</p>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ formatLastChecked(giteaPi.lastCheckedAt) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Informations sur le serveur distant -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Serveur hébergeant l'instance
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                    <span class="text-neutral-500">Nom du serveur :</span>
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(giteaPi) }}</span>
                  </div>
                  <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-300">
                      <span class="text-neutral-500 font-sans">Hôte :</span>
                      <span>{{ getServerHostDisplay(giteaPi) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-neutral-500">Statut serveur :</span>
                      <UBadge
                        :color="getStatusBadgeColor(getServerHealthStatus(giteaPi))"
                        variant="subtle"
                        size="xs"
                        :title="getIntegration(giteaPi)?.statusMessage || undefined"
                        class="flex items-center gap-1 font-sans"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getServerHealthStatus(giteaPi))"></span>
                        {{ getStatusLabel(getServerHealthStatus(giteaPi)) }}
                      </UBadge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Si Gitea n'est pas lié -->
        <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
          <div class="max-w-md mx-auto space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto border border-amber-500/20">
              <UIcon name="i-heroicons-code-bracket" class="w-7 h-7" />
            </div>
            <div>
              <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                Gitea n'est pas encore associé
              </h3>
              <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed">
                Reliez ce projet à un dépôt Gitea pour activer le suivi des commits, le parcours des branches et l'arborescence des fichiers.
              </p>
            </div>
            <div class="pt-2">
              <UButton
                color="warning"
                icon="i-heroicons-plus"
                label="Associer Gitea"
                size="sm"
                @click="emit('create-integration', 'gitea')"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- 4. Paramètres SonarQube -->
      <div v-show="activeSubTab === 'sonarqube'" class="space-y-6">
        <!-- Si SonarQube est lié -->
        <div v-if="sonarPi" class="space-y-6">
          <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
            <!-- En-tête du service -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-500/20 shrink-0">
                  <UIcon name="i-heroicons-shield-check" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                      Connecteur SonarQube
                    </h3>
                    <UBadge color="primary" variant="subtle" size="xs">
                      Qualité & Sécurité
                    </UBadge>
                    <UBadge
                      :color="getHealthColor(sonarPi)"
                      variant="soft"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(sonarHealthStatus)"></span>
                      {{ getStatusLabel(sonarHealthStatus) }}
                    </UBadge>
                  </div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Liaison d'audit continu du Quality Gate, des vulnérabilités et de la dette technique.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <UButton
                  v-if="sonarExternalUrl"
                  :to="sonarExternalUrl"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Ouvrir SonarQube"
                />
                <UButton
                  color="primary"
                  variant="soft"
                  size="sm"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingSonar"
                  label="Tester la santé"
                  @click="testServiceHealth(sonarPi, 'sonar')"
                />
                <UButton
                  color="primary"
                  variant="solid"
                  size="sm"
                  icon="i-heroicons-pencil-square"
                  label="Modifier"
                  @click="emit('edit-integration', sonarPi)"
                />
                <UButton
                  color="error"
                  variant="ghost"
                  size="sm"
                  icon="i-heroicons-trash"
                  title="Dissocier cette intégration"
                  @click="emit('unlink-integration', sonarPi)"
                />
              </div>
            </div>

            <!-- Retour du test de connectivité -->
            <div v-if="sonarTestFeedback">
              <UAlert
                :color="sonarTestFeedback.success ? 'success' : 'error'"
                :title="sonarTestFeedback.success ? 'Intégration SonarQube opérationnelle' : 'Échec du test de santé SonarQube'"
                :description="sonarTestFeedback.message"
                :icon="sonarTestFeedback.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
                size="sm"
                close
                @close="sonarTestFeedback = null"
              />
            </div>

            <!-- Grille des paramètres configurés -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Paramètres de liaison
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Clé de projet (Project Key)</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ sonarPi.parameters?.project_key || 'Non spécifiée' }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Dernier contrôle</p>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ formatLastChecked(sonarPi.lastCheckedAt) }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Statut opérationnel</p>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1 truncate flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :class="getStatusDotClass(sonarHealthStatus)"></span>
                    {{ getStatusLabel(sonarHealthStatus) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Informations sur le serveur distant -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Serveur hébergeant l'instance
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                    <span class="text-neutral-500">Nom du serveur :</span>
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(sonarPi) }}</span>
                  </div>
                  <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-300">
                      <span class="text-neutral-500 font-sans">Hôte :</span>
                      <span>{{ getServerHostDisplay(sonarPi) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-neutral-500">Statut serveur :</span>
                      <UBadge
                        :color="getStatusBadgeColor(getServerHealthStatus(sonarPi))"
                        variant="subtle"
                        size="xs"
                        :title="getIntegration(sonarPi)?.statusMessage || undefined"
                        class="flex items-center gap-1 font-sans"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getServerHealthStatus(sonarPi))"></span>
                        {{ getStatusLabel(getServerHealthStatus(sonarPi)) }}
                      </UBadge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Si SonarQube n'est pas lié -->
        <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
          <div class="max-w-md mx-auto space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto border border-blue-500/20">
              <UIcon name="i-heroicons-shield-check" class="w-7 h-7" />
            </div>
            <div>
              <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                SonarQube n'est pas encore associé
              </h3>
              <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed">
                Connectez ce projet à SonarQube pour surveiller en direct la santé de votre code, le Quality Gate et la sécurité logicielle.
              </p>
            </div>
            <div class="pt-2">
              <UButton
                color="primary"
                icon="i-heroicons-plus"
                label="Associer SonarQube"
                size="sm"
                @click="emit('create-integration', 'sonarqube')"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- 5. Paramètres Mantis BT -->
      <div v-show="activeSubTab === 'mantis'" class="space-y-6">
        <!-- Si Mantis est lié -->
        <div v-if="mantisPi" class="space-y-6">
          <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
            <!-- En-tête du service -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20 shrink-0">
                  <UIcon name="i-heroicons-bug-ant" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                      Connecteur Mantis BT
                    </h3>
                    <UBadge color="success" variant="subtle" size="xs">
                      Bug Tracker
                    </UBadge>
                    <UBadge
                      :color="getHealthColor(mantisPi)"
                      variant="soft"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(mantisHealthStatus)"></span>
                      {{ getStatusLabel(mantisHealthStatus) }}
                    </UBadge>
                  </div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Synchronisation et pointage vers les anomalies et feuilles de route Mantis.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <UButton
                  v-if="mantisExternalUrl"
                  :to="mantisExternalUrl"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Ouvrir Mantis"
                />
                <UButton
                  color="success"
                  variant="soft"
                  size="sm"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingMantis"
                  label="Tester la santé"
                  @click="testServiceHealth(mantisPi, 'mantis')"
                />
                <UButton
                  color="primary"
                  variant="solid"
                  size="sm"
                  icon="i-heroicons-pencil-square"
                  label="Modifier"
                  @click="emit('edit-integration', mantisPi)"
                />
                <UButton
                  color="error"
                  variant="ghost"
                  size="sm"
                  icon="i-heroicons-trash"
                  title="Dissocier cette intégration"
                  @click="emit('unlink-integration', mantisPi)"
                />
              </div>
            </div>

            <!-- Retour du test de connectivité -->
            <div v-if="mantisTestFeedback">
              <UAlert
                :color="mantisTestFeedback.success ? 'success' : 'error'"
                :title="mantisTestFeedback.success ? 'Intégration Mantis opérationnelle' : 'Échec du test de santé Mantis'"
                :description="mantisTestFeedback.message"
                :icon="mantisTestFeedback.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
                size="sm"
                close
                @close="mantisTestFeedback = null"
              />
            </div>

            <!-- Grille des paramètres configurés -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Paramètres de liaison
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Projet Mantis cible</p>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1 truncate" :title="mantisProjectTarget">
                    {{ mantisProjectTarget }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Identifiant numérique</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    #{{ mantisPi.parameters?.project_id || 'N/A' }}
                  </p>
                </div>

                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500 font-medium">Dernier contrôle</p>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ formatLastChecked(mantisPi.lastCheckedAt) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Informations sur le serveur distant -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Serveur hébergeant l'instance
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                    <span class="text-neutral-500">Nom du serveur :</span>
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(mantisPi) }}</span>
                  </div>
                  <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-300">
                      <span class="text-neutral-500 font-sans">Hôte :</span>
                      <span>{{ getServerHostDisplay(mantisPi) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-neutral-500">Statut serveur :</span>
                      <UBadge
                        :color="getStatusBadgeColor(getServerHealthStatus(mantisPi))"
                        variant="subtle"
                        size="xs"
                        :title="getIntegration(mantisPi)?.statusMessage || undefined"
                        class="flex items-center gap-1 font-sans"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getServerHealthStatus(mantisPi))"></span>
                        {{ getStatusLabel(getServerHealthStatus(mantisPi)) }}
                      </UBadge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Si Mantis n'est pas lié -->
        <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
          <div class="max-w-md mx-auto space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/20">
              <UIcon name="i-heroicons-bug-ant" class="w-7 h-7" />
            </div>
            <div>
              <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                Mantis BT n'est pas encore associé
              </h3>
              <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed">
                Connectez ce projet à Mantis Bug Tracker pour enregistrer et suivre la résolution de vos anomalies.
              </p>
            </div>
            <div class="pt-2">
              <UButton
                color="success"
                icon="i-heroicons-plus"
                label="Associer Mantis BT"
                size="sm"
                @click="emit('create-integration', 'mantis')"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- 5. Paramètres Jenkins CI -->
      <div v-show="activeSubTab === 'jenkins'" class="space-y-6">
        <!-- Si Jenkins est lié -->
        <div v-if="jenkinsPi" class="space-y-6">
          <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
            <!-- En-tête du service -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center border border-sky-500/20 shrink-0">
                  <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                      Connecteur Jenkins CI
                    </h3>
                    <UBadge color="info" variant="subtle" size="xs">
                      Intégration continue
                    </UBadge>
                    <UBadge
                      :color="getHealthColor(jenkinsPi)"
                      variant="soft"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(jenkinsHealthStatus)"></span>
                      {{ getStatusLabel(jenkinsHealthStatus) }}
                    </UBadge>
                  </div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Gestion de la liaison entre le projet et les jobs Jenkins CI.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <UButton
                  v-if="jenkinsExternalUrl"
                  :to="jenkinsExternalUrl"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Ouvrir Jenkins"
                />
                <UButton
                  color="warning"
                  variant="soft"
                  size="sm"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingJenkins"
                  label="Tester la santé"
                  @click="testServiceHealth(jenkinsPi, 'jenkins')"
                />
                <UButton
                  color="primary"
                  variant="solid"
                  size="sm"
                  icon="i-heroicons-pencil-square"
                  label="Modifier les paramètres"
                  @click="emit('edit-integration', jenkinsPi)"
                />
                <UButton
                  color="error"
                  variant="ghost"
                  size="sm"
                  icon="i-heroicons-trash"
                  label="Dissocier"
                  @click="emit('unlink-integration', jenkinsPi)"
                />
              </div>
            </div>

            <!-- Retour du test de connectivité -->
            <div v-if="jenkinsTestFeedback">
              <UAlert
                :color="jenkinsTestFeedback.success ? 'success' : 'error'"
                :title="jenkinsTestFeedback.success ? 'Intégration Jenkins opérationnelle' : 'Échec du test de santé Jenkins'"
                :description="jenkinsTestFeedback.message"
                :icon="jenkinsTestFeedback.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
                size="sm"
                close
                @close="jenkinsTestFeedback = null"
              />
            </div>

            <!-- Paramètres configurés -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Paramètres de liaison
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    Dossier des jobs Jenkins
                  </span>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ jenkinsFolderTarget }}
                  </p>
                </div>

                <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    Jobs référencés
                  </span>
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mt-1">
                    {{ jenkinsJobs.length }} job(s) référencé(s)
                  </p>
                </div>
              </div>
            </div>

            <!-- Liste des jobs référencés -->
            <div v-if="jenkinsJobs.length > 0">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Jobs enregistrés dans le connecteur
              </h4>
              <div class="flex items-center gap-2 flex-wrap">
                <UBadge
                  v-for="job in jenkinsJobs"
                  :key="job.name"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-xs"
                >
                  {{ job.displayName || job.name }}
                </UBadge>
              </div>
            </div>

            <!-- Test de santé opérationnel du connecteur -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Tester la validité du connecteur
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                  <p class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                    Vérification de connectivité & détection des jobs
                  </p>
                  <p class="text-xs text-neutral-500">
                    {{ jenkinsPi.statusMessage || 'Aucune vérification effectuée pour le moment.' }}
                  </p>
                  <p v-if="jenkinsPi.lastCheckedAt" class="text-[11px] text-neutral-400">
                    Dernière vérification : {{ formatDate(jenkinsPi.lastCheckedAt) }}
                  </p>
                </div>

                <UButton
                  color="neutral"
                  variant="outline"
                  size="xs"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingHealth(jenkinsPi)"
                  label="Tester la liaison"
                  @click="testHealth(jenkinsPi)"
                />
              </div>
            </div>

            <!-- Informations sur le serveur distant -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
                Serveur hébergeant l'instance
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                    <span class="text-neutral-500">Nom du serveur :</span>
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(jenkinsPi) }}</span>
                  </div>
                  <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-300">
                      <span class="text-neutral-500 font-sans">Hôte :</span>
                      <span>{{ getServerHostDisplay(jenkinsPi) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-neutral-500">Statut serveur :</span>
                      <UBadge
                        :color="getStatusBadgeColor(getServerHealthStatus(jenkinsPi))"
                        variant="subtle"
                        size="xs"
                        :title="getIntegration(jenkinsPi)?.statusMessage || undefined"
                        class="flex items-center gap-1 font-sans"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getServerHealthStatus(jenkinsPi))"></span>
                        {{ getStatusLabel(getServerHealthStatus(jenkinsPi)) }}
                      </UBadge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Si Jenkins n'est pas lié -->
        <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
          <div class="max-w-md mx-auto space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center mx-auto border border-sky-500/20">
              <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-7 h-7" />
            </div>
            <div>
              <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                Jenkins CI n'est pas encore associé
              </h3>
              <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed">
                Connectez un dossier Jenkins à ce projet pour référencer automatiquement tous ses jobs et surveiller vos builds en temps réel.
              </p>
            </div>
            <div class="pt-2">
              <UButton
                color="primary"
                icon="i-heroicons-plus"
                label="Associer Jenkins CI"
                size="sm"
                @click="emit('create-integration', 'jenkins')"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- 5. Paramètres Nexus Repository -->
      <div v-show="activeSubTab === 'nexus'" class="space-y-6">
        <!-- Si Nexus est lié -->
        <div v-if="nexusPi" class="space-y-6">
          <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
            <!-- En-tête du service -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center border border-teal-500/20 shrink-0">
                  <UIcon name="i-heroicons-cube" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                      Connecteur Nexus Repository
                    </h3>
                    <UBadge color="info" variant="subtle" size="xs">
                      Gestionnaire d'artefacts
                    </UBadge>
                    <UBadge
                      :color="getHealthColor(nexusPi)"
                      variant="soft"
                      size="xs"
                      class="flex items-center gap-1"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(nexusHealthStatus)"></span>
                      {{ getStatusLabel(nexusHealthStatus) }}
                    </UBadge>
                  </div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Gestion de la liaison entre le projet et le gestionnaire d'artefacts Nexus.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <UButton
                  v-if="nexusExternalUrl"
                  :to="nexusExternalUrl"
                  target="_blank"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-top-right-on-square"
                  label="Ouvrir Nexus"
                />
                <UButton
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-heroicons-arrow-path"
                  :loading="isTestingNexus"
                  label="Tester la santé"
                  @click="testServiceHealth(nexusPi, 'nexus')"
                />
                <UButton
                  color="primary"
                  variant="solid"
                  size="sm"
                  icon="i-heroicons-pencil-square"
                  label="Modifier les paramètres"
                  @click="emit('edit-integration', nexusPi)"
                />
                <UButton
                  color="error"
                  variant="ghost"
                  size="sm"
                  icon="i-heroicons-trash"
                  label="Dissocier"
                  @click="emit('unlink-integration', nexusPi)"
                />
              </div>
            </div>

            <!-- Feedback de test de santé -->
            <UAlert
              v-if="nexusTestFeedback"
              :color="nexusTestFeedback.success ? 'success' : 'error'"
              :icon="nexusTestFeedback.success ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
              variant="subtle"
              :title="nexusTestFeedback.success ? 'Test de connectivité réussi' : 'Échec du test de connectivité'"
              :description="nexusTestFeedback.message"
              class="text-xs"
              close
              @close="nexusTestFeedback = null"
            />

            <!-- Configuration actuelle -->
            <div class="space-y-4">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
                Configuration du dépôt
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500">Dépôt cible (repository)</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ nexusPi.parameters?.repository || 'Non configuré' }}
                  </p>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500">Groupe de composants</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ nexusPi.parameters?.group || 'Tous les groupes' }}
                  </p>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80">
                  <p class="text-xs text-neutral-500">Format</p>
                  <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                    {{ nexusPi.parameters?.format || 'Auto' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Serveur hébergeant l'instance Nexus -->
            <div v-if="getServer(nexusPi)" class="space-y-4">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
                Serveur Nexus
              </h4>
              <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                    <span class="text-neutral-500">Nom du serveur :</span>
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(nexusPi) }}</span>
                  </div>
                  <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-400">
                    <span class="text-neutral-500 font-sans">Hôte :</span>
                    <span>{{ getServerHostDisplay(nexusPi) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Si Nexus n'est pas lié -->
        <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
          <div class="max-w-md mx-auto space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center mx-auto border border-teal-500/20">
              <UIcon name="i-heroicons-cube" class="w-7 h-7" />
            </div>
            <div>
              <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100">
                Nexus Repository n'est pas encore associé
              </h3>
              <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1 leading-relaxed">
                Associez une instance Nexus pour consulter les artefacts publiés et les packages de vos dépendances.
              </p>
            </div>
            <div class="pt-2">
              <UButton
                color="primary"
                icon="i-heroicons-plus"
                label="Associer Nexus Repository"
                size="sm"
                @click="emit('create-integration', 'nexus')"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- 6. Paramètres d'une autre intégration spécifique -->
      <div v-if="selectedOtherIntegration" class="space-y-6">
        <UCard :ui="{ body: 'p-5 sm:p-6 space-y-6' }">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-3.5">
              <div
                class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                :class="getTypeBgClass(getIntegrationType(selectedOtherIntegration))"
              >
                <UIcon :name="getTypeIcon(getIntegrationType(selectedOtherIntegration))" class="w-6 h-6" />
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-neutral-100">
                    {{ getIntegrationName(selectedOtherIntegration) }}
                  </h3>
                  <UBadge :color="getTypeBadgeColor(getIntegrationType(selectedOtherIntegration))" variant="subtle" size="xs">
                    {{ getIntegrationType(selectedOtherIntegration) }}
                  </UBadge>
                  <UBadge
                    :color="getHealthColor(selectedOtherIntegration)"
                    variant="soft"
                    size="xs"
                    class="flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(selectedOtherIntegration.status)"></span>
                    {{ getStatusLabel(selectedOtherIntegration.status) }}
                  </UBadge>
                </div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                  Gestion du service externe connecté.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
              <UButton
                color="primary"
                variant="solid"
                size="sm"
                icon="i-heroicons-pencil-square"
                label="Modifier"
                @click="emit('edit-integration', selectedOtherIntegration)"
              />
              <UButton
                color="error"
                variant="ghost"
                size="sm"
                icon="i-heroicons-trash"
                label="Dissocier"
                @click="emit('unlink-integration', selectedOtherIntegration)"
              />
            </div>
          </div>

          <!-- Paramètres -->
          <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
              Paramètres configurés
            </h4>
            <div v-if="hasParameters(selectedOtherIntegration)" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="(val, key) in selectedOtherIntegration.parameters"
                :key="key"
                class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80"
              >
                <p class="text-xs text-neutral-500 font-mono">{{ key }}</p>
                <p class="text-sm font-semibold font-mono text-neutral-900 dark:text-neutral-100 mt-1 truncate">
                  {{ val }}
                </p>
              </div>
            </div>
            <p v-else class="text-xs text-neutral-400 italic">
              Aucun paramètre spécifique requis.
            </p>
          </div>

          <!-- Informations sur le serveur distant -->
          <div v-if="getServer(selectedOtherIntegration)">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-3">
              Serveur hébergeant l'instance
            </h4>
            <div class="p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/80 dark:border-neutral-700/80 space-y-2">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                  <UIcon name="i-heroicons-server" class="w-4 h-4 text-neutral-400 shrink-0" />
                  <span class="text-neutral-500">Nom du serveur :</span>
                  <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ getServerDisplayName(selectedOtherIntegration) }}</span>
                </div>
                <div class="flex items-center gap-4 flex-wrap">
                  <div class="flex items-center gap-2 font-mono text-neutral-600 dark:text-neutral-300">
                    <span class="text-neutral-500 font-sans">Hôte :</span>
                    <span>{{ getServerHostDisplay(selectedOtherIntegration) }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-neutral-500">Statut serveur :</span>
                    <UBadge
                      :color="getStatusBadgeColor(getServerHealthStatus(selectedOtherIntegration))"
                      variant="subtle"
                      size="xs"
                      :title="getIntegration(selectedOtherIntegration)?.statusMessage || undefined"
                      class="flex items-center gap-1 font-sans"
                    >
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(getServerHealthStatus(selectedOtherIntegration))"></span>
                      {{ getStatusLabel(getServerHealthStatus(selectedOtherIntegration)) }}
                    </UBadge>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </UCard>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import ProjectDetails from "./ProjectDetails.vue";
import ProjectIntegrations from "./ProjectIntegrations.vue";
import ProjectMembers from "./ProjectMembers.vue";
import ProjectTeams from "./ProjectTeams.vue";
import { useProjectIntegrationHealthStore } from "~/stores/projectIntegration/health";
import {
  getIntegration,
  getIntegrationType,
  getIntegrationName,
  getServer,
  getServerName,
  getServerHost,
  getServerStatus,
  getStatusBadgeColor,
  getExternalUrl,
  getTypeIcon,
  getTypeBadgeColor,
  getTypeBgClass,
  getStatusLabel,
  getStatusDotClass,
  getServerNameAndHost,
} from "~/utils/integration";

const props = withDefaults(
  defineProps<{
    project?: Project;
    projectIntegrations?: ProjectIntegration[];
    allIntegrations?: Integration[];
    initialSubTab?: string;
    isLoading?: boolean;
  }>(),
  {
    projectIntegrations: () => [],
    allIntegrations: () => [],
    initialSubTab: "integrations",
    isLoading: false,
  }
);

const emit = defineEmits<{
  (e: "create-integration", type?: string): void;
  (e: "edit-integration", pi: ProjectIntegration): void;
  (e: "unlink-integration", pi: ProjectIntegration): void;
  (e: "tested"): void;
  (e: "edit-project"): void;
  (e: "subtab-changed", subTabId: string): void;
}>();

const healthStore = useProjectIntegrationHealthStore();

// Sous-rubrique active dans les paramètres
const activeSubTab = ref<string>(props.initialSubTab || "integrations");

watch(
  () => props.initialSubTab,
  (newVal) => {
    if (newVal) {
      activeSubTab.value = newVal;
    }
  }
);

function selectSubTab(subTabId: string) {
  activeSubTab.value = subTabId;
  emit("subtab-changed", subTabId);
}

// Liaisons dédiées
const giteaPi = computed(() =>
  props.projectIntegrations.find((pi) => getIntegrationType(pi) === "gitea")
);

const sonarPi = computed(() =>
  props.projectIntegrations.find((pi) => getIntegrationType(pi) === "sonarqube")
);

const mantisPi = computed(() =>
  props.projectIntegrations.find((pi) => getIntegrationType(pi) === "mantis")
);

const jenkinsPi = computed(() =>
  props.projectIntegrations.find((pi) => getIntegrationType(pi) === "jenkins")
);

const nexusPi = computed(() =>
  props.projectIntegrations.find((pi) => getIntegrationType(pi) === "nexus")
);

const otherIntegrations = computed(() =>
  props.projectIntegrations.filter((pi) => {
    const t = getIntegrationType(pi);
    return t !== "gitea" && t !== "sonarqube" && t !== "mantis" && t !== "jenkins" && t !== "nexus";
  })
);

const selectedOtherIntegration = computed(() => {
  if (!activeSubTab.value.startsWith("custom_")) return null;
  const targetId = activeSubTab.value.replace("custom_", "");
  return (
    props.projectIntegrations.find(
      (pi) => String(pi.id) === targetId || String(pi["@id"]) === targetId
    ) || null
  );
});

// URLs externes
const giteaExternalUrl = computed(() => (giteaPi.value ? getExternalUrl(giteaPi.value) : null));
const sonarExternalUrl = computed(() => (sonarPi.value ? getExternalUrl(sonarPi.value) : null));
const mantisExternalUrl = computed(() => (mantisPi.value ? getExternalUrl(mantisPi.value) : null));
const jenkinsExternalUrl = computed(() => (jenkinsPi.value ? getExternalUrl(jenkinsPi.value) : null));
const nexusExternalUrl = computed(() => (nexusPi.value ? getExternalUrl(nexusPi.value) : null));

// Labels et présentations
const giteaHealthStatus = computed(() => giteaPi.value?.status);
const sonarHealthStatus = computed(() => sonarPi.value?.status);
const mantisHealthStatus = computed(() => mantisPi.value?.status);
const jenkinsHealthStatus = computed(() => jenkinsPi.value?.status);
const nexusHealthStatus = computed(() => nexusPi.value?.status);

const jenkinsFolderTarget = computed(() => {
  if (!jenkinsPi.value) return "Non configuré";
  const p = jenkinsPi.value.parameters || {};
  return p.folder || p.folder_path || p.job_folder || p.job || p.job_name || "Non spécifié";
});

const jenkinsJobs = computed(() => {
  if (!jenkinsPi.value) return [];
  const p = jenkinsPi.value.parameters || {};
  return Array.isArray(p.jobs) ? p.jobs : [];
});

const mantisProjectTarget = computed(() => {
  if (!mantisPi.value) return "Non configuré";
  const p = mantisPi.value.parameters || {};
  if (p.project_name && p.project_id) {
    return `${p.project_name} (#${p.project_id})`;
  }
  if (p.project_name) return p.project_name;
  if (p.project_id) return `Projet #${p.project_id}`;
  return "Non spécifié";
});

function getServerDisplayName(pi?: ProjectIntegration | null): string {
  return getServerName(pi);
}

function getServerHostDisplay(pi?: ProjectIntegration | null): string {
  return getServerHost(pi);
}

function getServerHealthStatus(pi?: ProjectIntegration | null): string {
  return getServerStatus(pi);
}

function getHealthColor(pi?: ProjectIntegration | null): "success" | "warning" | "error" | "neutral" {
  if (!pi?.status) return "neutral";
  switch (pi.status) {
    case "healthy":
      return "success";
    case "warning":
      return "warning";
    case "error":
      return "error";
    default:
      return "neutral";
  }
}

function hasParameters(pi: ProjectIntegration): boolean {
  return !!pi.parameters && Object.keys(pi.parameters).length > 0;
}

function formatLastChecked(dateString?: string): string {
  if (!dateString) return "Jamais vérifié";
  try {
    const d = new Date(dateString);
    return new Intl.DateTimeFormat("fr-FR", {
      dateStyle: "short",
      timeStyle: "short",
    }).format(d);
  } catch {
    return dateString;
  }
}

function formatDate(dateString?: string): string {
  return formatLastChecked(dateString);
}

// Tests de santé pour chaque connecteur
const isTestingGitea = ref(false);
const giteaTestFeedback = ref<{ success: boolean; message: string } | null>(null);

const isTestingSonar = ref(false);
const sonarTestFeedback = ref<{ success: boolean; message: string } | null>(null);

const isTestingMantis = ref(false);
const mantisTestFeedback = ref<{ success: boolean; message: string } | null>(null);

const isTestingJenkins = ref(false);
const jenkinsTestFeedback = ref<{ success: boolean; message: string } | null>(null);

const isTestingNexus = ref(false);
const nexusTestFeedback = ref<{ success: boolean; message: string } | null>(null);

function isTestingHealth(pi?: ProjectIntegration | null): boolean {
  if (!pi) return false;
  const t = getIntegrationType(pi);
  if (t === "gitea") return isTestingGitea.value;
  if (t === "sonarqube") return isTestingSonar.value;
  if (t === "mantis") return isTestingMantis.value;
  if (t === "jenkins") return isTestingJenkins.value;
  if (t === "nexus") return isTestingNexus.value;
  return false;
}

function testHealth(pi?: ProjectIntegration | null) {
  if (!pi) return;
  const t = getIntegrationType(pi);
  if (t === "gitea") return testServiceHealth(pi, "gitea");
  if (t === "sonarqube") return testServiceHealth(pi, "sonar");
  if (t === "mantis") return testServiceHealth(pi, "mantis");
  if (t === "jenkins") return testServiceHealth(pi, "jenkins");
  if (t === "nexus") return testServiceHealth(pi, "nexus");
}

async function testServiceHealth(
  pi: ProjectIntegration,
  service: "gitea" | "sonar" | "mantis" | "jenkins" | "nexus"
) {
  if (!pi.id) return;

  if (service === "gitea") isTestingGitea.value = true;
  if (service === "sonar") isTestingSonar.value = true;
  if (service === "mantis") isTestingMantis.value = true;
  if (service === "jenkins") isTestingJenkins.value = true;
  if (service === "nexus") isTestingNexus.value = true;

  try {
    const res = await healthStore.checkHealth(pi.id);
    const feedback = {
      success: res.success,
      message:
        res.statusMessage ||
        res.message ||
        (res.success ? "Test réussi." : "Échec du test."),
    };

    if (service === "gitea") giteaTestFeedback.value = feedback;
    if (service === "sonar") sonarTestFeedback.value = feedback;
    if (service === "mantis") mantisTestFeedback.value = feedback;
    if (service === "jenkins") jenkinsTestFeedback.value = feedback;
    if (service === "nexus") nexusTestFeedback.value = feedback;

    emit("tested");
  } catch (err: any) {
    const feedback = {
      success: false,
      message: err?.message || "Erreur lors du test de santé.",
    };
    if (service === "gitea") giteaTestFeedback.value = feedback;
    if (service === "sonar") sonarTestFeedback.value = feedback;
    if (service === "mantis") mantisTestFeedback.value = feedback;
    if (service === "jenkins") jenkinsTestFeedback.value = feedback;
    if (service === "nexus") nexusTestFeedback.value = feedback;
  } finally {
    if (service === "gitea") isTestingGitea.value = false;
    if (service === "sonar") isTestingSonar.value = false;
    if (service === "mantis") isTestingMantis.value = false;
    if (service === "jenkins") isTestingJenkins.value = false;
    if (service === "nexus") isTestingNexus.value = false;
  }
}
</script>
