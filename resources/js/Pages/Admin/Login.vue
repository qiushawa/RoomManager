<template>

    <Head title="管理員登入 | RoomManager" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-6">
        <div class="w-full max-w-md bg-white border border-gray-100 shadow-xl rounded-xl p-8">

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                    管理員登入
                </h1>

            </div>

            <!-- Form -->
            <form class="space-y-5" @submit.prevent="submitForm">

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        帳號
                    </label>
                    <input v-model="form.username" type="text" required
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition"
                        placeholder="admin" />
                    <div v-if="form.errors.username" class="mt-1 text-sm text-red-500">{{ form.errors.username }}</div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        密碼
                    </label>
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition"
                        placeholder="••••••••" />
                    <div v-if="form.errors.password" class="mt-1 text-sm text-red-500">{{ form.errors.password }}</div>
                </div>

                <!-- Remember
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center text-gray-600">
                        <input type="checkbox" v-model="form.remember"
                            class="mr-2 text-primary focus:ring-primary border-gray-300 rounded" />
                        記住我
                    </label>
                </div> -->

                <!-- Button -->
                <button type="submit" :disabled="form.processing"
                    class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-dark transition-colors disabled:opacity-50">
                    登入
                </button>

                <!-- Back -->
                <div class="text-center pt-4">
                    <a href="/Home" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                        返回首頁
                    </a>
                </div>

            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
    remember: false
});

const submitForm = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
