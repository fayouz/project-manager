<template>
  <div class="flex flex-col items-center justify-center min-h-screen p-4 bg-gray-100 dark:bg-gray-900">
    <UCard class="w-full max-w-md">
      <template #header>
        <div class="space-y-1">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white">
              <UIcon name="i-heroicons-user-plus" class="w-5 h-5" />
            </div>
            <span class="font-bold text-base text-gray-900 dark:text-white">Project Manager</span>
          </div>
          <h3 class="text-lg font-bold">Inscription</h3>
          <p class="text-sm text-gray-500">Créez votre compte utilisateur local</p>
        </div>
      </template>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-heroicons-exclamation-triangle"
          :title="errorMessage"
          class="mb-2"
        />

        <UFormField label="Adresse email" required>
          <UInput
            v-model="email"
            type="email"
            placeholder="collaborateur@exemple.com"
            class="w-full"
            icon="i-heroicons-envelope"
            autocomplete="email"
            required
          />
        </UFormField>

        <UFormField label="Nom d'utilisateur (optionnel)">
          <UInput
            v-model="username"
            placeholder="jdupont"
            class="w-full"
            icon="i-heroicons-user"
            autocomplete="username"
          />
        </UFormField>

        <UFormField label="Mot de passe" required>
          <InputPassword
            ref="passwordInput"
            v-model="password"
            :show-meter="true"
            autocomplete="new-password"
            required
          />
        </UFormField>

        <UFormField label="Confirmer le mot de passe" required>
          <UInput
            v-model="confirmPassword"
            type="password"
            placeholder="••••••••"
            class="w-full"
            icon="i-heroicons-lock-closed"
            autocomplete="new-password"
            required
          />
        </UFormField>

        <UButton
          type="submit"
          block
          label="Créer mon compte"
          :loading="isLoading"
          class="mt-4"
        />
      </form>

      <template #footer>
        <div class="text-center text-sm text-gray-500">
          Vous avez déjà un compte ?
          <NuxtLink to="/login" class="text-primary-600 hover:underline font-medium ml-1">
            Se connecter
          </NuxtLink>
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "~/stores/auth";

const authStore = useAuthStore();

const email = ref("");
const username = ref("");
const password = ref("");
const confirmPassword = ref("");
const passwordInput = ref();
const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

async function handleRegister() {
  errorMessage.value = null;

  if (!email.value || !password.value || !confirmPassword.value) {
    errorMessage.value = "Veuillez remplir tous les champs obligatoires.";
    return;
  }

  if (password.value !== confirmPassword.value) {
    errorMessage.value = "Les mots de passe ne correspondent pas.";
    return;
  }

  if (passwordInput.value && !passwordInput.value.isComplete) {
    errorMessage.value = "Le mot de passe doit respecter l'ensemble des critères de sécurité.";
    return;
  }

  isLoading.value = true;

  const success = await authStore.register({
    email: email.value,
    password: password.value,
    username: username.value || undefined,
  });

  if (success) {
    // Connexion automatique après inscription
    const loginSuccess = await authStore.login({
      email: email.value,
      password: password.value,
    });

    isLoading.value = false;

    if (loginSuccess) {
      await navigateTo("/dashboard");
    } else {
      await navigateTo("/login");
    }
  } else {
    isLoading.value = false;
    errorMessage.value = authStore.error || "Une erreur est survenue lors de l'inscription.";
  }
}
</script>
