<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Paramètres LDAP">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #title>
        <div class="flex items-center gap-2">
          <UIcon name="i-heroicons-adjustments-horizontal" class="w-5 h-5 text-primary" />
          <span class="font-semibold text-base text-neutral-900 dark:text-neutral-100">Configuration & Synchronisation LDAP</span>
        </div>
      </template>

      <template #right>
        <UButton
          size="sm"
          color="primary"
          variant="subtle"
          icon="i-heroicons-users"
          to="/users"
          label="Voir les utilisateurs"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6 max-w-5xl">
      <!-- En-tête de la page -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
            Annuaire d'entreprise (LDAP / Active Directory)
          </h1>
          <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
            Configurez la liaison avec votre annuaire LDAP pour importer vos utilisateurs, synchroniser les profils et activer l'authentification hybride.
          </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <UBadge
            :color="form.enabled ? 'success' : 'neutral'"
            variant="soft"
            size="md"
          >
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full" :class="form.enabled ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
              {{ form.enabled ? 'LDAP Actif' : 'LDAP Inactif' }}
            </span>
          </UBadge>
        </div>
      </div>

      <!-- Alertes de retour -->
      <UAlert
        v-if="statusMessage"
        :color="statusType === 'success' ? 'success' : 'error'"
        variant="subtle"
        :icon="statusType === 'success' ? 'i-heroicons-check-circle' : 'i-heroicons-exclamation-triangle'"
        :title="statusMessage"
        :description="statusDetail"
      />

      <!-- Formulaire découpé en plusieurs cartes -->
      <form @submit.prevent="saveConfiguration" class="space-y-6">
        <!-- Carte 1 : Paramètres de connexion serveur -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                  <UIcon name="i-heroicons-server-stack" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Serveur & Connexion LDAP</h3>
                  <p class="text-xs text-neutral-500">Coordonnées du serveur d'annuaire et compte de service</p>
                </div>
              </div>
              <UBadge
                :color="form.enabled ? 'success' : 'neutral'"
                variant="subtle"
                size="sm"
              >
                {{ form.enabled ? 'Activé' : 'Désactivé' }}
              </UBadge>
            </div>
          </template>

          <div class="space-y-4">
            <div class="flex items-center justify-between p-3.5 rounded-lg bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
              <div>
                <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Activer l'intégration LDAP</span>
                <p class="text-xs text-neutral-500">Autorise l'authentification des utilisateurs et la synchronisation des comptes</p>
              </div>
              <USwitch v-model="form.enabled" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <UFormField label="Hôte LDAP" required>
                  <UInput
                    v-model="form.host"
                    placeholder="ldap.mon-entreprise.local"
                    class="w-full"
                    icon="i-heroicons-server"
                    required
                  />
                </UFormField>
              </div>

              <div>
                <UFormField label="Port" required>
                  <UInput
                    v-model.number="form.port"
                    type="number"
                    placeholder="389"
                    class="w-full"
                    required
                  />
                </UFormField>
              </div>
            </div>

            <UFormField label="Base DN (Racine de recherche)" required>
              <UInput
                v-model="form.baseDn"
                placeholder="dc=mon-entreprise,dc=local"
                class="w-full"
                icon="i-heroicons-folder-open"
                required
              />
            </UFormField>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <UFormField label="Bind DN (Compte de service)">
                <UInput
                  v-model="form.bindDn"
                  placeholder="cn=admin,dc=mon-entreprise,dc=local"
                  class="w-full"
                  icon="i-heroicons-user"
                />
              </UFormField>

              <UFormField label="Mot de passe du Bind DN">
                <UInput
                  v-model="form.bindPassword"
                  type="password"
                  placeholder="••••••••"
                  class="w-full"
                  icon="i-heroicons-key"
                />
              </UFormField>
            </div>
          </div>
        </UCard>

        <!-- Carte 2 : Mapping des attributs LDAP -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                  <UIcon name="i-heroicons-arrows-right-left" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Mapping des attributs</h3>
                  <p class="text-xs text-neutral-500">Correspondance entre les champs de l'annuaire et le profil utilisateur</p>
                </div>
              </div>
              <UBadge color="info" variant="subtle" size="sm">
                Attributs LDAP
              </UBadge>
            </div>
          </template>

          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <UFormField label="Attribut Image / Photo">
                <UInput
                  v-model="form.imageAttribute"
                  placeholder="jpegPhoto"
                  class="w-full"
                  icon="i-heroicons-photo"
                />
              </UFormField>

              <UFormField label="Attribut Email">
                <UInput
                  v-model="form.attributeMapping!.email"
                  placeholder="mail"
                  class="w-full"
                  icon="i-heroicons-envelope"
                />
              </UFormField>

              <UFormField label="Attribut Nom d'utilisateur">
                <UInput
                  v-model="form.attributeMapping!.username"
                  placeholder="sAMAccountName"
                  class="w-full"
                  icon="i-heroicons-identification"
                />
              </UFormField>
            </div>

            <div class="p-3.5 rounded-lg bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 flex items-start gap-3">
              <UIcon name="i-heroicons-information-circle" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
              <div class="text-xs text-neutral-600 dark:text-neutral-400 space-y-1">
                <p class="font-medium text-neutral-800 dark:text-neutral-200">Attributs automatiquement reconnus :</p>
                <p>
                  Les informations complémentaires (<strong>prénom</strong> : <code>givenName</code>, <strong>nom</strong> : <code>sn</code>, <strong>nom complet</strong> : <code>displayName</code>, <strong>fonction</strong> : <code>title</code>, <strong>département</strong> : <code>department</code>, <strong>manager</strong> : <code>manager</code>) sont automatiquement extraites de l'annuaire lors de la synchronisation.
                </p>
              </div>
            </div>
          </div>
        </UCard>

        <!-- Carte 3 : Filtres de recherche & Regex -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                  <UIcon name="i-heroicons-funnel" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Filtres de recherche (Regex)</h3>
                  <p class="text-xs text-neutral-500">Filtrer les comptes synchronisés ou autorisés à se connecter via des expressions régulières</p>
                </div>
              </div>
              <UBadge
                :color="(form.searchFilter || form.queryRegex || form.searchFilterRegex || (form.searchFilters && form.searchFilters.length > 0)) ? 'primary' : 'neutral'"
                variant="subtle"
                size="sm"
              >
                {{ (form.searchFilter || form.queryRegex || form.searchFilterRegex || (form.searchFilters && form.searchFilters.length > 0)) ? 'Filtres actifs' : 'Aucun filtre' }}
              </UBadge>
            </div>
          </template>

          <div class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <UFormField
                label="Modèle de requête LDAP (Query Filter)"
                description="Modèle de filtre LDAP pour la recherche d'utilisateur (ex: (cn={username}), (sAMAccountName={username}) ou (|(sAMAccountName={username})(cn={username})))"
              >
                <UInput
                  v-model="form.searchFilter"
                  placeholder="(cn={username})"
                  class="w-full font-mono text-xs"
                  icon="i-heroicons-magnifying-glass"
                />
              </UFormField>

              <UFormField
                label="Regex de la requête (Query Regex)"
                description="Expression régulière pour valider ou extraire l'identifiant recherché (ex: ^[A-Z0-9]+$ ou ^([^@]+) pour extraire avant @)"
              >
                <UInput
                  v-model="form.queryRegex"
                  placeholder="^[A-Z0-9]+$"
                  class="w-full font-mono text-xs"
                  icon="i-heroicons-code-bracket"
                />
              </UFormField>
            </div>

            <UFormField
              label="Filtre Regex Global"
              description="Vérifié sur le nom d'utilisateur, l'email, le nom complet ou le DN (ex: ^PINF.* ou .*@(bm-energies\.com|groupegdb\.local)$)"
            >
              <UInput
                v-model="form.searchFilterRegex"
                placeholder=".*@(bm-energies\.com|groupegdb\.local)$"
                class="w-full font-mono text-xs"
                icon="i-heroicons-funnel"
              />
            </UFormField>

            <!-- Filtres spécifiques par attribut -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Filtres ciblés par attribut :</span>
                <UButton
                  type="button"
                  size="xs"
                  variant="soft"
                  color="primary"
                  icon="i-heroicons-plus"
                  label="Ajouter un filtre"
                  @click="addSearchFilter"
                />
              </div>

              <div
                v-if="!form.searchFilters || form.searchFilters.length === 0"
                class="text-xs text-neutral-400 italic p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/40 border border-dashed border-neutral-200 dark:border-neutral-700"
              >
                Aucun filtre par attribut spécifique défini. Seul le filtre global sera appliqué s'il est renseigné.
              </div>

              <div
                v-for="(filter, index) in form.searchFilters"
                :key="index"
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-2.5 rounded-lg bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700"
              >
                <div class="w-full sm:w-1/3">
                  <UInput
                    v-model="filter.attribute"
                    placeholder="Attribut (ex: mail, sAMAccountName, department)"
                    class="w-full text-xs font-mono"
                  />
                </div>

                <div class="flex-1">
                  <UInput
                    v-model="filter.pattern"
                    placeholder="Regex (ex: .*@bm-energies\.com$ ou ^PINF\d+)"
                    class="w-full text-xs font-mono"
                  />
                </div>

                <UButton
                  type="button"
                  size="xs"
                  color="error"
                  variant="ghost"
                  icon="i-heroicons-trash"
                  @click="removeSearchFilter(index)"
                />
              </div>
            </div>

            <!-- Testeur de Regex interactif -->
            <div class="p-3.5 rounded-lg bg-neutral-100/60 dark:bg-neutral-800/60 border border-neutral-200 dark:border-neutral-700 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300 flex items-center gap-1.5">
                  <UIcon name="i-heroicons-variable" class="w-4 h-4 text-neutral-500" />
                  Testeur interactif d'expressions régulières
                </span>
                <UBadge
                  v-if="testInput.trim()"
                  :color="testMatches ? 'success' : 'error'"
                  variant="subtle"
                  size="xs"
                >
                  {{ testMatches ? 'Correspond aux filtres' : 'Ne correspond pas' }}
                </UBadge>
              </div>
              <div class="flex gap-2">
                <UInput
                  v-model="testInput"
                  placeholder="Testez un identifiant ou email (ex: FBouloussa-ext@bm-energies.com ou PINF14)..."
                  class="w-full text-xs"
                />
              </div>
              <p v-if="testInput.trim()" class="text-[11px] text-neutral-500">
                Résultat : {{ testMatches ? 'Cette entrée est acceptée par les filtres regex configurés.' : 'Cette entrée serait ignorée lors de la synchronisation / recherche.' }}
              </p>
            </div>
          </div>
        </UCard>

        <!-- Carte 4 : Actions & Synchronisation -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                  <UIcon name="i-heroicons-bolt" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Actions & Opérations</h3>
                  <p class="text-xs text-neutral-500">Sauvegardez la configuration, vérifiez la liaison ou synchronisez les comptes</p>
                </div>
              </div>
            </div>
          </template>

          <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <UButton
                  type="submit"
                  color="primary"
                  icon="i-heroicons-check"
                  label="Enregistrer les paramètres"
                  :loading="isSaving"
                />

                <UButton
                  type="button"
                  variant="outline"
                  color="neutral"
                  icon="i-heroicons-signal"
                  label="Tester la connexion"
                  :loading="isTesting"
                  @click="testConnection"
                />
              </div>

              <UButton
                type="button"
                color="warning"
                icon="i-heroicons-arrow-path"
                label="Lancer la synchronisation immédiate"
                :loading="isSyncing"
                @click="syncUsers"
              />
            </div>

            <div class="p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-700/60 text-xs text-neutral-500">
              <span class="font-medium text-neutral-700 dark:text-neutral-300">Synchronisation automatique (JIT) :</span>
              Lorsque l'intégration LDAP est active, les utilisateurs sont également créés et mis à jour automatiquement lors de leur authentification.
            </div>
          </div>
        </UCard>
      </form>

      <!-- Carte 5 : Dernier rapport de synchronisation -->
      <UCard v-if="lastSyncResult">
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <UIcon name="i-heroicons-chart-bar" class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Dernier rapport de synchronisation</h3>
                <p class="text-xs text-neutral-500">Bilan des utilisateurs traités lors de la dernière synchronisation</p>
              </div>
            </div>
            <UBadge color="success" variant="subtle" size="sm">
              Terminé
            </UBadge>
          </div>
        </template>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
          <div class="p-3.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ lastSyncResult.created }}</div>
            <div class="text-xs text-neutral-500 mt-0.5">Utilisateurs créés</div>
          </div>
          <div class="p-3.5 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ lastSyncResult.updated }}</div>
            <div class="text-xs text-neutral-500 mt-0.5">Utilisateurs mis à jour</div>
          </div>
          <div class="p-3.5 rounded-lg bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
            <div class="text-2xl font-bold text-neutral-600 dark:text-neutral-400">{{ lastSyncResult.skipped }}</div>
            <div class="text-xs text-neutral-500 mt-0.5">Ignorés / Filtrés</div>
          </div>
          <div class="p-3.5 rounded-lg bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800">
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ lastSyncResult.total }}</div>
            <div class="text-xs text-neutral-500 mt-0.5">Total synchronisé</div>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { getEntrypoint, resolveApiUrl } from "~/utils/config";
