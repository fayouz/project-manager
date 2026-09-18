<template>
  <div class="space-y-6 w-full">
    <!-- Bannière de synthèse du projet -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-primary-600/10 via-primary-500/5 to-transparent border border-primary-500/20">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
              {{ project.name || 'Projet' }}
            </h2>
            <UBadge v-if="organisationName" color="neutral" variant="subtle" size="sm" class="flex items-center gap-1">
              <UIcon name="i-heroicons-building-office-2" class="w-3.5 h-3.5" />
              {{ organisationName }}
            </UBadge>
          </div>
          <p class="text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 mt-1">
            Cockpit centralisé : suivez le code source, la qualité logicielle et les anomalies en un seul endroit.
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <UBadge
            :color="overallHealthColor"
            variant="soft"
            size="md"
            class="flex items-center gap-1.5 font-medium px-3 py-1"
          >
            <span class="w-2 h-2 rounded-full" :class="overallHealthDot"></span>
            {{ overallHealthText }}
          </UBadge>
          <UButton
            color="primary"
            variant="solid"
            size="sm"
            icon="i-heroicons-pencil-square"
            label="Modifier"
            @click="emit('edit-project')"
          />
        </div>
      </div>
    </div>

    <!-- Cartes KPI rapides en haut du dashboard -->
    <div
      class="grid grid-cols-1 sm:grid-cols-2 gap-4"
      :class="jenkinsPi ? 'lg:grid-cols-5' : 'lg:grid-cols-4'"
    >
      <!-- KPI 1 : Intégrations -->
      <UCard
        class="cursor-pointer transition hover:border-primary-500/50 hover:shadow-sm"
        :ui="{ body: 'p-4 sm:p-5' }"
        @click="emit('switch-tab', 'settings', 'integrations')"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Services connectés</p>
            <p class="text-2xl font-bold mt-1 text-neutral-900 dark:text-neutral-100">
              {{ projectIntegrations.length }}
            </p>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
              <UIcon name="i-heroicons-check-circle" class="w-3.5 h-3.5" />
              <span>{{ activeIntegrationsCount }} actif(s)</span>
            </p>
          </div>
          <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
            <UIcon name="i-heroicons-puzzle-piece" class="w-6 h-6" />
          </div>
        </div>
      </UCard>

      <!-- KPI 2 : Gitea -->
      <UCard
        class="cursor-pointer transition hover:border-amber-500/50 hover:shadow-sm"
        :ui="{ body: 'p-4 sm:p-5' }"
        @click="emit('switch-tab', 'gitea')"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Forge Git</p>
            <p class="text-sm font-bold mt-1 text-neutral-900 dark:text-neutral-100 truncate max-w-[150px]">
              {{ giteaRepo || 'Non configuré' }}
            </p>
            <p v-if="giteaPi" class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1 font-mono">
              <UIcon name="i-heroicons-hashtag" class="w-3 h-3" />
              {{ giteaBranch }}
            </p>
            <p v-else class="text-xs text-neutral-400 mt-1">
              Cliquer pour associer
            </p>
          </div>
          <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <UIcon name="i-heroicons-code-bracket" class="w-6 h-6" />
          </div>
        </div>
      </UCard>

      <!-- KPI 3 : SonarQube -->
      <UCard
        class="cursor-pointer transition hover:border-blue-500/50 hover:shadow-sm"
        :ui="{ body: 'p-4 sm:p-5' }"
        @click="emit('switch-tab', 'sonarqube')"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Qualité de code</p>
            <p class="text-sm font-bold mt-1 text-neutral-900 dark:text-neutral-100 truncate max-w-[150px]">
              {{ sonarKey || 'Non configuré' }}
            </p>
            <p v-if="sonarPi" class="text-xs mt-1 flex items-center gap-1 font-medium" :class="sonarQualityGateColor">
              <UIcon :name="sonarQualityGateIcon" class="w-3.5 h-3.5" />
              Quality Gate : {{ sonarQualityGateLabel }}
            </p>
            <p v-else class="text-xs text-neutral-400 mt-1">
              Cliquer pour associer
            </p>
          </div>
          <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <UIcon name="i-heroicons-shield-check" class="w-6 h-6" />
          </div>
        </div>
      </UCard>

      <!-- KPI 4 : Mantis BT -->
      <UCard
        class="cursor-pointer transition hover:border-emerald-500/50 hover:shadow-sm"
        :ui="{ body: 'p-4 sm:p-5' }"
        @click="emit('switch-tab', 'mantis')"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Suivi des bogues</p>
            <p class="text-sm font-bold mt-1 text-neutral-900 dark:text-neutral-100 truncate" :class="{ 'font-mono': !mantisProjectName }">
              {{ mantisProjectName || (mantisId ? `Projet #${mantisId}` : 'Non configuré') }}
            </p>
            <p v-if="mantisPi" class="text-xs mt-1 flex items-center gap-1 font-medium" :class="(mantisLiveData?.stats?.open ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
              <UIcon name="i-heroicons-bug-ant" class="w-3.5 h-3.5" />
              {{ mantisLiveData?.stats?.open !== undefined ? `${mantisLiveData.stats.open} anomalie(s) active(s)` : 'Suivi actif' }}
            </p>
            <p v-else class="text-xs text-neutral-400 mt-1">
              Cliquer pour associer
            </p>
          </div>
          <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <UIcon name="i-heroicons-bug-ant" class="w-6 h-6" />
          </div>
        </div>
      </UCard>

      <!-- KPI 5 : Jenkins CI (si configuré) -->
      <UCard
        v-if="jenkinsPi"
        class="cursor-pointer transition hover:border-sky-500/50 hover:shadow-sm"
        :ui="{ body: 'p-4 sm:p-5' }"
        @click="emit('switch-tab', 'jenkins')"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Jenkins CI</p>
            <p class="text-sm font-bold mt-1 text-neutral-900 dark:text-neutral-100 truncate max-w-[150px]">
              {{ jenkinsFolderDisplay || 'Non configuré' }}
            </p>
            <p class="text-xs text-sky-600 dark:text-sky-400 mt-1 flex items-center gap-1 font-medium">
              <UIcon name="i-heroicons-queue-list" class="w-3.5 h-3.5" />
              <span>{{ jenkinsJobsCount }} job(s) référencé(s)</span>
            </p>
          </div>
          <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center">
            <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-6 h-6" />
          </div>
        </div>
      </UCard>
    </div>

    <!-- Section principale : Cockpit des Widgets de services -->
    <div
      class="grid gap-6"
      :class="jenkinsPi ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4' : 'grid-cols-1 lg:grid-cols-3'"
    >
      <!-- 1. Widget Gitea -->
      <UCard :ui="{ body: 'p-5 space-y-4 flex flex-col justify-between h-full' }">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <UIcon name="i-heroicons-code-bracket" class="w-5 h-5" />
              </div>
              <div>
                <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">Gitea</h4>
                <p class="text-[11px] text-neutral-500">Dépôt & Code source</p>
              </div>
            </div>
            <UBadge
              :color="giteaPi ? 'warning' : 'neutral'"
              variant="subtle"
              size="xs"
            >
              {{ giteaPi ? 'Connecté' : 'Non lié' }}
            </UBadge>
          </div>

          <div v-if="giteaPi" class="mt-4 space-y-3">
            <div class="bg-neutral-50 dark:bg-neutral-900/60 p-3 rounded-xl border border-neutral-200/60 dark:border-neutral-800/60">
              <p class="text-[11px] text-neutral-500 font-medium">Dépôt Git</p>
              <p class="text-xs font-mono font-bold text-neutral-900 dark:text-neutral-100 truncate mt-0.5">
                {{ giteaRepo }}
              </p>
              <p class="text-[11px] text-neutral-500 mt-1">Branche : <code class="text-neutral-700 dark:text-neutral-300">{{ giteaBranch }}</code></p>
            </div>

            <div class="flex items-center gap-2">
              <UButton
                v-if="giteaExternalUrl"
                :to="giteaExternalUrl"
                target="_blank"
                size="xs"
                color="warning"
                variant="soft"
                icon="i-heroicons-arrow-top-right-on-square"
                label="Ouvrir dans Gitea"
                class="flex-1 justify-center"
              />
              <UButton
                size="xs"
                variant="outline"
                color="neutral"
                icon="i-heroicons-clipboard"
                label="Cloner"
                @click="copyGiteaClone"
              />
            </div>
          </div>

          <div v-else class="mt-4 py-4 text-center space-y-2">
            <p class="text-xs text-neutral-500">Associez un dépôt Gitea pour naviguer dans le code.</p>
            <UButton
              size="xs"
              color="warning"
              variant="soft"
              icon="i-heroicons-plus"
              label="Associer Gitea"
              @click="emit('link-tool', 'gitea')"
            />
          </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800 flex justify-end">
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-arrow-right"
            label="Vue détaillée Gitea"
            @click="emit('switch-tab', 'gitea')"
          />
        </div>
      </UCard>

      <!-- 2. Widget SonarQube -->
      <UCard :ui="{ body: 'p-5 space-y-4 flex flex-col justify-between h-full' }">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <UIcon name="i-heroicons-shield-check" class="w-5 h-5" />
              </div>
              <div>
                <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">SonarQube</h4>
                <p class="text-[11px] text-neutral-500">Qualité & Sécurité</p>
              </div>
            </div>
            <UBadge
              :color="sonarPi ? 'primary' : 'neutral'"
              variant="subtle"
              size="xs"
            >
              {{ sonarPi ? 'Connecté' : 'Non lié' }}
            </UBadge>
          </div>

          <div v-if="sonarPi" class="mt-4 space-y-3">
            <div class="bg-neutral-50 dark:bg-neutral-900/60 p-3 rounded-xl border border-neutral-200/60 dark:border-neutral-800/60">
              <div class="flex items-center justify-between">
                <p class="text-[11px] text-neutral-500 font-medium">Quality Gate</p>
                <UBadge :color="sonarQualityGateStatus === 'OK' ? 'success' : (sonarQualityGateStatus === 'ERROR' ? 'error' : 'neutral')" variant="subtle" size="xs">
                  {{ sonarQualityGateLabel }}
                </UBadge>
              </div>
              <p class="text-xs font-mono font-bold text-neutral-900 dark:text-neutral-100 truncate mt-1">
                {{ sonarKey }}
              </p>
            </div>

            <!-- Mini grille métriques -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div class="p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-800">
                <span class="text-[10px] text-neutral-400 block">Bugs</span>
                <span class="font-bold" :class="(sonarLiveData?.metrics?.bugs ?? 0) === 0 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ sonarLiveData?.metrics?.bugs ?? 0 }} ({{ sonarLiveData?.metrics?.reliabilityRating || 'A' }})
                </span>
              </div>
              <div class="p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-800">
                <span class="text-[10px] text-neutral-400 block">Sécurité</span>
                <span class="font-bold" :class="(sonarLiveData?.metrics?.vulnerabilities ?? 0) === 0 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ sonarLiveData?.metrics?.vulnerabilities ?? 0 }} ({{ sonarLiveData?.metrics?.securityRating || 'A' }})
                </span>
              </div>
              <div class="p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-800">
                <span class="text-[10px] text-neutral-400 block">Dette</span>
                <span class="font-bold text-blue-600 truncate block">
                  {{ sonarLiveData?.metrics?.debtDisplay || '0min' }}
                </span>
              </div>
            </div>

            <UButton
              v-if="sonarExternalUrl"
              :to="sonarExternalUrl"
              target="_blank"
              size="xs"
              color="primary"
              variant="soft"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir SonarQube"
              class="w-full justify-center"
            />
          </div>

          <div v-else class="mt-4 py-4 text-center space-y-2">
            <p class="text-xs text-neutral-500">Auditez la qualité du code et la conformité du Quality Gate.</p>
            <UButton
              size="xs"
              color="primary"
              variant="soft"
              icon="i-heroicons-plus"
              label="Associer SonarQube"
              @click="emit('link-tool', 'sonarqube')"
            />
          </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800 flex justify-end">
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-arrow-right"
            label="Vue détaillée Qualité"
            @click="emit('switch-tab', 'sonarqube')"
          />
        </div>
      </UCard>

      <!-- 3. Widget Mantis BT -->
      <UCard :ui="{ body: 'p-5 space-y-4 flex flex-col justify-between h-full' }">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <UIcon name="i-heroicons-bug-ant" class="w-5 h-5" />
              </div>
              <div>
                <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">Mantis BT</h4>
                <p class="text-[11px] text-neutral-500">Suivi des anomalies</p>
              </div>
            </div>
            <UBadge
              :color="mantisPi ? 'success' : 'neutral'"
              variant="subtle"
              size="xs"
            >
              {{ mantisPi ? 'Connecté' : 'Non lié' }}
            </UBadge>
          </div>

          <div v-if="mantisPi" class="mt-4 space-y-3">
            <div class="bg-neutral-50 dark:bg-neutral-900/60 p-3 rounded-xl border border-neutral-200/60 dark:border-neutral-800/60">
              <div class="flex items-center justify-between">
                <p class="text-[11px] text-neutral-500 font-medium">Projet Mantis</p>
                <UBadge color="success" variant="subtle" size="xs">
                  {{ mantisLiveData?.stats?.resolutionRate ?? 0 }}% résolu
                </UBadge>
              </div>
              <p class="text-sm font-bold text-neutral-900 dark:text-neutral-100 truncate mt-0.5" :title="mantisTargetDisplay">
                {{ mantisTargetDisplay }}
              </p>
              <p class="text-[11px] text-neutral-500 mt-1 flex items-center gap-1">
                <span class="text-rose-600 font-medium">{{ mantisLiveData?.stats?.open ?? 0 }} anomalie(s) active(s)</span>
                <span>•</span>
                <span>{{ mantisLiveData?.stats?.total ?? 0 }} au total</span>
              </p>
            </div>

            <div class="flex items-center gap-2">
              <UButton
                v-if="mantisExternalUrl"
                :to="mantisExternalUrl"
                target="_blank"
                size="xs"
                color="success"
                variant="soft"
                icon="i-heroicons-arrow-top-right-on-square"
                label="Voir les anomalies"
                class="flex-1 justify-center"
              />
            </div>
          </div>

          <div v-else class="mt-4 py-4 text-center space-y-2">
            <p class="text-xs text-neutral-500">Centralisez vos tickets d'anomalies et signalements.</p>
            <UButton
              size="xs"
              color="success"
              variant="soft"
              icon="i-heroicons-plus"
              label="Associer Mantis BT"
              @click="emit('link-tool', 'mantis')"
            />
          </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800 flex justify-end">
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-arrow-right"
            label="Vue détaillée Mantis"
            @click="emit('switch-tab', 'mantis')"
          />
        </div>
      </UCard>

      <!-- 4. Widget Jenkins CI -->
      <UCard :ui="{ body: 'p-5 space-y-4 flex flex-col justify-between h-full' }">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-5 h-5" />
              </div>
              <div>
                <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">Jenkins CI</h4>
                <p class="text-[11px] text-neutral-500">Pipelines & Builds</p>
              </div>
            </div>
            <UBadge
              :color="jenkinsPi ? 'info' : 'neutral'"
              variant="subtle"
              size="xs"
            >
              {{ jenkinsPi ? 'Connecté' : 'Non lié' }}
            </UBadge>
          </div>

          <div v-if="jenkinsPi" class="mt-4 space-y-3">
            <div class="bg-neutral-50 dark:bg-neutral-900/60 p-3 rounded-xl border border-neutral-200/60 dark:border-neutral-800/60">
              <div class="flex items-center justify-between">
                <p class="text-[11px] text-neutral-500 font-medium">Dossier des jobs</p>
                <UBadge color="info" variant="subtle" size="xs">
                  {{ jenkinsJobsCount }} job(s)
                </UBadge>
              </div>
              <p class="text-sm font-bold text-neutral-900 dark:text-neutral-100 truncate mt-0.5" :title="jenkinsFolder">
                {{ jenkinsFolderDisplay }}
              </p>
              <p class="text-[11px] text-neutral-500 mt-1 flex items-center gap-1.5 flex-wrap">
                <span class="text-emerald-600 font-medium">{{ jenkinsSuccessCount }} succès</span>
                <span v-if="jenkinsFailureCount > 0" class="text-red-600 font-medium">• {{ jenkinsFailureCount }} échec(s)</span>
                <span v-if="jenkinsBuildingCount > 0" class="text-sky-600 font-medium animate-pulse">• {{ jenkinsBuildingCount }} en cours</span>
              </p>
            </div>

            <div class="flex items-center gap-2">
              <UButton
                v-if="jenkinsExternalUrl"
                :to="jenkinsExternalUrl"
                target="_blank"
                size="xs"
                color="primary"
                variant="soft"
                icon="i-heroicons-arrow-top-right-on-square"
                label="Ouvrir Jenkins"
                class="flex-1 justify-center"
              />
            </div>
          </div>

          <div v-else class="mt-4 py-4 text-center space-y-2">
            <p class="text-xs text-neutral-500">Supervisez l'exécution de vos pipelines et builds.</p>
            <UButton
              size="xs"
              color="primary"
              variant="soft"
              icon="i-heroicons-plus"
              label="Associer Jenkins CI"
              @click="emit('link-tool', 'jenkins')"
            />
          </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800 flex justify-end">
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-arrow-right"
            label="Vue détaillée Jenkins"
            @click="emit('switch-tab', 'jenkins')"
          />
        </div>
      </UCard>
    </div>

    <!-- Section secondaire : Informations & Intégrations supplémentaires -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Récapitulatif du projet & Organisation -->
      <UCard :ui="{ body: 'p-5 space-y-3' }">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-information-circle" class="w-5 h-5 text-primary-500" />
            <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
              Informations du projet
            </h4>
          </div>
          <UButton
            variant="ghost"
            color="neutral"
            size="xs"
            icon="i-heroicons-arrow-right"
            label="Tous les détails"
            @click="emit('switch-tab', 'settings', 'general')"
          />
        </div>

        <div class="space-y-2 text-xs">
          <div class="flex items-center justify-between py-1 border-b border-neutral-100 dark:border-neutral-800/50">
            <span class="text-neutral-500">Nom du projet</span>
            <span class="font-bold text-neutral-900 dark:text-neutral-100">{{ project.name || '-' }}</span>
          </div>

          <div class="flex items-center justify-between py-1 border-b border-neutral-100 dark:border-neutral-800/50">
            <span class="text-neutral-500">Organisation</span>
            <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ organisationName || 'Non rattachée' }}</span>
          </div>

          <div class="flex items-center justify-between py-1">
            <span class="text-neutral-500">Identifiant API</span>
            <code class="font-mono text-[11px] text-neutral-700 dark:text-neutral-300">{{ project['@id'] || '-' }}</code>
          </div>
        </div>
      </UCard>

      <!-- Gestion rapide des intégrations -->
      <UCard :ui="{ body: 'p-5 space-y-3' }">
        <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-squares-plus" class="w-5 h-5 text-primary-500" />
            <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
              Écosystème DevOps
            </h4>
          </div>
          <UButton
            variant="ghost"
            color="primary"
            size="xs"
            icon="i-heroicons-plus"
            label="Associer un outil"
            @click="emit('link-tool')"
          />
        </div>

        <div v-if="projectIntegrations.length > 0" class="space-y-2">
          <div
            v-for="pi in projectIntegrations"
            :key="pi['@id'] || pi.id"
            class="flex items-center justify-between p-2.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/60 dark:border-neutral-700/60"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <UIcon :name="getTypeIcon(getIntegrationType(pi))" class="w-4 h-4 shrink-0 text-neutral-500" />
              <span class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 truncate">
                {{ getIntegrationName(pi) }}
              </span>
              <UBadge
                :color="getTypeBadgeColor(getIntegrationType(pi))"
                variant="subtle"
                size="xs"
                class="uppercase text-[9px]"
              >
                {{ getIntegrationType(pi) }}
              </UBadge>
            </div>

            <div class="flex items-center gap-1">
              <UButton
                v-if="getExternalUrl(pi)"
                :to="getExternalUrl(pi)!"
                target="_blank"
                variant="ghost"
                color="primary"
                size="xs"
                icon="i-heroicons-arrow-top-right-on-square"
              />
              <UButton
                variant="ghost"
                color="neutral"
                size="xs"
                icon="i-heroicons-pencil-square"
                @click="emit('edit-pi', pi)"
              />
            </div>
          </div>
        </div>

        <div v-else class="text-center py-4 text-xs text-neutral-400 italic">
          Aucun connecteur associé pour l'instant.
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import { useProjectIntegrationLiveDataStore } from "~/stores/projectIntegration/liveData";
import type { GiteaLiveData, SonarQubeLiveData, MantisLiveData, JenkinsLiveData } from "~/types/liveData";
import {
  getIntegration,
  getIntegrationType,
  getIntegrationName,
  getExternalUrl,
  getTypeIcon,
  getTypeBadgeColor,
  getServer,
  getServerBaseUrl,
  getTargetDisplay,
} from "~/utils/integration";

