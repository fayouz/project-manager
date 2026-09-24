<template>
  <div>
    <UButton
      v-if="showTrigger"
      :size="triggerSize"
      :variant="triggerVariant"
      :color="triggerColor"
      :block="triggerBlock"
      :icon="collapsed ? 'i-heroicons-magnifying-glass' : undefined"
      :aria-label="collapsed ? 'Recherche globale' : undefined"
      :title="collapsed ? 'Recherche (Ctrl+K)' : undefined"
      class="justify-start"
      @click="open = true"
    >
      <template v-if="!collapsed">
        <span class="flex items-center gap-2 w-full min-w-0">
          <UIcon name="i-heroicons-magnifying-glass" class="w-4 h-4 shrink-0" />
          <span class="flex-1 text-left truncate text-neutral-500 dark:text-neutral-400">
            Rechercher…
          </span>
          <span class="hidden sm:inline-flex items-center gap-0.5 shrink-0">
            <UKbd value="meta" size="sm" />
            <UKbd value="K" size="sm" />
          </span>
        </span>
      </template>
    </UButton>

    <UModal v-model:open="open" :ui="{ content: 'sm:max-w-xl' }">
      <template #content>
        <UCommandPalette
          v-model:search-term="searchTerm"
          :groups="groups"
          :loading="isLoading"
          placeholder="Rechercher projets, organisations, serveurs…"
          close
          @update:open="onPaletteOpenChange"
          @update:model-value="onSelect"
        />
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useFetchList } from "~/composables/api";
import { getIdFromIri } from "~/utils/resource";
import type { Project } from "~/types/project";
import type { Organisation } from "~/types/organisation";
import type { Server } from "~/types/server";
import type { Integration } from "~/types/integration";

const props = withDefaults(
  defineProps<{
    showTrigger?: boolean;
    collapsed?: boolean;
    triggerSize?: "xs" | "sm" | "md" | "lg" | "xl";
    triggerVariant?: "solid" | "outline" | "soft" | "subtle" | "ghost" | "link";
    triggerColor?: "primary" | "neutral" | "secondary" | "success" | "info" | "warning" | "error";
    triggerBlock?: boolean;
  }>(),
  {
    showTrigger: true,
    collapsed: false,
    triggerSize: "sm",
    triggerVariant: "soft",
    triggerColor: "neutral",
    triggerBlock: true,
  }
);

const open = ref(false);
const searchTerm = ref("");
const isLoading = ref(false);
const hasLoaded = ref(false);

const projects = ref<Project[]>([]);
const organisations = ref<Organisation[]>([]);
const servers = ref<Server[]>([]);
const integrations = ref<Integration[]>([]);

defineShortcuts({
  meta_k: {
    usingInput: true,
    handler: () => {
      open.value = !open.value;
    },
  },
});

watch(open, async (isOpen) => {
  if (isOpen) {
    searchTerm.value = "";
    if (!hasLoaded.value) {
      await loadResources();
    }
  }
});

async function loadResources() {
  isLoading.value = true;
  try {
    const results = await Promise.allSettled([
      useFetchList<Project>("projects"),
      useFetchList<Organisation>("organisations"),
      useFetchList<Server>("servers"),
      useFetchList<Integration>("integrations"),
    ]);

    const [projectsRes, organisationsRes, serversRes, integrationsRes] = results;

    if (projectsRes.status === "fulfilled") {
      projects.value = projectsRes.value.items.value || [];
    }
    if (organisationsRes.status === "fulfilled") {
      organisations.value = organisationsRes.value.items.value || [];
    }
    if (serversRes.status === "fulfilled") {
      servers.value = serversRes.value.items.value || [];
    }
    if (integrationsRes.status === "fulfilled") {
      integrations.value = integrationsRes.value.items.value || [];
    }

    hasLoaded.value = true;
  } finally {
    isLoading.value = false;
  }
}

function resourceId(item: { "@id"?: string; id?: string | number }) {
  return getIdFromIri(item["@id"]) || item.id;
}

const groups = computed(() => {
  const projectItems = projects.value
    .map((p) => {
      const id = resourceId(p);
      if (!id) return null;
      return {
        id: `project-${id}`,
        label: p.name || `Projet #${id}`,
        suffix: String(id),
        icon: "i-heroicons-folder",
        to: `/projects/${id}`,
      };
    })
    .filter(Boolean);

  const organisationItems = organisations.value
    .map((o) => {
      const id = resourceId(o);
      if (!id) return null;
      return {
        id: `organisation-${id}`,
        label: o.name || `Organisation #${id}`,
        suffix: String(id),
        icon: "i-heroicons-building-office-2",
        to: `/organisations/${id}`,
      };
    })
    .filter(Boolean);

  const serverItems = servers.value
    .map((s) => {
      const id = resourceId(s);
      if (!id) return null;
      return {
        id: `server-${id}`,
        label: s.name || s.host || `Serveur #${id}`,
        description: s.host,
        suffix: String(id),
        icon: "i-heroicons-server",
        to: `/servers/${id}`,
      };
    })
    .filter(Boolean);

  const integrationItems = integrations.value
    .map((i) => {
      const id = resourceId(i);
      if (!id) return null;
      return {
        id: `integration-${id}`,
        label: i.name || i.type || `Intégration #${id}`,
        description: i.type,
        suffix: String(id),
        icon: "i-heroicons-puzzle-piece",
        to: `/integrations/${id}`,
      };
    })
    .filter(Boolean);

  return [
    {
      id: "actions",
      label: "Actions rapides",
      items: [
        {
          id: "action-new-project",
          label: "Nouveau projet",
          icon: "i-heroicons-plus-circle",
          to: "/projects",
          kbds: ["N"],
        },
        {
          id: "action-ldap",
          label: "Paramètres LDAP",
          icon: "i-heroicons-adjustments-horizontal",
          to: "/settings/ldap",
        },
        {
          id: "action-docs",
          label: "Documentation API",
          icon: "i-heroicons-arrow-top-right-on-square",
          to: "/docs",
          target: "_blank",
        },
      ],
    },
    {
      id: "projects",
      label: "Projets",
      items: projectItems,
    },
    {
      id: "organisations",
      label: "Organisations",
      items: organisationItems,
    },
    {
      id: "servers",
      label: "Serveurs",
      items: serverItems,
    },
    {
      id: "integrations",
      label: "Intégrations",
      items: integrationItems,
    },
  ];
});

function onPaletteOpenChange(value: boolean) {
  if (!value) {
    open.value = false;
  }
}

function onSelect(_item: any) {
  // La navigation est gérée par la prop `to` des items CommandPalette (ULink).
  open.value = false;
  searchTerm.value = "";
}

defineExpose({
  openSearch: () => {
    open.value = true;
  },
});
</script>
