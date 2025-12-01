<template>
    <div>
        <h1>Home Page</h1>
        <ClassRoom :classrooms="classrooms" />
    </div>
</template>

<script setup lang="ts">
    import ClassRoom from '@/components/ClassRoom.vue';
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