const props = defineProps<{
  project: Project;
  projectIntegrations: ProjectIntegration[];
  allIntegrations: Integration[];
}>();

const emit = defineEmits<{
  (e: "switch-tab", tabId: string, subTabId?: string): void;
  (e: "link-tool", toolType?: string): void;
  (e: "edit-pi", pi: ProjectIntegration): void;
  (e: "edit-project"): void;
}>();

const organisationName = computed(() => {
  const org = props.project.organisation;
  if (!org) return "";
  if (typeof org === "object" && org !== null) {
    return (org as any).name || (org as any)["@id"] || "";
  }
  return String(org);
});

// Gitea
const giteaPi = computed(() => {
  return props.projectIntegrations.find((pi) => getIntegrationType(pi) === "gitea");
});

const giteaRepo = computed(() => {
  return giteaPi.value?.parameters?.repository || "";
});

const giteaBranch = computed(() => {
  return giteaPi.value?.parameters?.branch || "main";
});

const giteaExternalUrl = computed(() => {
  return giteaPi.value ? getExternalUrl(giteaPi.value) : null;
});

// SonarQube
const sonarPi = computed(() => {
  return props.projectIntegrations.find((pi) => getIntegrationType(pi) === "sonarqube");
});

const sonarKey = computed(() => {
  return sonarPi.value?.parameters?.project_key || "";
});