import { useAuthStore } from "~/stores/auth";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Paramètres LDAP - Project Manager",
});

const authStore = useAuthStore();

interface LdapSearchFilterItem {
  attribute: string;
  pattern: string;
}

interface LdapConfigState {
  id?: number;
  "@id"?: string;
  enabled: boolean;
  host: string;
  port: number;
  baseDn: string;
  bindDn?: string;
  bindPassword?: string;
  imageAttribute?: string;
  attributeMapping?: {
    image?: string;
    email?: string;
    username?: string;
    [key: string]: string | undefined;
  };
  searchFilter?: string;
  queryRegex?: string;
  searchFilterRegex?: string;
  searchFilters?: LdapSearchFilterItem[];
}

const form = ref<LdapConfigState>({
  enabled: false,
  host: "",
  port: 389,
  baseDn: "",
  bindDn: "",
  bindPassword: "",
  imageAttribute: "jpegPhoto",
  attributeMapping: {
    image: "jpegPhoto",
    email: "mail",
    username: "sAMAccountName",
  },
  searchFilter: "",
  queryRegex: "",
  searchFilterRegex: "",
  searchFilters: [],
});

const isSaving = ref(false);
const isTesting = ref(false);
const isSyncing = ref(false);

const testInput = ref("");

const testMatches = computed(() => {
  if (!testInput.value.trim()) return false;
  const val = testInput.value.trim();

  // Test query regex
  if (form.value.queryRegex && form.value.queryRegex.trim()) {
    try {
      const pattern = form.value.queryRegex.trim();
      let regex: RegExp;
      const delimMatch = pattern.match(/^\/([\s\S]*)\/([gimsuy]*)$/);
      if (delimMatch) {
        regex = new RegExp(delimMatch[1], delimMatch[2]);
      } else {
        regex = new RegExp(pattern, "i");
      }
      if (!regex.test(val)) {
        return false;
      }
    } catch {
      // Regex invalide
    }
  }

  // Test global regex
  if (form.value.searchFilterRegex && form.value.searchFilterRegex.trim()) {
    try {
      const pattern = form.value.searchFilterRegex.trim();
      let regex: RegExp;
      const delimMatch = pattern.match(/^\/([\s\S]*)\/([gimsuy]*)$/);
      if (delimMatch) {
        regex = new RegExp(delimMatch[1], delimMatch[2]);
      } else {
        regex = new RegExp(pattern, "i");
      }
      if (!regex.test(val)) {
        return false;
      }
    } catch {
      // Regex invalide
    }
  }

  // Test attribute filters
  if (form.value.searchFilters && form.value.searchFilters.length > 0) {
    for (const filter of form.value.searchFilters) {
      if (!filter.pattern || !filter.pattern.trim()) continue;
      try {
        const pattern = filter.pattern.trim();
        let regex: RegExp;
        const delimMatch = pattern.match(/^\/([\s\S]*)\/([gimsuy]*)$/);
        if (delimMatch) {
          regex = new RegExp(delimMatch[1], delimMatch[2]);
        } else {
          regex = new RegExp(pattern, "i");
        }
        if (!regex.test(val)) {
          return false;
        }
      } catch {
        // Regex invalide
      }
    }
  }

  return true;
});

