<template>
    <Head :title="pageTitle" />

    <div class="min-h-screen bg-stone-100 px-6 py-10 text-slate-800 sm:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white">
                <section class="border-b border-stone-200 px-6 py-8 sm:px-10">
                    <div class="mb-4 text-[11px] font-semibold uppercase tracking-[0.28em] text-stone-400">
                        {{ pageEyebrow }}
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-500 sm:text-[15px]">
                        {{ pageDescription }}
                    </p>
                    <p v-if="mode === 'confirm'" class="mt-3 text-xs tracking-wide text-stone-400">
                        僅待審核中的申請可由此頁面取消。
                    </p>
                </section>

                <section class="px-6 py-8 sm:px-10">
                    <div v-if="noticeText" :class="noticeClass" class="rounded-2xl border px-4 py-4 text-sm leading-7">
                        {{ noticeText }}
                    </div>

                    <div v-if="summary" class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
                        <div v-for="item in summaryItems" :key="item.label" class="grid gap-2 py-4 sm:grid-cols-[140px_minmax(0,1fr)] sm:gap-6">
                            <div class="text-sm font-medium text-stone-400">
                                {{ item.label }}
                            </div>
                            <div class="text-sm leading-7 text-slate-800">
                                <template v-if="item.list">
                                    <ul class="list-disc pl-5">
                                        <li v-for="entry in item.list" :key="entry">{{ entry }}</li>
                                    </ul>
                                </template>
                                <template v-else>
                                    {{ item.value }}
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <button
                            v-if="canConfirmCancel"
                            type="button"
                            class="inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            :disabled="form.processing"
                            @click="submitCancellation"
                        >
                            {{ form.processing ? '處理中...' : '確認取消申請' }}
                        </button>

                        <Link
                            :href="homeUrl"
                            class="inline-flex items-center justify-center rounded-full border border-stone-300 px-6 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-stone-50"
                        >
                            返回借用首頁
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Summary = {
    borrower_name: string;
    classroom_name: string;
    date: string;
    teacher: string;
    reason: string;
    time_slots: string[];
};

const props = defineProps<{
    mode: 'confirm' | 'result';
    state: 'confirm' | 'cancelled' | 'locked' | 'missing';
    summary: Summary | null;
    cancelActionUrl: string | null;
    homeUrl: string;
}>();

const form = useForm({});

const canConfirmCancel = computed(() => {
    return props.mode === 'confirm' && props.state === 'confirm' && !!props.cancelActionUrl;
});

const pageEyebrow = computed(() => {
    return props.mode === 'confirm' ? 'Booking Cancellation' : 'Cancellation Result';
});

const pageTitle = computed(() => {
    return props.mode === 'confirm' ? '取消借用申請' : '取消借用申請結果';
});

const pageDescription = computed(() => {
    if (props.mode === 'confirm') {
        if (props.state === 'missing') return '找不到這筆借用申請，可能連結無效或資料不存在。';
        if (props.state === 'cancelled') return '這筆申請已經取消，不需要再次操作。';
        if (props.state === 'locked') return '這筆申請目前無法線上取消，請先確認目前狀態。';
        return '請確認以下借用資訊。若確定要撤回申請，按下下方按鈕後將把此筆申請標記為已取消。';
    }

    return '以下為本次操作結果。';
});

const noticeText = computed(() => {
    if (props.mode === 'confirm') {
        if (props.state === 'missing') return '此連結對應的申請資料已不存在，因此無法執行取消作業。';
        if (props.state === 'cancelled') return '這筆借用申請目前狀態為已取消，系統不會再次變更。';
        if (props.state === 'locked') return '此申請已不是待審核狀態，因此不能直接取消。若仍需處理，請聯繫管理員協助。';
        return '';
    }

    if (props.state === 'cancelled') return '借用申請已取消完成。';
    if (props.state === 'locked') return '此申請目前已無法線上取消，請聯繫管理員協助處理。';
    if (props.state === 'missing') return '找不到這筆借用申請，可能連結失效或資料不存在。';
    return '';
});

const noticeClass = computed(() => {
    if (props.state === 'cancelled') {
        return props.mode === 'result'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-stone-200 bg-stone-50 text-slate-500';
    }

    if (props.state === 'locked') {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    return 'border-stone-200 bg-stone-50 text-slate-500';
});

const summaryItems = computed(() => {
    if (!props.summary) return [];

    return [
        { label: '申請人', value: props.summary.borrower_name },
        { label: '教室', value: props.summary.classroom_name },
        { label: '借用日期', value: props.summary.date },
        { label: '指導老師', value: props.summary.teacher },
        { label: '借用事由', value: props.summary.reason },
        { label: '申請時段', list: props.summary.time_slots },
    ];
});

const submitCancellation = () => {
    if (!props.cancelActionUrl || form.processing) return;

    form.post(props.cancelActionUrl);
};
</script>