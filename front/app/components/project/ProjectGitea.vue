<template>
  <div class="space-y-6">
    <!-- Cas 1 : Gitea est configuré pour ce projet -->
    <div v-if="projectIntegration" class="space-y-6">
      <!-- En-tête du dépôt Gitea (Contenu) -->
      <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/20">
              <UIcon name="i-heroicons-code-bracket" class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                  {{ repositoryName || 'Dépôt Gitea' }}
                </h3>
                <UBadge color="warning" variant="subtle" size="xs">
                  Gitea Forge
                </UBadge>
                <UBadge
                  v-if="branch"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="flex items-center gap-1 font-mono text-[11px]"
                >
                  <UIcon name="i-heroicons-hashtag" class="w-3 h-3" />
                  {{ branch }}
                </UBadge>
                <UBadge v-if="isPrivate" color="neutral" variant="subtle" size="xs">
                  Privé
                </UBadge>
              </div>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-3 flex-wrap">
                <span v-if="commits.length > 0" class="flex items-center gap-1">
                  <UIcon name="i-heroicons-clock" class="w-3.5 h-3.5 text-neutral-400" />
                  Dernier commit : {{ formatDate(commits[0]?.date) }}
                </span>
                <span v-if="tags.length > 0" class="flex items-center gap-1">
                  <UIcon name="i-heroicons-tag" class="w-3.5 h-3.5 text-neutral-400" />
                  {{ tags[0]?.name }}
                </span>
                <span v-if="stats" class="flex items-center gap-1">
                  <UIcon name="i-heroicons-document-duplicate" class="w-3.5 h-3.5 text-neutral-400" />
                  {{ commits.length }} commit(s) récents
                </span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <UButton
              color="neutral"
              variant="outline"
              size="sm"
              icon="i-heroicons-arrow-path"
              :loading="isLoading"
              label="Actualiser"
              @click="refreshData"
            />
            <UButton
              v-if="externalUrl"
              :to="externalUrl"
              target="_blank"
              color="warning"
              variant="solid"
              size="sm"
              icon="i-heroicons-arrow-top-right-on-square"
              label="Ouvrir dans Gitea"
            />
            <UButton
              color="neutral"
              variant="outline"
              size="sm"
              icon="i-heroicons-cog-6-tooth"
              label="Paramètres de l'intégration"
              @click="emit('configure')"
            />
          </div>
        </div>

        <!-- Alerte d'erreur de chargement -->
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-heroicons-exclamation-triangle"
          title="Erreur de synchronisation Gitea"
          :description="errorMessage"
          close
          @close="liveDataStore.clear(projectIntegration?.id)"
        />

        <!-- Bloc de clonage compact -->
        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 space-y-2">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-command-line" class="w-4 h-4 text-amber-500" />
              <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                Cloner le dépôt :
              </span>
            </div>
            <div class="flex items-center gap-1 bg-neutral-100 dark:bg-neutral-800 p-0.5 rounded-lg shrink-0">
              <UButton
                size="xs"
                :variant="cloneProtocol === 'https' ? 'solid' : 'ghost'"
                :color="cloneProtocol === 'https' ? 'primary' : 'neutral'"
                label="HTTPS"
                @click="cloneProtocol = 'https'"
              />
              <UButton
                size="xs"
                :variant="cloneProtocol === 'ssh' ? 'solid' : 'ghost'"
                :color="cloneProtocol === 'ssh' ? 'primary' : 'neutral'"
                label="SSH"
                @click="cloneProtocol = 'ssh'"
              />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex-1 bg-neutral-100 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-lg px-3 py-1.5 font-mono text-xs text-neutral-800 dark:text-neutral-200 select-all overflow-x-auto whitespace-nowrap">
              {{ currentCloneUrl }}
            </div>
            <UButton
              color="neutral"
              variant="outline"
              size="xs"
              :icon="hasCopiedClone ? 'i-heroicons-check' : 'i-heroicons-clipboard-document'"
              :label="hasCopiedClone ? 'Copié !' : 'Copier'"
              @click="copyToClipboard(currentCloneUrl, 'clone')"
            />
          </div>
        </div>
      </UCard>

      <!-- Navigation du contenu Gitea (Sous-onglets) -->
      <div class="border-b border-neutral-200 dark:border-neutral-800 pb-2">
        <div class="flex items-center gap-2 overflow-x-auto scrollbar-none">
          <UButton
            v-for="sub in contentTabs"
            :key="sub.id"
            :variant="currentContentTab === sub.id ? 'solid' : 'ghost'"
            :color="currentContentTab === sub.id ? 'primary' : 'neutral'"
            size="sm"
            :icon="sub.icon"
            class="text-xs font-medium"
            @click="currentContentTab = sub.id"
          >
            <span>{{ sub.label }}</span>
            <UBadge
              v-if="sub.count !== undefined"
              color="neutral"
              variant="subtle"
              size="xs"
              class="ml-1 text-[10px]"
            >
              {{ sub.count }}
            </UBadge>
          </UButton>
        </div>
      </div>

      <!-- État de chargement initial -->
      <div v-if="isLoading && !liveData" class="space-y-4">
        <div class="p-8 text-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-amber-500 animate-spin mx-auto mb-2" />
          <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
            Chargement des données en direct depuis Gitea...
          </p>
          <p class="text-xs text-neutral-500 mt-1">
            Récupération des commits, branches, pull requests et fichiers du dépôt.
          </p>
        </div>
      </div>

      <!-- Contenu 1 : Commits récents -->
      <div v-else-if="currentContentTab === 'commits'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <UIcon name="i-heroicons-clock" class="w-4 h-4 text-amber-500" />
            Historique des commits récents
          </h4>
          <span class="text-xs text-neutral-500 font-mono">
            Branche : {{ branch || 'develop' }}
          </span>
        </div>

        <div v-if="commits.length > 0" class="space-y-2.5">
          <UCard
            v-for="commit in commits"
            :key="commit.sha"
            :ui="{ body: 'p-3.5 sm:p-4' }"
            class="transition hover:border-amber-500/40"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-start gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/20">
                  {{ getInitials(commit.author) }}
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate">
                    {{ commit.message }}
                  </p>
                  <div class="flex items-center gap-2 mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                    <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ commit.author }}</span>
                    <span>•</span>
                    <span>{{ formatDate(commit.date) }}</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <UBadge
                  color="neutral"
                  variant="outline"
                  size="xs"
                  class="font-mono text-[11px] flex items-center gap-1 cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-800"
                  @click="copyToClipboard(commit.sha, 'hash')"
                >
                  <UIcon name="i-heroicons-hashtag" class="w-3 h-3 text-neutral-400" />
                  {{ commit.shortSha }}
                </UBadge>
                <UButton
                  v-if="commit.url || externalUrl"
                  :to="commit.url || `${externalUrl}/commit/${commit.sha}`"
                  target="_blank"
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  icon="i-heroicons-arrow-top-right-on-square"
                  title="Voir le commit sur Gitea"
                />
              </div>
            </div>
          </UCard>
        </div>
        <div v-else class="p-8 text-center rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500">
          Aucun commit trouvé pour cette branche.
        </div>
      </div>

      <!-- Contenu 2 : Branches & Tags -->
      <div v-else-if="currentContentTab === 'branches'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Branches -->
        <UCard :ui="{ body: 'p-5 space-y-4' }">
          <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
            <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <UIcon name="i-heroicons-arrows-pointing-out" class="w-4 h-4 text-amber-500" />
              Branches actives
            </h4>
            <UBadge color="neutral" variant="subtle" size="xs">
              {{ branches.length }} branches
            </UBadge>
          </div>

          <div v-if="branches.length > 0" class="space-y-2">
            <div
              v-for="b in branches"
              :key="b.name"
              class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/60 dark:border-neutral-800 flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-xs font-bold text-neutral-900 dark:text-neutral-100">
                    {{ b.name }}
                  </span>
                  <UBadge v-if="b.isDefault" color="primary" variant="subtle" size="xs">
                    Par défaut
                  </UBadge>
                </div>
                <p class="text-[11px] text-neutral-500 mt-0.5">
                  Dernier commit : <code class="font-mono">{{ b.commitSha }}</code>
                </p>
              </div>

              <UButton
                v-if="externalUrl"
                :to="`${externalUrl}/src/branch/${b.name}`"
                target="_blank"
                variant="ghost"
                color="neutral"
                size="xs"
                icon="i-heroicons-arrow-top-right-on-square"
              />
            </div>
          </div>
          <div v-else class="text-center py-4 text-xs text-neutral-500">
            Aucune branche répertoriée.
          </div>
        </UCard>

        <!-- Releases / Tags -->
        <UCard :ui="{ body: 'p-5 space-y-4' }">
          <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
            <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <UIcon name="i-heroicons-tag" class="w-4 h-4 text-amber-500" />
              Releases & Versions
            </h4>
            <UBadge color="neutral" variant="subtle" size="xs">
              {{ tags.length }} versions
            </UBadge>
          </div>

          <div v-if="tags.length > 0" class="space-y-2 max-h-96 overflow-y-auto pr-1">
            <div
              v-for="(t, idx) in tags"
              :key="t.name"
              class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/60 dark:border-neutral-800 flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-xs font-bold text-neutral-900 dark:text-neutral-100">
                    {{ t.name }}
                  </span>
                  <UBadge v-if="idx === 0" color="success" variant="subtle" size="xs">
                    Dernière release
                  </UBadge>
                </div>
                <p class="text-[11px] text-neutral-500 mt-0.5">
                  Commit : <code class="font-mono">{{ t.commitSha }}</code>
                </p>
              </div>

              <UButton
                v-if="externalUrl"
                :to="`${externalUrl}/releases/tag/${t.name}`"
                target="_blank"
                variant="ghost"
                color="neutral"
                size="xs"
                icon="i-heroicons-arrow-down-tray"
                title="Consulter la release"
              />
            </div>
          </div>
          <div v-else class="text-center py-4 text-xs text-neutral-500">
            Aucun tag ou release disponible.
          </div>
        </UCard>
      </div>

      <!-- Contenu 3 : Pull Requests -->
      <div v-else-if="currentContentTab === 'pulls'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <UIcon name="i-heroicons-arrow-path" class="w-4 h-4 text-amber-500" />
            Pull Requests du projet
          </h4>
          <UButton
            v-if="externalUrl"
            :to="`${externalUrl}/pulls`"
            target="_blank"
            color="warning"
            variant="soft"
            size="xs"
            icon="i-heroicons-arrow-top-right-on-square"
            label="Toutes les Pull Requests"
          />
        </div>

        <div v-if="pullRequests.length > 0" class="space-y-2.5">
          <UCard
            v-for="pr in pullRequests"
            :key="pr.number"
            :ui="{ body: 'p-3.5 sm:p-4' }"
            class="transition hover:border-amber-500/40"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-start gap-3 min-w-0">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold"
                  :class="pr.state === 'open' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : (pr.merged ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20' : 'bg-neutral-500/10 text-neutral-600 dark:text-neutral-400 border border-neutral-500/20')"
                >
                  <UIcon :name="pr.state === 'open' ? 'i-heroicons-arrow-path' : (pr.merged ? 'i-heroicons-check' : 'i-heroicons-x-mark')" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                      {{ pr.title }}
                    </span>
                    <UBadge
                      :color="pr.state === 'open' ? 'success' : (pr.merged ? 'primary' : 'neutral')"
                      variant="subtle"
                      size="xs"
                    >
                      #{{ pr.number }} • {{ pr.state === 'open' ? 'Ouverte' : (pr.merged ? 'Fusionnée' : 'Fermée') }}
                    </UBadge>
                  </div>
                  <div class="flex items-center gap-2 mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                    <span>Par {{ pr.author }}</span>
                    <span>•</span>
                    <span>{{ formatDate(pr.createdAt) }}</span>
                  </div>
                </div>
              </div>

              <UButton
                v-if="pr.url || externalUrl"
                :to="pr.url || `${externalUrl}/pulls/${pr.number}`"
                target="_blank"
                variant="outline"
                color="neutral"
                size="xs"
                icon="i-heroicons-arrow-top-right-on-square"
                label="Examiner"
              />
            </div>
          </UCard>
        </div>
        <div v-else class="p-8 text-center rounded-xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500">
          Aucune Pull Request active ou archivée.
        </div>
      </div>

      <!-- Contenu 4 : Code & README -->
      <div v-else-if="currentContentTab === 'code'" class="space-y-6">
        <!-- Arborescence des fichiers -->
        <UCard :ui="{ body: 'p-4 sm:p-5 space-y-3' }">
          <div class="flex items-center justify-between pb-2 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-folder-open" class="w-5 h-5 text-amber-500" />
              <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">
                Structure du dépôt (racine)
              </h4>
            </div>
            <span class="font-mono text-xs text-neutral-500">
              {{ branch || 'develop' }}
            </span>
          </div>

          <div v-if="files.length > 0" class="divide-y divide-neutral-100 dark:divide-neutral-800/80 font-mono text-xs max-h-96 overflow-y-auto">
            <div
              v-for="file in files"
              :key="file.name"
              class="py-2.5 px-2 flex items-center justify-between hover:bg-neutral-50 dark:hover:bg-neutral-800/40 rounded-lg transition"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <UIcon
                  :name="file.type === 'dir' ? 'i-heroicons-folder' : 'i-heroicons-document-text'"
                  class="w-4 h-4 shrink-0"
                  :class="file.type === 'dir' ? 'text-amber-500' : 'text-neutral-400'"
                />
                <span class="font-medium text-neutral-800 dark:text-neutral-200 truncate">
                  {{ file.name }}
                </span>
              </div>
              <span class="text-[11px] text-neutral-400 shrink-0 font-sans">
                {{ file.type === 'dir' ? 'Dossier' : formatBytes(file.size) }}
              </span>
            </div>
          </div>
          <div v-else class="text-center py-4 text-xs text-neutral-500">
            Aucun fichier trouvé.
          </div>
        </UCard>

        <!-- Aperçu du README -->
        <UCard :ui="{ body: 'p-5 sm:p-6 space-y-4' }">
          <div class="flex items-center justify-between pb-3 border-b border-neutral-100 dark:border-neutral-800">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-book-open" class="w-5 h-5 text-amber-500" />
              <h4 class="font-bold text-sm text-neutral-900 dark:text-neutral-100">
                README.md
              </h4>
            </div>
            <UBadge v-if="readmeContent" color="neutral" variant="subtle" size="xs">
              {{ branch }}
            </UBadge>
          </div>

          <div v-if="readmeContent" class="bg-neutral-50 dark:bg-neutral-900/60 p-4 rounded-xl border border-neutral-200/80 dark:border-neutral-800 overflow-x-auto text-xs sm:text-sm leading-relaxed">
            <pre class="font-mono text-xs whitespace-pre-wrap text-neutral-800 dark:text-neutral-200">{{ readmeContent }}</pre>
          </div>

          <div v-else class="prose dark:prose-invert max-w-none text-xs sm:text-sm space-y-3 leading-relaxed">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
              {{ project?.name || 'Projet' }}
            </h2>
            <p class="text-neutral-600 dark:text-neutral-300">
              {{ project?.description || 'Module applicatif intégrant les pipelines DevOps et le cycle de vie logiciel.' }}
            </p>

            <div class="bg-neutral-100 dark:bg-neutral-900 p-3 rounded-xl border border-neutral-200 dark:border-neutral-800 font-mono text-xs space-y-1">
              <p class="text-neutral-500"># Cloner et initialiser le projet</p>
              <p class="text-primary-600 dark:text-primary-400">git clone {{ currentCloneUrl }}</p>
              <p class="text-primary-600 dark:text-primary-400">cd {{ repositoryName ? repositoryName.split('/').pop() : 'projet' }}</p>
            </div>
          </div>
        </UCard>
      </div>
    </div>

    <!-- Cas 2 : Gitea non configuré sur ce projet -->
    <UCard v-else :ui="{ body: 'p-8 sm:p-12 text-center' }">
      <div class="max-w-md mx-auto space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto border border-amber-500/20">
          <UIcon name="i-heroicons-code-bracket" class="w-8 h-8" />
        </div>
        <div>
          <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
            Aucun dépôt Git connecté
          </h3>
          <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1.5 leading-relaxed">
            Ce projet n'est pas encore relié à un dépôt Gitea. Pour afficher l'arborescence des fichiers, les commits et les branches, configurez l'intégration dans les paramètres.
          </p>
        </div>

        <div class="pt-2">
          <UButton
            color="warning"
            icon="i-heroicons-cog-6-tooth"
            label="Configurer l'intégration Gitea"
            size="md"
            @click="emit('configure')"
          />
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import { useProjectIntegrationLiveDataStore } from "~/stores/projectIntegration/liveData";
import type { GiteaLiveData } from "~/types/liveData";
import {
  getServer,
  getServerBaseUrl,
  getExternalUrl,
} from "~/utils/integration";

