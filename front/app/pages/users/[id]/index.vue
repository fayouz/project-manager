<template>
  <div class="flex flex-col h-full overflow-hidden">
    <UDashboardNavbar title="Détails de l'utilisateur">
      <template #leading>
        <UDashboardSidebarCollapse class="lg:hidden" />
      </template>

      <template #left>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-heroicons-arrow-left"
          size="sm"
          label="Retour aux utilisateurs"
          to="/users"
        />
      </template>
    </UDashboardNavbar>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
      <div class="max-w-4xl mx-auto space-y-6">
        <UserShow
          :key="refreshKey"
          @edit="onEdit"
          @back="navigateTo('/users')"
        />
      </div>
    </div>

    <!-- Modale de Modification -->
    <UModal v-model:open="isEditModalOpen" title="Modifier l'utilisateur">
      <template #body>
        <UserUpdate
          v-if="selectedUser && selectedUserId"
          :id="selectedUserId"
          :item="selectedUser"
          :show-back="false"
          @updated="onUpdated"
          @deleted="onDeleted"
          @cancel="isEditModalOpen = false"
        />
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import UserShow from "~/components/user/UserShow.vue";
import UserUpdate from "~/components/user/UserUpdate.vue";
import { getIdFromIri } from "~/utils/resource";
import type { User } from "~/types/user";

definePageMeta({
  layout: "dashboard",
});

useHead({
  title: "Détails Utilisateur - Project Manager",
});

const route = useRoute();
const refreshKey = ref(0);
const isEditModalOpen = ref(false);
const selectedUser = ref<User | null>(null);

const selectedUserId = computed(() => {
  if (selectedUser.value?.["@id"]) {
    return getIdFromIri(selectedUser.value["@id"]);
  }
  return (route.params.id as string) || "";
});

function onEdit(item?: User) {
  if (item) {
    selectedUser.value = item;
    isEditModalOpen.value = true;
  }
}

function onUpdated() {
  isEditModalOpen.value = false;
  refreshKey.value++;
}

function onDeleted() {
  isEditModalOpen.value = false;
  navigateTo("/users");
}
</script>
