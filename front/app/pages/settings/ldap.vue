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

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6 max-w-4xl">
      <div>
        <h1 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">Annuaire d'entreprise (LDAP / Active Directory)</h1>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          Configurez la liaison avec votre annuaire LDAP pour importer vos utilisateurs et activer la synchronisation ainsi que l'authentification hybride.
        </p>
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

      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold">Paramètres de connexion</h3>
              <p class="text-xs text-neutral-500">Coordonnées du serveur LDAP / AD</p>
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

        <form @submit.prevent="saveConfiguration" class="space-y-4">
          <div class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
            <div>
              <span class="text-sm font-medium">Activer l'intégration LDAP</span>
              <p class="text-xs text-neutral-500">Autorise l'authentification et l'import des utilisateurs de l'annuaire</p>
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

          <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
            <div class="flex items-center gap-2">
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
        </form>
      </UCard>

    <UCard v-if="lastSyncResult">
      <template #header>
        <div class="flex items-center gap-2">
          <UIcon name="i-heroicons-clock" class="w-5 h-5 text-neutral-400" />
          <h3 class="text-base font-semibold">Dernier rapport de synchronisation</h3>
        </div>
      </template>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
        <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ lastSyncResult.created }}</div>
          <div class="text-xs text-neutral-500">Utilisateurs créés</div>
        </div>
        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ lastSyncResult.updated }}</div>
          <div class="text-xs text-neutral-500">Utilisateurs mis à jour</div>
        </div>
        <div class="p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
          <div class="text-2xl font-bold text-neutral-600 dark:text-neutral-400">{{ lastSyncResult.skipped }}</div>
          <div class="text-xs text-neutral-500">Ignorés / Locaux</div>
        </div>
        <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800">
          <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ lastSyncResult.total }}</div>
          <div class="text-xs text-neutral-500">Total synchronisé</div>
        </div>
      </div>
    </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { getEntrypoint, resolveApiUrl } from "~/utils/config";
import { useAuthStore } from "~/stores/auth";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Paramètres LDAP - Project Manager",
});

const authStore = useAuthStore();

interface LdapConfigState {
  id?: number;
  "@id"?: string;
  enabled: boolean;
  host: string;
  port: number;
  baseDn: string;
  bindDn?: string;
  bindPassword?: string;
}

const form = ref<LdapConfigState>({
  enabled: false,
  host: "",
  port: 389,
  baseDn: "",
  bindDn: "",
  bindPassword: "",
});

const isSaving = ref(false);
const isTesting = ref(false);
const isSyncing = ref(false);

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