function addSearchFilter() {
  if (!form.value.searchFilters) {
    form.value.searchFilters = [];
  }
  form.value.searchFilters.push({
    attribute: "mail",
    pattern: "",
  });
}

function removeSearchFilter(index: number) {
  if (form.value.searchFilters) {
    form.value.searchFilters.splice(index, 1);
  }
}

const statusMessage = ref<string | null>(null);
const statusDetail = ref<string | undefined>(undefined);
const statusType = ref<"success" | "error">("success");

const lastSyncResult = ref<{
  created: number;
  updated: number;
  skipped: number;
  total: number;
} | null>(null);

async function loadConfiguration() {
  try {
    const response = await $fetch<any>(`${getEntrypoint()}/ldap_configurations`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        Accept: "application/ld+json",
      },
    });

    const items = response?.["hydra:member"] || response?.member || [];
    if (items.length > 0) {
      const config = items[0];
      form.value = {
        id: config.id,
        "@id": config["@id"],
        enabled: config.enabled ?? false,
        host: config.host || "",
        port: config.port || 389,
        baseDn: config.baseDn || "",
        bindDn: config.bindDn || "",
        bindPassword: "",
        imageAttribute: config.imageAttribute || config.attributeMapping?.image || "jpegPhoto",
        attributeMapping: {
          image: config.imageAttribute || config.attributeMapping?.image || "jpegPhoto",
          email: config.attributeMapping?.email || "mail",
          username: config.attributeMapping?.username || "sAMAccountName",
        },
        searchFilter: config.searchFilter || "",
        queryRegex: config.queryRegex || "",
        searchFilterRegex: config.searchFilterRegex || "",
        searchFilters: Array.isArray(config.searchFilters) ? config.searchFilters : [],
      };
    }
  } catch (err: any) {
    console.error("Erreur chargement config LDAP", err);
  }
}

