<template>
  <div class="flex flex-col items-center justify-center min-h-screen p-4 bg-gray-100 dark:bg-gray-900">
    <UCard class="w-full max-w-sm">
      <template #header>
        <div class="space-y-1">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white">
              <UIcon name="i-heroicons-command-line" class="w-5 h-5" />
            </div>
            <span class="font-bold text-base text-gray-900 dark:text-white">Project Manager</span>
          </div>
          <h3 class="text-lg font-bold">Connexion</h3>
          <p class="text-sm text-gray-500">Accédez à votre espace (compte local ou LDAP)</p>
        </div>
      </template>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <UAlert
          v-if="route.query.installed"
          color="success"
          variant="subtle"
          icon="i-heroicons-check-circle"
          title="Installation terminée avec succès ! Vous pouvez maintenant vous connecter."
          class="mb-2"
        />

        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-heroicons-exclamation-triangle"
          :title="errorMessage"
          class="mb-2"
        />

        <UFormField label="Email ou identifiant" required>
          <UInput
            v-model="identifier"
            placeholder="utilisateur@domaine.com"
            class="w-full"
            icon="i-heroicons-envelope"
            autocomplete="username"
            required
          />
        </UFormField>

        <UFormField label="Mot de passe" required>
          <InputPassword v-model="password" autocomplete="current-password" required />
        </UFormField>

        <UButton
          type="submit"
          block
          label="Se connecter"
          :loading="isLoading"
          class="mt-2"
        />
      </form>

      <template #footer>
        <div class="text-center text-sm text-gray-500">
          Pas encore de compte local ?
          <NuxtLink to="/register" class="text-primary-600 hover:underline font-medium ml-1">
            Créer un compte
          </NuxtLink>
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "~/stores/auth";

const route = useRoute();
const authStore = useAuthStore();

const identifier = ref("");
const password = ref("");
const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

async function handleLogin() {
  if (!identifier.value || !password.value) {
    errorMessage.value = "Veuillez renseigner votre identifiant et votre mot de passe.";
    return;
  }

  isLoading.value = true;
  errorMessage.value = null;

  const success = await authStore.login({
    email: identifier.value,
    password: password.value,
  });

  isLoading.value = false;

  if (success) {
    const redirectUrl = (route.query.redirect as string) || "/dashboard";
    await navigateTo(redirectUrl);
  } else {
    errorMessage.value = authStore.error || "Identifiants invalides.";
  }
}
</script>