const props = defineProps<{
  project?: Project;
  projectIntegration?: ProjectIntegration | null;
  allIntegrations?: Integration[];
}>();

const emit = defineEmits<{
  (e: "configure"): void;
}>();

const liveDataStore = useProjectIntegrationLiveDataStore();

// Chargement automatique des données réelles
const liveData = computed<GiteaLiveData | undefined>(() =>
  props.projectIntegration?.id ? liveDataStore.getData<GiteaLiveData>(props.projectIntegration.id) : undefined
);

const isLoading = computed(() =>
  props.projectIntegration?.id ? liveDataStore.isLoading(props.projectIntegration.id) : false
);

const errorMessage = computed(() =>
  props.projectIntegration?.id ? liveDataStore.getError(props.projectIntegration.id) : undefined
);

onMounted(() => {
  if (props.projectIntegration?.id) {
    liveDataStore.fetchLiveData<GiteaLiveData>(props.projectIntegration.id);
  }
});

watch(
  () => props.projectIntegration?.id,
  (newId) => {
    if (newId) {
      liveDataStore.fetchLiveData<GiteaLiveData>(newId);
    }
  }
);

async function refreshData() {
  if (props.projectIntegration?.id) {
    await liveDataStore.fetchLiveData<GiteaLiveData>(props.projectIntegration.id, true);
  }
}

