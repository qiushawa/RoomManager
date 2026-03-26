/**
 * 預約流程相關 Composable
 * 管理步驟、模態框顯示、表單驗證等
 */

import type { ApplicantForm, Room, SelectedSlot, Step } from '@/types';
import { API_ENDPOINTS } from '@/constants';
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

/** 預設表單值 */
const DEFAULT_FORM: ApplicantForm = {
    name: '',
    identity_code: '',
    email: '',
    phone: '',
    department: '資訊工程系',
    teacher: '',
    reason: '',
};

export interface UseBookingFlowOptions {
    getTargetRoom: () => Room | null;
    getSelectedSlots: () => SelectedSlot[];
    onReset?: () => void;
    onSubmitSuccess?: () => void;
}

export function useBookingFlow(options: UseBookingFlowOptions) {
    const { getTargetRoom, getSelectedSlots, onReset, onSubmitSuccess } = options;

    // 當前步驟
    const currentStep = ref<Step>(1);

    // 模態框狀態
    const showGuidelinesModal = ref(false);
    const showBookingFormModal = ref(false);
    const showFeedbackModal = ref(false);
    const feedbackTitle = ref('');
    const feedbackMessage = ref('');
    const feedbackType = ref<'success' | 'error' | 'warning'>('success');

    const openFeedbackModal = (
        title: string,
        message: string,
        type: 'success' | 'error' | 'warning' = 'success'
    ) => {
        feedbackTitle.value = title;
        feedbackMessage.value = message;
        feedbackType.value = type;
        showFeedbackModal.value = true;
    };

    const closeFeedbackModal = () => {
        showFeedbackModal.value = false;
    };

    // 申請表單
    const applicantForm = reactive<ApplicantForm>({ ...DEFAULT_FORM });

    // 重設表單
    const resetForm = () => {
        Object.assign(applicantForm, DEFAULT_FORM);
    };

    // 重設整個流程
    const resetFlow = () => {
        currentStep.value = 1;
        resetForm();
        onReset?.();
        // url
        router.visit(API_ENDPOINTS.home, { preserveState: true, preserveScroll: true });
    };

    // 進入下一步
    const nextStep = () => {
        const slots = getSelectedSlots();

        if (slots.length === 0) return;

        // 顯示借用須知
        showGuidelinesModal.value = true;
    };

    // 須知確認後
    const onGuidelinesConfirmed = () => {
        showGuidelinesModal.value = false;
        showBookingFormModal.value = true;
    };

    // 返回上一步
    const prevStep = () => {
        currentStep.value = 1;
    };

    // 提交表單
    const submitForm = () => {
        const targetRoom = getTargetRoom();
        const slots = getSelectedSlots();

        if (!targetRoom) {
            openFeedbackModal('資料不完整', '請先選擇教室後再送出。', 'warning');
            return;
        }

        if (slots.length === 0) {
            openFeedbackModal('資料不完整', '請先選擇至少一個時段。', 'warning');
            return;
        }

        // 驗證必填欄位
        if (
            !applicantForm.name ||
            !applicantForm.identity_code ||
            !applicantForm.email ||
            !applicantForm.phone ||
            !applicantForm.reason
        ) {
            openFeedbackModal('資料不完整', '請填寫完整資料（姓名、學號、Email、電話、事由為必填）。', 'warning');
            return;
        }

        const slotsByDate = new Map<string, Set<number>>();
        slots.forEach((slot) => {
            const date = slot.date;
            const slotId = Number(slot.id);
            if (!date || !Number.isFinite(slotId) || slotId <= 0) {
                return;
            }

            if (!slotsByDate.has(date)) {
                slotsByDate.set(date, new Set<number>());
            }

            slotsByDate.get(date)?.add(slotId);
        });

        const selections = Array.from(slotsByDate.entries())
            .map(([date, idSet]) => ({
                date,
                time_slot_ids: Array.from(idSet).sort((a, b) => a - b),
            }))
            .sort((a, b) => a.date.localeCompare(b.date));

        if (selections.length === 0) {
            openFeedbackModal('資料不完整', '請先選擇有效的借用時段。', 'warning');
            return;
        }

        const firstSelection = selections[0];

        const payload = {
            classroom_id: targetRoom.id,
            classroom_code: targetRoom.code,
            // Backward compatible fields
            date: firstSelection.date,
            time_slot_ids: firstSelection.time_slot_ids,
            // New multi-date payload
            selections,
            applicant: { ...applicantForm },
        };

        router.post(API_ENDPOINTS.bookings, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showBookingFormModal.value = false;
                onSubmitSuccess?.();
            },
            onError: (errors) => {
                const firstError =
                    errors['applicant.identity_code']
                    || errors['applicant.name']
                    || errors['applicant.email']
                    || errors['applicant.phone']
                    || errors['applicant.reason']
                    || errors['date']
                    || errors['time_slot_ids']
                    || errors['selections']
                    || '送出失敗，請稍後再試';
                openFeedbackModal('送出失敗', firstError, 'error');
            },
        });
    };

    return {
        currentStep,
        showGuidelinesModal,
        showBookingFormModal,
        showFeedbackModal,
        feedbackTitle,
        feedbackMessage,
        feedbackType,
        applicantForm,
        resetForm,
        resetFlow,
        closeFeedbackModal,
        openFeedbackModal,
        nextStep,
        prevStep,
        onGuidelinesConfirmed,
        submitForm,
    };
}
