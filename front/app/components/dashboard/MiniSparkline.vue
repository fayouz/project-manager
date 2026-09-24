<template>
  <svg
    :viewBox="`0 0 ${width} ${height}`"
    :width="width"
    :height="height"
    class="overflow-visible"
    role="img"
    :aria-label="ariaLabel"
  >
    <polyline
      v-if="points"
      fill="none"
      :stroke="resolvedColor"
      stroke-width="1.75"
      stroke-linecap="round"
      stroke-linejoin="round"
      :points="points"
    />
    <circle
      v-if="lastPoint"
      :cx="lastPoint.x"
      :cy="lastPoint.y"
      r="2"
      :fill="resolvedColor"
    />
  </svg>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    values?: number[];
    color?: string;
    width?: number;
    height?: number;
    ariaLabel?: string;
  }>(),
  {
    values: () => [],
    color: undefined,
    width: 80,
    height: 28,
    ariaLabel: "Tendance",
  }
);

const resolvedColor = computed(() => props.color || "currentColor");

const normalized = computed(() => {
  const raw = (props.values || []).map((v) => Number(v) || 0);
  if (raw.length === 0) return [] as number[];
  if (raw.length === 1) return [raw[0], raw[0]];
  return raw;
});

const points = computed(() => {
  const vals = normalized.value;
  if (vals.length < 2) return "";

  const min = Math.min(...vals);
  const max = Math.max(...vals);
  const range = max - min || 1;
  const padY = 2;
  const usableH = props.height - padY * 2;
  const stepX = props.width / (vals.length - 1);

  return vals
    .map((v, i) => {
      const x = i * stepX;
      const y = props.height - padY - ((v - min) / range) * usableH;
      return `${x.toFixed(2)},${y.toFixed(2)}`;
    })
    .join(" ");
});

const lastPoint = computed(() => {
  const vals = normalized.value;
  if (vals.length < 2) return null;

  const min = Math.min(...vals);
  const max = Math.max(...vals);
  const range = max - min || 1;
  const padY = 2;
  const usableH = props.height - padY * 2;
  const stepX = props.width / (vals.length - 1);
  const i = vals.length - 1;

  return {
    x: i * stepX,
    y: props.height - padY - ((vals[i] - min) / range) * usableH,
  };
});
</script>
