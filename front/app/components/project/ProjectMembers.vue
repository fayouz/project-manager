<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Membres du projet
        </h3>
        <p class="text-xs text-neutral-500">
          Collaborateurs (locaux et LDAP) affectés à ce projet et leurs rôles.
        </p>
      </div>
      <UButton
        color="primary"
        size="sm"
        icon="i-heroicons-user-plus"
        label="Ajouter un membre"
        @click="openAddModal"
      />
    </div>

    <!-- Alertes -->
    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-heroicons-exclamation-triangle"
      :title="error"
      class="text-xs"
    />

    <!-- Chargement -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-primary" />
    </div>

    <!-- Vide -->
    <div
      v-else-if="members.length === 0"
      class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl text-neutral-500 text-sm"
    >
      <UIcon name="i-heroicons-users" class="size-10 mx-auto mb-2 text-neutral-400" />
      <p class="font-medium">Aucun membre n'est encore assigné à ce projet.</p>
      <p class="text-xs text-neutral-400 mt-1">Ajoutez des membres locaux ou issus de l'annuaire LDAP.</p>
    </div>

    <!-- Liste des membres -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div
        v-for="member in members"
        :key="member['@id']"
        class="flex items-center justify-between p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs"
      >
        <div class="flex items-center gap-3">
          <div class="size-10 rounded-full bg-primary-50 dark:bg-primary-950/60 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-xs uppercase border border-primary-200 dark:border-primary-800">
            {{ getUserInitials(member.user) }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ getUserDisplayName(member.user) }}
              </span>
              <UBadge
                :color="member.user?.type === 'ldap' ? 'info' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ member.user?.type === 'ldap' ? 'LDAP' : 'Local' }}
              </UBadge>
            </div>
            <div class="flex items-center gap-2 mt-1">
              <UBadge
                :color="getRoleBadgeColor(member.role)"
                variant="solid"
                size="xs"
              >
                {{ member.role }}
              </UBadge>
              <span class="text-xs text-neutral-400 font-mono truncate max-w-[180px]">
                {{ member.user?.email || '' }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <UButton
            color="neutral"
            variant="ghost"
            size="xs"
            icon="i-heroicons-pencil-square"
            title="Modifier le rôle"
            @click="openEditModal(member)"
          />
          <UButton
            color="error"
            variant="ghost"
            size="xs"
            icon="i-heroicons-trash"
            title="Retirer du projet"
            :loading="deletingId === member['@id']"
            @click="handleRemoveMember(member)"
          />
        </div>
      </div>
    </div>

    <!-- Modale Ajout / Modification -->
    <UModal v-model:open="isModalOpen" :title="editingMember ? 'Modifier le rôle du membre' : 'Ajouter un membre au projet'">
      <template #body>
        <div class="space-y-4">
          <UAlert
            v-if="modalError"
            color="error"
            variant="subtle"
            :title="modalError"
            size="sm"
          />

          <!-- Sélection utilisateur (si création) -->
          <div v-if="!editingMember" class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Utilisateur <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="selectedUserIri"
              :items="userOptions"
              placeholder="Sélectionner un utilisateur"
              class="w-full"
            />
          </div>

          <!-- Rôle -->
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Rôle sur le projet <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="selectedRole"
              :items="roleOptions"
              class="w-full"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <UButton
              color="neutral"
              variant="outline"
              label="Annuler"
              size="sm"
              @click="isModalOpen = false"
            />
            <UButton
              color="primary"
              :label="editingMember ? 'Enregistrer' : 'Ajouter'"
              size="sm"
              :loading="isSaving"
              @click="handleSave"
            />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useFetchList, useCreateItem, useUpdateItem, useDeleteItem } from '~/composables/api';
import type { ProjectMember } from '~/types/projectmember';
import type { User } from '~/types/user';

const props = defineProps<{
  projectIri: string;
}>();

