<template>
    <div>
        <h1>Home Page</h1>
        <ClassRoom :building="classrooms" />
    </div>
</template>

<script setup lang="ts">
    import ClassRooms from '@/components/ClassRooms.vue';
    import { defineProps } from 'vue';
    const { classrooms } = defineProps<{
        classrooms: Array<{
            name: string;
            items: Array<{
                id: number;
                title: string;
            }>;
        }>;
    }>();
</script>
