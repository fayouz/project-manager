<template>
  <div class="space-y-2">
    <div
      v-for="(val, index) in valuesList"
      :key="index"
      class="flex items-center gap-2"
    >
      <UInput
        v-model="valuesList[index]"
        class="flex-1"
        @input="updateValues"
      />
      <UButton
        variant="ghost"
        color="error"
        icon="i-heroicons-trash"
        size="xs"
        @click="removeValue(index)"
      />
    </div>
    <UButton
      variant="soft"
      color="neutral"
      icon="i-heroicons-plus"
      size="xs"
      label="Ajouter une valeur"
      @click="addValue"
    />
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from "vue";

const props = defineProps<{
  values?: any[];
}>();

const emit = defineEmits<{
  (e: "update", values: any[]): void;
}>();

const valuesList = ref<any[]>(props.values ? [...props.values] : []);

watch(
  () => props.values,
  (val) => {
    if (val) valuesList.value = [...val];
  }
);

function updateValues() {
  emit("update", valuesList.value);
}

function addValue() {
  valuesList.value.push("");
  updateValues();
}

function removeValue(index: number) {
  valuesList.value.splice(index, 1);
  updateValues();
}
</script>
