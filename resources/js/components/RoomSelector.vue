<template>
  <div class="flex flex-col gap-4">
    <div v-for="building in buildings" :key="building.name" class="flex flex-col gap-4">
      <h1>{{ building.name }}</h1>
      <ul>
        <li
          v-for="item in building.classrooms"
          :key="item.id">
          <button
            @click="$emit('item-click', item)"
            class="bg-gray-500 p-2 h-10 w-52 text-white flex items-center justify-center"
            type="button"
          >
            {{ item.name }}
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { BuildingView } from '@/types';
const { buildings } = defineProps<{ buildings: BuildingView[] }>();
console.log(buildings);
</script>
