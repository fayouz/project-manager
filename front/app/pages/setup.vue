<template>
  <div class="flex items-center justify-center min-h-screen p-4 bg-gray-100 dark:bg-gray-900">
    <UCard class="w-full max-w-sm">
      <template #header>
        <div class="space-y-1">
          <h3 class="text-lg font-bold">Configuration initiale</h3>
          <p class="text-sm text-gray-500">Bienvenue dans votre gestionnaire de projet</p>
        </div>
      </template>

      <form class="space-y-4" @submit.prevent="handleInstall">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Créez votre compte administrateur pour commencer.
        </p>

        <UFormField label="Email">
          <UInput v-model="email" type="email" placeholder="admin@example.com" class="w-full" icon="i-heroicons-envelope" />
        </UFormField>

        <UFormField label="Mot de passe">
          <InputPassword v-model="password" :show-meter="true" @update:complete="isPasswordComplete = $event" />
        </UFormField>

        <UFormField label="Confirmer le mot de passe">
          <UInput v-model="confirmPassword" type="password" placeholder="••••••••" icon="i-heroicons-lock-closed" autocomplete="new-password" class="w-full" />
        </UFormField>

        <UAlert v-if="error" color="error" variant="soft" :title="error" icon="i-heroicons-exclamation-triangle" />

        <div class="pt-2">
          <UButton type="submit" block label="Finaliser l'installation" :loading="loading" :disabled="!isFormValid" />
        </div>
      </form>
    </UCard>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { z } from 'zod';
import { getEntrypoint } from '~/utils/config';

const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const isPasswordComplete = ref(false);

const error = ref('');
const loading = ref(false);

// Schéma de validation Zod
const schema = z.object({
  email: z.string().email('Email invalide'),
  password: z.string()
    .min(8, 'Minimum 8 caractères')
    .regex(/[A-Z]/, 'Doit contenir une majuscule')
    .regex(/[a-z]/, 'Doit contenir une minuscule')
    .regex(/[0-9]/, 'Doit contenir un chiffre'),
  confirmPassword: z.string().min(1, 'Veuillez confirmer votre mot de passe')
}).refine((data) => data.password === data.confirmPassword, {
  message: 'Les mots de passe ne correspondent pas',
  path: ['confirmPassword']
});

// Validation globale du formulaire
const isFormValid = computed(() => {
  const result = schema.safeParse({
    email: email.value,
    password: password.value,
    confirmPassword: confirmPassword.value
  });
  return result.success;
});

const handleInstall = async () => {
  if (!isFormValid.value) return;

  error.value = '';
  loading.value = true;

  try {
    const headers = process.client ? {} : useRequestHeaders(['x-test-env']);
    await $fetch('setup', {
      method: 'POST',
      baseURL: getEntrypoint(),
      headers,
      body: {
        email: email.value,
        password: password.value,
        confirmPassword: confirmPassword.value
      }
    });

    const isInstalled = useState('app_is_installed', () => null);
    isInstalled.value = true;

    await navigateTo({ path: '/login', query: { installed: 'true' } });
  } catch (e) {
    error.value = e.data?.error || 'Une erreur est survenue lors de l\'installation.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
</style>