const sonarExternalUrl = computed(() => {
  return sonarPi.value ? getExternalUrl(sonarPi.value) : null;
});

// Mantis
const mantisPi = computed(() => {
  return props.projectIntegrations.find((pi) => getIntegrationType(pi) === "mantis");
});

const mantisId = computed(() => {
  return mantisPi.value?.parameters?.project_id || "";
});

const mantisProjectName = computed(() => {
  return mantisPi.value?.parameters?.project_name || "";
});

const mantisTargetDisplay = computed(() => {
  return getTargetDisplay(mantisPi.value);
});

const mantisExternalUrl = computed(() => {
  return mantisPi.value ? getExternalUrl(mantisPi.value) : null;
});

// Jenkins
const jenkinsPi = computed(() => {
  return props.projectIntegrations.find((pi) => getIntegrationType(pi) === "jenkins");
});

const jenkinsFolder = computed(() => {
  const p = jenkinsPi.value?.parameters || {};
  return p.folder || p.folder_path || p.job_folder || p.job || p.job_name || "";
});

const jenkinsFolderDisplay = computed(() => {
  if (jenkinsLiveData.value?.folderName) {
    return jenkinsLiveData.value.folderName;
  }
  const f = jenkinsFolder.value;
  if (!f) return "Jenkins CI";
  const clean = f.replace(/\/$/, "");
  const parts = clean.split("/");
  return parts[parts.length - 1] || f;
});

