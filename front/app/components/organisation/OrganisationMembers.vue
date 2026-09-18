<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
          Membres de l'organisation
        </h4>
        <p class="text-xs text-neutral-500">
          Gérez les utilisateurs locaux et LDAP ayant accès à cette organisation.
        </p>
      </div>
      <UButton
        color="primary"
        size="xs"
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
    <div v-if="isLoading" class="flex justify-center py-6">
      <UIcon name="i-heroicons-arrow-path" class="size-5 animate-spin text-primary" />
    </div>

    <!-- Liste vide -->
    <div
      v-else-if="members.length === 0"
      class="text-center py-8 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-lg text-neutral-500 text-xs"
    >
      <UIcon name="i-heroicons-users" class="size-8 mx-auto mb-2 text-neutral-400" />
      <p>Aucun membre n'est encore associé à cette organisation.</p>
    </div>

    <!-- Table ou Liste des membres -->
    <div v-else class="divide-y divide-neutral-200 dark:divide-neutral-800 border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden bg-white dark:bg-neutral-900">
      <div
        v-for="member in members"
        :key="member['@id']"
        class="flex items-center justify-between p-3.5 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition"
      >
        <div class="flex items-center gap-3">
          <div class="size-9 rounded-full bg-primary-100 dark:bg-primary-950 flex items-center justify-center text-primary-600 dark:text-primary-400 font-semibold text-xs uppercase">
            {{ getUserInitials(member.user) }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                {{ getUserDisplayName(member.user) }}
              </span>
              <UBadge
                :color="member.user?.type === 'ldap' ? 'info' : 'neutral'"
                variant="subtle"
                size="xs"
              >
                {{ member.user?.type === 'ldap' ? 'LDAP' : 'Local' }}
              </UBadge>
              <UBadge
                :color="getRoleBadgeColor(member.role)"
                variant="subtle"
                size="xs"
              >
                {{ member.role }}
              </UBadge>
            </div>
            <p class="text-[11px] text-neutral-500 font-mono mt-0.5">
              {{ member.user?.email || 'Sans email' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-1.5">
          <UButton
            color="neutral"
            variant="ghost"
            size="xs"
            icon="i-heroicons-pencil"
            title="Modifier le rôle"
            @click="openEditModal(member)"
          />
          <UButton
            color="error"
            variant="ghost"
            size="xs"
            icon="i-heroicons-trash"
            title="Retirer le membre"
            :loading="deletingId === member['@id']"
            @click="handleRemoveMember(member)"
          />
        </div>
      </div>
    </div>

    <!-- Modale d'ajout ou de modification -->
    <UModal v-model:open="isModalOpen" :title="editingMember ? 'Modifier le rôle' : 'Ajouter un membre'">
      <template #body>
        <div class="space-y-4">
          <UAlert
            v-if="modalError"
            color="error"
            variant="subtle"
            :title="modalError"
            size="sm"
          />

          <!-- Sélection utilisateur (si ajout) -->
          <div v-if="!editingMember" class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Utilisateur (Local ou LDAP) <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="selectedUserIri"
              :items="userOptions"
              placeholder="Sélectionnez un utilisateur"
              class="w-full"
            />
          </div>

          <!-- Rôle -->
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Rôle dans l'organisation <span class="text-error-500">*</span>
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
import type { OrganisationMember } from '~/types/organisationmember';
import type { User } from '~/types/user';

const props = defineProps<{
  organisationIri: string;
}>();

const members = ref<OrganisationMember[]>([]);
const allUsers = ref<User[]>([]);
const isLoading = ref(false);
const isSaving = ref(false);
const error = ref<string | null>(null);
const modalError = ref<string | null>(null);
const isModalOpen = ref(false);
const editingMember = ref<OrganisationMember | null>(null);
const deletingId = ref<string | null>(null);

const selectedUserIri = ref<string>('');
const selectedRole = ref<string>('MEMBER');

const roleOptions = [
  { label: 'Membre (MEMBER)', value: 'MEMBER' },
  { label: 'Administrateur (ADMIN)', value: 'ADMIN' },
  { label: 'Propriétaire (OWNER)', value: 'OWNER' },
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
    case 'OWNER':
      return 'warning';
    case 'ADMIN':
      return 'primary';
    default:
      return 'neutral';
  }
}

async function loadMembers() {
  if (!props.organisationIri) return;
  isLoading.value = true;
  error.value = null;
  try {
    const data = await useFetchList<OrganisationMember>(
      `organisation_members?organisation=${encodeURIComponent(props.organisationIri)}`
    );
    members.value = data.items.value || [];
  } catch (err: any) {
    error.value = err.message || 'Impossible de charger les membres.';
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
  selectedRole.value = 'MEMBER';
  selectedUserIri.value = allUsers.value[0]?.['@id'] || '';
  modalError.value = null;
  isModalOpen.value = true;
}

function openEditModal(member: OrganisationMember) {
  editingMember.value = member;
  selectedRole.value = member.role || 'MEMBER';
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
      await useCreateItem<OrganisationMember>('organisation_members', {
        organisation: props.organisationIri,
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

async function handleRemoveMember(member: OrganisationMember) {
  if (!confirm('Êtes-vous sûr de vouloir retirer ce membre ?')) return;
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
