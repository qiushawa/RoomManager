<template>
  <div class="flex flex-col gap-4">
    <div v-for="building in classrooms" :key="building.name" class="flex flex-col gap-4">
      <h1>{{ building.name }}</h1>
      <ul>
        <li
          v-for="item in building.items"
          :key="item.id">
          <button
            @click="$emit('item-click', item)"
            class="bg-gray-500 p-2 h-10 w-52 text-white flex items-center justify-center"
            type="button"
          >
            {{ item.title }}
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue'
const { classrooms } = defineProps<{
  classrooms: Array<{
    name: string;
    items: Array<{
      id: number;
      title: string;
    }>;
  }>;
}>()
</script>
