<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          Équipes du projet
        </h3>
        <p class="text-xs text-neutral-500">
          Organisez les membres du projet en équipes fonctionnelles ou techniques.
        </p>
      </div>
      <UButton
        color="primary"
        size="sm"
        icon="i-heroicons-user-group"
        label="Créer une équipe"
        @click="openCreateTeamModal"
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
      v-else-if="teams.length === 0"
      class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl text-neutral-500 text-sm"
    >
      <UIcon name="i-heroicons-user-group" class="size-10 mx-auto mb-2 text-neutral-400" />
      <p class="font-medium">Aucune équipe n'a été créée pour ce projet.</p>
      <p class="text-xs text-neutral-400 mt-1">Créez des équipes (ex: Backend, Frontend, QA) et assignez-y des membres.</p>
    </div>

    <!-- Liste des équipes -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <UCard
        v-for="team in teams"
        :key="team['@id']"
        class="border border-neutral-200 dark:border-neutral-800 shadow-xs hover:border-neutral-300 dark:hover:border-neutral-700 transition"
      >
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="size-8 rounded-lg bg-primary-100 dark:bg-primary-950 flex items-center justify-center text-primary-600 dark:text-primary-400">
                <UIcon name="i-heroicons-user-group" class="size-4" />
              </div>
              <div>
                <h4 class="text-sm font-bold text-neutral-900 dark:text-neutral-100">
                  {{ team.name }}
                </h4>
                <p v-if="team.description" class="text-xs text-neutral-500 line-clamp-1">
                  {{ team.description }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-1">
              <UButton
                color="primary"
                variant="ghost"
                size="xs"
                icon="i-heroicons-user-plus"
                title="Ajouter un membre à l'équipe"
                @click="openAddMemberModal(team)"
              />
              <UButton
                color="error"
                variant="ghost"
                size="xs"
                icon="i-heroicons-trash"
                title="Supprimer l'équipe"
                @click="handleDeleteTeam(team)"
              />
            </div>
          </div>
        </template>

        <!-- Membres de cette équipe -->
        <div class="space-y-2">
          <div class="flex items-center justify-between text-xs text-neutral-500 font-medium pb-1 border-b border-neutral-100 dark:border-neutral-800">
            <span>Membres</span>
            <span>{{ (teamMembersMap[team['@id'] || ''] || []).length }} membre(s)</span>
          </div>

          <div v-if="(teamMembersMap[team['@id'] || ''] || []).length === 0" class="text-center py-4 text-xs text-neutral-400 italic">
            Aucun membre dans cette équipe.
          </div>

          <div v-else class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
            <div
              v-for="tm in teamMembersMap[team['@id'] || '']"
              :key="tm['@id']"
              class="flex items-center justify-between p-2 rounded-lg bg-neutral-50 dark:bg-neutral-800/40 text-xs"
            >
              <div class="flex items-center gap-2 min-w-0">
                <div class="size-6 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                  {{ getUserInitials(tm.user) }}
                </div>
                <span class="font-medium text-neutral-800 dark:text-neutral-200 truncate">
                  {{ getUserDisplayName(tm.user) }}
                </span>
                <UBadge
                  :color="tm.user?.type === 'ldap' ? 'info' : 'neutral'"
                  variant="subtle"
                  size="xs"
                  class="text-[9px] px-1 py-0"
                >
                  {{ tm.user?.type === 'ldap' ? 'LDAP' : 'Local' }}
                </UBadge>
                <UBadge
                  :color="tm.role === 'LEAD' ? 'warning' : 'neutral'"
                  variant="subtle"
                  size="xs"
                  class="text-[9px] px-1 py-0"
                >
                  {{ tm.role }}
                </UBadge>
              </div>

              <UButton
                color="error"
                variant="ghost"
                size="xs"
                icon="i-heroicons-x-mark"
                class="size-5 p-0"
                title="Retirer de l'équipe"
                @click="handleRemoveTeamMember(tm, team)"
              />
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Modale Créer une équipe -->
    <UModal v-model:open="isTeamModalOpen" title="Créer une nouvelle équipe">
      <template #body>
        <div class="space-y-4">
          <UAlert
            v-if="modalError"
            color="error"
            variant="subtle"
            :title="modalError"
            size="sm"
          />

          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Nom de l'équipe <span class="text-error-500">*</span>
            </label>
            <UInput
              v-model="teamForm.name"
              placeholder="ex: Backend, Frontend, QA..."
              class="w-full"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Description (optionnelle)
            </label>
            <UInput
              v-model="teamForm.description"
              placeholder="Rôle ou objectifs de l'équipe..."
              class="w-full"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <UButton
              color="neutral"
              variant="outline"
              label="Annuler"
              size="sm"
              @click="isTeamModalOpen = false"
            />
            <UButton
              color="primary"
              label="Créer"
              size="sm"
              :loading="isSaving"
              @click="handleSaveTeam"
            />
          </div>
        </div>
      </template>
    </UModal>

    <!-- Modale Ajouter un membre à une équipe -->
    <UModal v-model:open="isMemberModalOpen" :title="`Ajouter un membre à l'équipe ${activeTeam?.name || ''}`">
      <template #body>
        <div class="space-y-4">
          <UAlert
            v-if="modalError"
            color="error"
            variant="subtle"
            :title="modalError"
            size="sm"
          />

          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Utilisateur <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="memberForm.userIri"
              :items="userOptions"
              placeholder="Sélectionner un utilisateur"
              class="w-full"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300">
              Rôle dans l'équipe <span class="text-error-500">*</span>
            </label>
            <USelect
              v-model="memberForm.role"
              :items="teamRoleOptions"
              class="w-full"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-neutral-200 dark:border-neutral-800">
            <UButton
              color="neutral"
              variant="outline"
              label="Annuler"
              size="sm"
              @click="isMemberModalOpen = false"
            />
            <UButton
              color="primary"
              label="Ajouter"
              size="sm"
              :loading="isSaving"
              @click="handleSaveTeamMember"
            />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useFetchList, useCreateItem, useDeleteItem } from '~/composables/api';