const jenkinsJobsCount = computed(() => {
  if (jenkinsLiveData.value?.stats?.total !== undefined) {
    return jenkinsLiveData.value.stats.total;
  }
  const p = jenkinsPi.value?.parameters || {};
  if (Array.isArray(p.jobs)) return p.jobs.length;
  if (p.jobs_count) return Number(p.jobs_count);
  return 0;
});

const jenkinsSuccessCount = computed(() => {
  return jenkinsLiveData.value?.stats?.success ?? 0;
});

const jenkinsFailureCount = computed(() => {
  return jenkinsLiveData.value?.stats?.failure ?? 0;
});

const jenkinsBuildingCount = computed(() => {
  return jenkinsLiveData.value?.stats?.building ?? 0;
});

const jenkinsExternalUrl = computed(() => {
  return jenkinsPi.value ? getExternalUrl(jenkinsPi.value) : null;
});

const liveDataStore = useProjectIntegrationLiveDataStore();

const giteaLiveData = computed<GiteaLiveData | undefined>(() =>
  giteaPi.value?.id ? liveDataStore.getData<GiteaLiveData>(giteaPi.value.id) : undefined
);
const sonarLiveData = computed<SonarQubeLiveData | undefined>(() =>
  sonarPi.value?.id ? liveDataStore.getData<SonarQubeLiveData>(sonarPi.value.id) : undefined
);
const mantisLiveData = computed<MantisLiveData | undefined>(() =>
  mantisPi.value?.id ? liveDataStore.getData<MantisLiveData>(mantisPi.value.id) : undefined
);
const jenkinsLiveData = computed<JenkinsLiveData | undefined>(() =>
  jenkinsPi.value?.id ? liveDataStore.getData<JenkinsLiveData>(jenkinsPi.value.id) : undefined
);

