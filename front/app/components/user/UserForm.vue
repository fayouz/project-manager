<template>
  <form class="space-y-4" @submit.prevent="emitSubmit">
    <UFormField
      label="Adresse email"
      name="email"
      :error="violations?.email"
      required
    >
      <UInput
        id="user_email"
        v-model="item.email"
        class="w-full"
        type="email"
        placeholder="nom@exemple.com"
        required
      />
    </UFormField>

    <UFormField
      label="Nom d'utilisateur"
      name="username"
      :error="violations?.username"
    >
      <UInput
        id="user_username"
        v-model="item.username"
        class="w-full"
        type="text"
        placeholder="Ex: jdupont"
      />
    </UFormField>

    <UFormField
      :label="isEditing ? 'Mot de passe (laisser vide pour conserver l\'actuel)' : 'Mot de passe'"
      name="password"
      :error="violations?.password"
      :required="!isEditing"
    >
      <UInput
        id="user_password"
        v-model="item.password"
        class="w-full"
        type="password"
        :placeholder="isEditing ? '••••••••' : 'Définir un mot de passe'"
        :required="!isEditing"
      />
    </UFormField>

    <div class="space-y-3 pt-2 border-t border-neutral-200 dark:border-neutral-800">
      <div class="flex items-center justify-between">
        <label for="user_role_admin" class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
          Droits d'administrateur
        </label>
        <div class="flex items-center gap-2">
          <input
            id="user_role_admin"
            v-model="isAdmin"
            type="checkbox"
            class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
          />
          <span class="text-xs text-neutral-500">ROLE_ADMIN</span>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <label for="user_is_ldap" class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
          Compte synchronisé LDAP
        </label>
        <div class="flex items-center gap-2">
          <input
            id="user_is_ldap"
            v-model="item.isLdap"
            type="checkbox"
            class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
          />
          <span class="text-xs text-neutral-500">Authentification annuaire</span>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800">
      <slot name="actions">
        <UButton
          type="submit"
          color="primary"
          icon="i-heroicons-check"
          label="Enregistrer"
        />
      </slot>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { ref, toRef, watch, computed } from "vue";
import type { User } from "~/types/user";
import type { SubmissionErrors } from "~/types/error";

const props = defineProps<{
  values?: User;
  errors?: SubmissionErrors;
}>();

const violations = toRef(props, "errors");

const item = ref<User>({ ...props.values, password: "" });

const isEditing = computed(() => Boolean(props.values?.["@id"] || props.values?.id));

const isAdmin = computed({
  get: () => Boolean(item.value.roles?.includes("ROLE_ADMIN")),
  set: (val: boolean) => {
    const roles = new Set(item.value.roles || ["ROLE_USER"]);
    if (val) {
      roles.add("ROLE_ADMIN");
    } else {
      roles.delete("ROLE_ADMIN");
    }
    item.value.roles = Array.from(roles);
  },
});

watch(
  () => props.values,
  (newVal) => {
    if (newVal) {
      item.value = {
        ...newVal,
        password: "",
        roles: Array.isArray(newVal.roles) ? newVal.roles : ["ROLE_USER"],
      };
    }
  },
  { immediate: true, deep: true }
);

const emit = defineEmits<{
  (e: "submit", item: User): void;
}>();

function emitSubmit() {
  const roles = new Set(item.value.roles || []);
  roles.add("ROLE_USER");

  const payload: Record<string, any> = {
    email: item.value.email,
    username: item.value.username || null,
    roles: Array.from(roles),
    isLdap: Boolean(item.value.isLdap),
  };

  if (item.value["@id"]) {
    payload["@id"] = item.value["@id"];
  }

  // Include password only if provided
  if (item.value.password && item.value.password.trim() !== "") {
    payload.password = item.value.password;
  }

  emit("submit", payload as User);
}
</script>
