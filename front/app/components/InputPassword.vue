<template>
  <div class="w-full">
    <UInput 
      v-model="modelValue" 
      :type="isPasswordVisible ? 'text' : 'password'" 
      class="w-full" 
      icon="i-heroicons-lock-closed"
      :ui="{ trailing: 'pr-0.5' }"
      :color="showMeter && modelValue ? color : undefined"
      :aria-invalid="showMeter && modelValue ? score < 4 : undefined"
      aria-describedby="password-strength"
    >
      <template #trailing>
        <UButton
          color="neutral"
          variant="ghost"
          size="sm"
          :icon="isPasswordVisible ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          aria-label="Afficher/Masquer le mot de passe"
          @click="isPasswordVisible = !isPasswordVisible"
        />
      </template>
    </UInput>
    
    <div v-if="showMeter && modelValue" class="mt-4 space-y-2">
      <UProgress
        :color="color"
        :model-value="score"
        :max="4"
        size="sm"
      />

      <p id="password-strength" class="text-sm font-medium">
        {{ text }}. Doit contenir :
      </p>

      <ul class="space-y-1" aria-label="Password requirements">
        <li
          v-for="(req, index) in strength"
          :key="index"
          class="flex items-center gap-1.5"
          :class="req.met ? 'text-green-500' : 'text-gray-500'"
        >
          <UIcon :name="req.met ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'" class="size-4 shrink-0" />

          <span class="text-xs font-light">
            {{ req.text }}
            <span class="sr-only">
              {{ req.met ? ' - Critère rempli' : ' - Critère non rempli' }}
            </span>
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const modelValue = defineModel({ type: String, default: '' });

const props = defineProps({
  showMeter: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:complete']);

const isPasswordVisible = ref(false);

function checkStrength(str) {
  if (!str) return [];
  const requirements = [
    { regex: /.{8,}/, text: 'Au moins 8 caractères' },
    { regex: /\d/, text: 'Au moins 1 chiffre' },
    { regex: /[a-z]/, text: 'Au moins 1 minuscule' },
    { regex: /[A-Z]/, text: 'Au moins 1 majuscule' }
  ]

  return requirements.map(req => ({ met: req.regex.test(str), text: req.text }))
}

const strength = computed(() => checkStrength(modelValue.value))
const score = computed(() => strength.value.filter(req => req.met).length)

watch(score, (newScore) => {
  emit('update:complete', newScore === 4);
}, { immediate: true });

const color = computed(() => {
  if (score.value === 0) return 'neutral'
  if (score.value <= 1) return 'error'
  if (score.value <= 3) return 'warning'
  return 'success'
})

const text = computed(() => {
  if (score.value === 0) return 'Entrez un mot de passe'
  if (score.value <= 2) return 'Mot de passe faible'
  if (score.value === 3) return 'Mot de passe moyen'
  return 'Mot de passe fort'
})

defineExpose({
  score,
  isComplete: computed(() => score.value === 4)
});
</script>
