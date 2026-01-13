<template>
    <div>
        <h1>Home Page</h1>
        <RoomSelector :buildings="buildings" />
    </div>
</template>

<script setup lang="ts">
import RoomSelector from '@/components/RoomSelector.vue';
import type { BuildingView } from '@/types';
const { buildings } = defineProps<{ buildings: BuildingView[] }>();
</script>