const members = ref<ProjectMember[]>([]);
const allUsers = ref<User[]>([]);
const isLoading = ref(false);
const isSaving = ref(false);
const error = ref<string | null>(null);
const modalError = ref<string | null>(null);
const isModalOpen = ref(false);
const editingMember = ref<ProjectMember | null>(null);
const deletingId = ref<string | null>(null);

const selectedUserIri = ref<string>('');
const selectedRole = ref<string>('DEVELOPER');

const roleOptions = [
  { label: 'Développeur (DEVELOPER)', value: 'DEVELOPER' },
  { label: 'Mainteneur (MAINTAINER)', value: 'MAINTAINER' },
  { label: 'Invité (GUEST)', value: 'GUEST' },
];

const userOptions = computed(() => {
  return allUsers.value.map((u) => {
    const typeLabel = u.isLdap || (u as any).type === 'ldap' ? ' [LDAP]' : ' [Local]';
    return {
      label: `${u.username || u.email || u['@id']}${typeLabel} - ${u.email}`,
      value: u['@id'] as string,
    };
  });
});

function getUserDisplayName(user: any): string {
  if (!user) return 'Utilisateur inconnu';
  return user.username || user.email || user['@id'] || 'Sans nom';
}

function getUserInitials(user: any): string {
  const name = getUserDisplayName(user);
  return name.slice(0, 2).toUpperCase();
}

function getRoleBadgeColor(role?: string): 'primary' | 'warning' | 'neutral' {
  switch (role) {
    case 'MAINTAINER':
      return 'warning';
    case 'DEVELOPER':
      return 'primary';
    default:
      return 'neutral';
  }
}

async function loadMembers() {
  if (!props.projectIri) return;
  isLoading.value = true;
  error.value = null;
  try {
    const data = await useFetchList<ProjectMember>(
      `project_members?project=${encodeURIComponent(props.projectIri)}`
    );
    members.value = data.items.value || [];
  } catch (err: any) {
    error.value = err.message || 'Impossible de charger les membres du projet.';
  } finally {
    isLoading.value = false;
  }
}

async function loadUsers() {
  try {
    const data = await useFetchList<User>('users');
    allUsers.value = data.items.value || [];
  } catch (err) {
    console.error('Failed to load users', err);
  }
}

function openAddModal() {
  editingMember.value = null;
  selectedRole.value = 'DEVELOPER';
  selectedUserIri.value = allUsers.value[0]?.['@id'] || '';
  modalError.value = null;
  isModalOpen.value = true;
}

function openEditModal(member: ProjectMember) {
  editingMember.value = member;
  selectedRole.value = member.role || 'DEVELOPER';
  selectedUserIri.value = typeof member.user === 'string' ? member.user : member.user?.['@id'] || '';
  modalError.value = null;
  isModalOpen.value = true;
}

async function handleSave() {
  modalError.value = null;
  isSaving.value = true;
  try {
    if (editingMember.value) {
      await useUpdateItem(editingMember.value, {
        role: selectedRole.value,
      });
    } else {
      if (!selectedUserIri.value) {
        throw new Error('Veuillez sélectionner un utilisateur.');
      }
      await useCreateItem<ProjectMember>('project_members', {
        project: props.projectIri,
        user: selectedUserIri.value,
        role: selectedRole.value,
      });
    }
    isModalOpen.value = false;
    await loadMembers();
  } catch (err: any) {
    modalError.value = err.message || 'Une erreur est survenue lors de l\'enregistrement.';
  } finally {
    isSaving.value = false;
  }
}

async function handleRemoveMember(member: ProjectMember) {
  if (!confirm('Êtes-vous sûr de vouloir retirer ce membre du projet ?')) return;
  deletingId.value = member['@id'] || null;
  try {
    await useDeleteItem(member);
    await loadMembers();
  } catch (err: any) {
    error.value = err.message || 'Impossible de retirer le membre.';
  } finally {
    deletingId.value = null;
  }
}

onMounted(() => {
  loadMembers();
  loadUsers();
});
</script>