onMounted(() => {
  if (giteaPi.value?.id) liveDataStore.fetchLiveData<GiteaLiveData>(giteaPi.value.id);
  if (sonarPi.value?.id) liveDataStore.fetchLiveData<SonarQubeLiveData>(sonarPi.value.id);
  if (mantisPi.value?.id) liveDataStore.fetchLiveData<MantisLiveData>(mantisPi.value.id);
  if (jenkinsPi.value?.id) liveDataStore.fetchLiveData<JenkinsLiveData>(jenkinsPi.value.id);
});

watch(
  () => [giteaPi.value?.id, sonarPi.value?.id, mantisPi.value?.id, jenkinsPi.value?.id],
  ([gId, sId, mId, jId]) => {
    if (gId) liveDataStore.fetchLiveData<GiteaLiveData>(gId);
    if (sId) liveDataStore.fetchLiveData<SonarQubeLiveData>(sId);
    if (mId) liveDataStore.fetchLiveData<MantisLiveData>(mId);
    if (jId) liveDataStore.fetchLiveData<JenkinsLiveData>(jId);
  }
);

const sonarQualityGateStatus = computed(() => sonarLiveData.value?.qualityGate?.status || "UNKNOWN");
const sonarQualityGateLabel = computed(() => {
  if (sonarQualityGateStatus.value === "OK") return "Conforme";
  if (sonarQualityGateStatus.value === "ERROR") return "Non conforme";
  if (sonarQualityGateStatus.value === "WARN") return "Avertissement";
  return sonarPi.value ? "Connecté" : "Non configuré";
});
const sonarQualityGateColor = computed(() => {
  if (sonarQualityGateStatus.value === "OK") return "text-emerald-600 dark:text-emerald-400";
  if (sonarQualityGateStatus.value === "ERROR") return "text-rose-600 dark:text-rose-400";
  return "text-neutral-500";
});
const sonarQualityGateIcon = computed(() => {
  if (sonarQualityGateStatus.value === "OK") return "i-heroicons-check-circle";
  if (sonarQualityGateStatus.value === "ERROR") return "i-heroicons-x-circle";
  return "i-heroicons-shield-check";
});