// Navigation sous-onglets de contenu
const currentContentTab = ref<"commits" | "branches" | "pulls" | "code">("commits");

// Données réelles
const commits = computed(() => liveData.value?.commits || []);
const branches = computed(() => liveData.value?.branches || []);
const tags = computed(() => liveData.value?.tags || []);
const pullRequests = computed(() => liveData.value?.pullRequests || []);
const files = computed(() => liveData.value?.files || []);
const readmeContent = computed(() => liveData.value?.readme || null);
const stats = computed(() => liveData.value?.stats);
const isPrivate = computed(() => liveData.value?.isPrivate ?? true);

const contentTabs = computed(() => [
  { id: "commits" as const, label: "Commits", icon: "i-heroicons-clock", count: commits.value.length },
  { id: "branches" as const, label: "Branches & Tags", icon: "i-heroicons-arrows-pointing-out", count: branches.value.length },
  { id: "pulls" as const, label: "Pull Requests", icon: "i-heroicons-arrow-path", count: pullRequests.value.length },
  { id: "code" as const, label: "Fichiers & Code", icon: "i-heroicons-folder-open", count: files.value.length },
]);

// Paramètres de liaison
const repositoryName = computed(() => {
  return liveData.value?.fullName || liveData.value?.name || props.projectIntegration?.parameters?.repository || "";
});

