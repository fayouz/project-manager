<template>
  <div class="space-y-6">
    <!-- En-tête de la fiche de détails -->
    <UCard :ui="{ body: 'p-5 sm:p-6' }">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0 border border-primary-500/20">
            <UIcon name="i-heroicons-information-circle" class="w-7 h-7" />
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                {{ project.name || 'Projet sans nom' }}
              </h3>
              <UBadge color="primary" variant="subtle" size="xs">
                Projet
              </UBadge>
              <UBadge
                v-if="project.id"
                color="neutral"
                variant="outline"
                size="xs"
                class="font-mono text-[11px]"
              >
                #{{ project.id }}
              </UBadge>
            </div>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
              Fiche détaillée et métadonnées administratives
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <UButton
            color="primary"
            variant="solid"
            size="sm"
            icon="i-heroicons-pencil-square"
            label="Modifier le projet"
            @click="emit('edit')"
          />
        </div>
      </div>
    </UCard>

    <!-- Informations principales en grille -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- 1. Informations générales -->
      <UCard :ui="{ body: 'p-5 space-y-4' }">
        <div class="flex items-center gap-2 border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <UIcon name="i-heroicons-identification" class="w-5 h-5 text-primary-500" />
          <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
            Identité du projet
          </h4>
        </div>

        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between py-1">
            <span class="text-neutral-500 dark:text-neutral-400 text-xs">Nom complet :</span>
            <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ project.name || '-' }}</span>
          </div>

          <div class="flex items-center justify-between py-1">
            <span class="text-neutral-500 dark:text-neutral-400 text-xs">Identifiant interne :</span>
            <span class="font-mono text-xs font-semibold text-neutral-900 dark:text-neutral-100">
              {{ project.id || getIdFromIri(project['@id']) || '-' }}
            </span>
          </div>

          <div class="flex items-center justify-between py-1">
            <span class="text-neutral-500 dark:text-neutral-400 text-xs">Ressource API (IRI) :</span>
            <div class="flex items-center gap-1.5">
              <code class="text-xs font-mono text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded">
                {{ project['@id'] || '-' }}
              </code>
              <UButton
                v-if="project['@id']"
                variant="ghost"
                color="neutral"
                size="xs"
                :icon="hasCopiedIri ? 'i-heroicons-check' : 'i-heroicons-clipboard'"
                title="Copier l'IRI"
                @click="copyIri"
              />
            </div>
          </div>
        </div>
      </UCard>

      <!-- 2. Organisation associée -->
      <UCard :ui="{ body: 'p-5 space-y-4' }">
        <div class="flex items-center gap-2 border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <UIcon name="i-heroicons-building-office-2" class="w-5 h-5 text-primary-500" />
          <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
            Organisation de rattachement
          </h4>
        </div>

        <div v-if="organisation" class="space-y-3 text-sm">
          <div class="flex items-center justify-between py-1">
            <span class="text-neutral-500 dark:text-neutral-400 text-xs">Nom de l'organisation :</span>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ organisationName }}</span>
              <UButton
                v-if="organisationId"
                :to="`/organisations`"
                variant="ghost"
                color="primary"
                size="xs"
                icon="i-heroicons-arrow-top-right-on-square"
                title="Voir les organisations"
              />
            </div>
          </div>

          <div v-if="organisationIri" class="flex items-center justify-between py-1">
            <span class="text-neutral-500 dark:text-neutral-400 text-xs">Référence IRI :</span>
            <code class="text-xs font-mono text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded">
              {{ organisationIri }}
            </code>
          </div>
        </div>

        <div v-else class="text-neutral-500 text-sm py-4 text-center italic">
          Aucune organisation associée à ce projet.
        </div>
      </UCard>
    </div>

    <!-- Résumé de l'environnement technique -->
    <UCard :ui="{ body: 'p-5 space-y-4' }">
      <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
        <div class="flex items-center gap-2">
          <UIcon name="i-heroicons-cpu-chip" class="w-5 h-5 text-primary-500" />
          <h4 class="font-semibold text-sm text-neutral-900 dark:text-neutral-100">
            Écosystème & Connecteurs
          </h4>
        </div>
        <UBadge color="neutral" variant="outline" size="xs">
          {{ projectIntegrations.length }} service(s) configuré(s)
        </UBadge>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-code-bracket" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 font-medium truncate">Forge Git</p>
            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate">
              {{ hasGitea ? 'Gitea lié' : 'Non configuré' }}
            </p>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-shield-check" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 font-medium truncate">Qualité Code</p>
            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate">
              {{ hasSonarQube ? 'SonarQube lié' : 'Non configuré' }}
            </p>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-bug-ant" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 font-medium truncate">Suivi Bogues</p>
            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate">
              {{ hasMantis ? 'Mantis BT lié' : 'Non configuré' }}
            </p>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-arrow-path-rounded-square" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 font-medium truncate">CI / CD</p>
            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate">
              {{ hasJenkins ? 'Jenkins lié' : 'Non configuré' }}
            </p>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/80 dark:border-neutral-700/80 flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-cube" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400 font-medium truncate">Artefacts</p>
            <p class="text-xs font-bold text-neutral-900 dark:text-neutral-100 truncate">
              {{ hasNexus ? 'Nexus lié' : 'Non configuré' }}
            </p>
          </div>
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { Project } from "~/types/project";
import type { ProjectIntegration } from "~/types/projectIntegration";
import { getIntegrationType } from "~/utils/integration";
import { getIdFromIri } from "~/utils/resource";

const props = defineProps<{
  project: Project;
  projectIntegrations: ProjectIntegration[];
}>();

const emit = defineEmits<{
  (e: "edit"): void;
  (e: "back"): void;
}>();

const hasCopiedIri = ref(false);

const organisation = computed(() => {
  return props.project.organisation;
});

const organisationName = computed(() => {
  if (!organisation.value) return "";
  if (typeof organisation.value === "object" && organisation.value !== null) {
    return organisation.value.name || organisation.value["@id"] || "Organisation";
  }
  return String(organisation.value);
});

const organisationIri = computed(() => {
  if (typeof organisation.value === "object" && organisation.value !== null) {
    return organisation.value["@id"] || "";
  }
  return typeof organisation.value === "string" ? organisation.value : "";
});

const organisationId = computed(() => {
  return getIdFromIri(organisationIri.value);
});

const hasGitea = computed(() => {
  return props.projectIntegrations.some((pi) => getIntegrationType(pi) === "gitea");
});

const hasSonarQube = computed(() => {
  return props.projectIntegrations.some((pi) => getIntegrationType(pi) === "sonarqube");
});

const hasMantis = computed(() => {
  return props.projectIntegrations.some((pi) => getIntegrationType(pi) === "mantis");
});

const hasJenkins = computed(() => {
  return props.projectIntegrations.some((pi) => getIntegrationType(pi) === "jenkins");
});

const hasNexus = computed(() => {
  return props.projectIntegrations.some((pi) => getIntegrationType(pi) === "nexus");
});

function copyIri() {
  if (props.project["@id"] && navigator?.clipboard) {
    navigator.clipboard.writeText(props.project["@id"]);
    hasCopiedIri.value = true;
    setTimeout(() => {
      hasCopiedIri.value = false;
    }, 2000);
  }
}
</script>