// Active integrations count
const activeIntegrationsCount = computed(() => {
  return props.projectIntegrations.filter((pi) => {
    const status = getIntegration(pi)?.status?.toLowerCase();
    return status === "healthy" || status === "ok" || status === "active" || !status;
  }).length;
});

// Overall health
const overallHealthColor = computed(() => {
  if (props.projectIntegrations.length === 0) return "neutral";
  const hasError = props.projectIntegrations.some((pi) => {
    const status = getIntegration(pi)?.status?.toLowerCase();
    return status === "error" || status === "failed";
  });
  if (hasError) return "error";
  return "success";
});

const overallHealthDot = computed(() => {
  if (props.projectIntegrations.length === 0) return "bg-neutral-400";
  return overallHealthColor.value === "success" ? "bg-emerald-500" : "bg-red-500";
});

const overallHealthText = computed(() => {
  if (props.projectIntegrations.length === 0) return "Aucun service associé";
  if (overallHealthColor.value === "success") {
    return `${props.projectIntegrations.length} service(s) opérationnel(s)`;
  }
  return "Incident détecté sur un service";
});

function copyGiteaClone() {
  if (!giteaPi.value) return;
  const server = getServer(giteaPi.value);
  const base = getServerBaseUrl(server);
  const repo = giteaRepo.value;
  if (!base || !repo) return;
  const url = `${base}/${repo.replace(/\.git$/, "")}.git`;
  if (navigator?.clipboard) {
    navigator.clipboard.writeText(`git clone ${url}`);
  }
}
</script>