onMounted(() => {
  loadConfiguration();
});

async function saveConfiguration() {
  isSaving.value = true;
  statusMessage.value = null;

  try {
    const payload: any = {
      enabled: form.value.enabled,
      host: form.value.host,
      port: Number(form.value.port),
      baseDn: form.value.baseDn,
      bindDn: form.value.bindDn || null,
      imageAttribute: form.value.imageAttribute || "jpegPhoto",
      attributeMapping: {
        image: form.value.imageAttribute || "jpegPhoto",
        email: form.value.attributeMapping?.email || "mail",
        username: form.value.attributeMapping?.username || "sAMAccountName",
      },
      searchFilter: form.value.searchFilter?.trim() || null,
      queryRegex: form.value.queryRegex?.trim() || null,
      searchFilterRegex: form.value.searchFilterRegex?.trim() || null,
      searchFilters: (form.value.searchFilters || []).filter(
        (f) => f.attribute?.trim() && f.pattern?.trim()
      ),
    };
    if (form.value.bindPassword) {
      payload.bindPassword = form.value.bindPassword;
    }

    if (form.value["@id"]) {
      await $fetch(resolveApiUrl(form.value["@id"]), {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          "Content-Type": "application/ld+json",
          Accept: "application/ld+json",
        },
        body: payload,
      });
    }

    statusType.value = "success";
    statusMessage.value = "Configuration LDAP enregistrée avec succès !";
    statusDetail.value = undefined;
  } catch (err: any) {
    statusType.value = "error";
    statusMessage.value = "Erreur lors de l'enregistrement de la configuration.";
    statusDetail.value = err?.data?.["hydra:description"] || err?.message;
  } finally {
    isSaving.value = false;
  }
}