const branch = computed(() => {
  return liveData.value?.defaultBranch || props.projectIntegration?.parameters?.branch || "develop";
});

const externalUrl = computed(() => {
  if (liveData.value?.url) return liveData.value.url;
  if (!props.projectIntegration) return null;
  return getExternalUrl(props.projectIntegration);
});

// Clonage Git
const cloneProtocol = ref<"https" | "ssh">("https");
const hasCopiedClone = ref(false);

const currentCloneUrl = computed(() => {
  if (cloneProtocol.value === "ssh" && liveData.value?.sshUrl) {
    return liveData.value.sshUrl;
  }
  if (cloneProtocol.value === "https" && liveData.value?.cloneUrl) {
    return liveData.value.cloneUrl;
  }

  const server = props.projectIntegration ? getServer(props.projectIntegration) : null;
  const baseUrl = server ? getServerBaseUrl(server) : "http://gitea.example.com";
  const repo = repositoryName.value || "projet/repo";

  if (cloneProtocol.value === "ssh") {
    const host = server?.host || "gitea.example.com";
    return `git@${host}:${repo}.git`;
  }
  return `${baseUrl}/${repo}.git`;
});

function copyToClipboard(text: string, _type?: string) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text);
    hasCopiedClone.value = true;
    setTimeout(() => {
      hasCopiedClone.value = false;
    }, 2000);
  }
}

function getInitials(name: string): string {
  if (!name) return "??";
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
}

function formatDate(dateStr?: string): string {
  if (!dateStr) return "Récemment";
  try {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat("fr-FR", {
      dateStyle: "short",
      timeStyle: "short",
    }).format(d);
  } catch {
    return dateStr;
  }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return "0 o";
  const k = 1024;
  const sizes = ["o", "Ko", "Mo", "Go"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}
</script>
