<template>
  <div class="flex flex-col gap-4">
    <div v-for="classroom in classroom" :key="classroom.name" class="flex flex-col gap-4">
      <h1>{{ classroom.name }}</h1>
      <ul>
        <li
          v-for="item in classroom.items"
          :key="item.id">
          <button
            @click="$emit('item-click', item)"
            class="bg-gray-500 p-2 h-10 w-50 text-white flex items-center justify-center"
            type="button"
          >
            {{ item.title }}
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  classroom: {
    type: Array,
    required: true
  }
})
</script>