async function testConnection() {
  isTesting.value = true;
  statusMessage.value = null;

  try {
    const response = await $fetch<{ message: string }>(`${getEntrypoint()}/ldap_configurations/test`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        "Content-Type": "application/json",
      },
      body: {
        host: form.value.host,
        port: Number(form.value.port),
        bindDn: form.value.bindDn || null,
        bindPassword: form.value.bindPassword || null,
      },
    });

    statusType.value = "success";
    statusMessage.value = response?.message || "Connexion LDAP réussie !";
    statusDetail.value = undefined;
  } catch (err: any) {
    statusType.value = "error";
    statusMessage.value = "Échec du test de connexion LDAP.";
    statusDetail.value = err?.data?.message || err?.message;
  } finally {
    isTesting.value = false;
  }
}

async function syncUsers() {
  isSyncing.value = true;
  statusMessage.value = null;

  try {
    const response = await $fetch<{
      success: boolean;
      created: number;
      updated: number;
      skipped: number;
      total: number;
      message?: string;
    }>(`${getEntrypoint()}/ldap/sync`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        "Content-Type": "application/json",
      },
    });

    if (response.success) {
      lastSyncResult.value = {
        created: response.created,
        updated: response.updated,
        skipped: response.skipped,
        total: response.total,
      };
      statusType.value = "success";
      statusMessage.value = `Synchronisation réussie : ${response.total} utilisateur(s) traité(s) (${response.created} créés, ${response.updated} mis à jour, ${response.skipped} ignorés).`;
      statusDetail.value = undefined;
    } else {
      statusType.value = "error";
      statusMessage.value = "Erreur lors de la synchronisation LDAP.";
      statusDetail.value = response.message;
    }
  } catch (err: any) {
    statusType.value = "error";
    statusMessage.value = "Erreur lors de la synchronisation LDAP.";
    statusDetail.value = err?.data?.message || err?.message;
  } finally {
    isSyncing.value = false;
  }
}
</script>