import type { Team } from '~/types/team';
import type { TeamMember } from '~/types/teammember';
import type { User } from '~/types/user';

const props = defineProps<{
  projectIri: string;
}>();

const teams = ref<Team[]>([]);
const teamMembersMap = ref<Record<string, TeamMember[]>>({});
const allUsers = ref<User[]>([]);

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref<string | null>(null);
const modalError = ref<string | null>(null);

const isTeamModalOpen = ref(false);
const teamForm = ref({
  name: '',
  description: '',
});

const isMemberModalOpen = ref(false);
const activeTeam = ref<Team | null>(null);
const memberForm = ref({
  userIri: '',
  role: 'MEMBER',
});

const teamRoleOptions = [
  { label: 'Membre (MEMBER)', value: 'MEMBER' },
  { label: 'Responsable (LEAD)', value: 'LEAD' },
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

async function loadTeams() {
  if (!props.projectIri) return;
  isLoading.value = true;
  error.value = null;
  try {
    const data = await useFetchList<Team>(
      `teams?project=${encodeURIComponent(props.projectIri)}`
    );
    teams.value = data.items.value || [];
    // Load members for all teams
    for (const team of teams.value) {
      if (team['@id']) {
        await loadTeamMembers(team['@id']);
      }
    }
  } catch (err: any) {
    error.value = err.message || 'Impossible de charger les équipes du projet.';
  } finally {
    isLoading.value = false;
  }
}

async function loadTeamMembers(teamIri: string) {
  try {
    const data = await useFetchList<TeamMember>(
      `team_members?team=${encodeURIComponent(teamIri)}`
    );
    teamMembersMap.value[teamIri] = data.items.value || [];
  } catch (err) {
    console.error(`Failed to load members for team ${teamIri}`, err);
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

function openCreateTeamModal() {
  teamForm.value = { name: '', description: '' };
  modalError.value = null;
  isTeamModalOpen.value = true;
}

function openAddMemberModal(team: Team) {
  activeTeam.value = team;
  memberForm.value = {
    userIri: allUsers.value[0]?.['@id'] || '',
    role: 'MEMBER',
  };
  modalError.value = null;
  isMemberModalOpen.value = true;
}

async function handleSaveTeam() {
  if (!teamForm.value.name.trim()) {
    modalError.value = 'Le nom de l\'équipe est obligatoire.';
    return;
  }
  modalError.value = null;
  isSaving.value = true;
  try {
    await useCreateItem<Team>('teams', {
      name: teamForm.value.name.trim(),
      description: teamForm.value.description.trim() || undefined,
      project: props.projectIri,
    });
    isTeamModalOpen.value = false;
    await loadTeams();
  } catch (err: any) {
    modalError.value = err.message || 'Erreur lors de la création de l\'équipe.';
  } finally {
    isSaving.value = false;
  }
}

async function handleDeleteTeam(team: Team) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'équipe "${team.name}" ?`)) return;
  try {
    await useDeleteItem(team);
    await loadTeams();
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la suppression de l\'équipe.';
  }
}

async function handleSaveTeamMember() {
  if (!memberForm.value.userIri) {
    modalError.value = 'Veuillez sélectionner un utilisateur.';
    return;
  }
  if (!activeTeam.value?.['@id']) return;
  modalError.value = null;
  isSaving.value = true;
  try {
    await useCreateItem<TeamMember>('team_members', {
      team: activeTeam.value['@id'],
      user: memberForm.value.userIri,
      role: memberForm.value.role,
    });
    isMemberModalOpen.value = false;
    await loadTeamMembers(activeTeam.value['@id']);
  } catch (err: any) {
    modalError.value = err.message || 'Erreur lors de l\'ajout du membre à l\'équipe.';
  } finally {
    isSaving.value = false;
  }
}

async function handleRemoveTeamMember(tm: TeamMember, team: Team) {
  if (!confirm('Êtes-vous sûr de vouloir retirer ce membre de l\'équipe ?')) return;
  try {
    await useDeleteItem(tm);
    if (team['@id']) {
      await loadTeamMembers(team['@id']);
    }
  } catch (err: any) {
    error.value = err.message || 'Impossible de retirer le membre de l\'équipe.';
  }
}

onMounted(() => {
  loadTeams();
  loadUsers();
});
</script>
