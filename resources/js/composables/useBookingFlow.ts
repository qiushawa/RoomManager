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
    isConsecutive: () => boolean;
    onReset?: () => void;
}

export function useBookingFlow(options: UseBookingFlowOptions) {
    const { getTargetRoom, getSelectedSlots, isConsecutive, onReset } = options;

    // 當前步驟
    const currentStep = ref<Step>(1);

    // 模態框狀態
    const showGuidelinesModal = ref(false);
    const showBookingFormModal = ref(false);

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
        router.get(API_ENDPOINTS.home, {}, { replace: true });
    };

    // 進入下一步
    const nextStep = () => {
        const slots = getSelectedSlots();

        if (slots.length === 0) return;

        // 跨日檢查
        const uniqueDates = new Set(slots.map((s) => s.date));
        if (uniqueDates.size > 1) {
            alert('不能跨日借用，請重新選擇');
            return;
        }

        // 連續性檢查
        if (!isConsecutive()) {
            alert('請選擇連續的時段，中間不能有空堂！');
            return;
        }

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

        // 驗證必填欄位
        if (
            !applicantForm.name ||
            !applicantForm.identity_code ||
            !applicantForm.email ||
            !applicantForm.phone ||
            !applicantForm.reason
        ) {
            alert('請填寫完整資料 (姓名、學號、Email、電話、事由為必填)');
            return;
        }

        const startSlot = slots[0];
        const endSlot = slots[slots.length - 1];

        const payload = {
            classroom_id: targetRoom?.id,
            classroom_code: targetRoom?.code,
            date: startSlot?.date,
            start_slot_id: startSlot.id,
            end_slot_id: endSlot.id,
            applicant: { ...applicantForm },
        };

        router.post(API_ENDPOINTS.bookings, payload, {
            preserveState: false,
            preserveScroll: false,
            onSuccess: () => {
                showBookingFormModal.value = false;
                alert('預約申請已送出，請等待審核結果');
            },
            onError: () => {
                alert('送出失敗，請稍後再試');
            },
        });
    };

    return {
        currentStep,
        showGuidelinesModal,
        showBookingFormModal,
        applicantForm,
        resetForm,
        resetFlow,
        nextStep,
        prevStep,
        onGuidelinesConfirmed,
        submitForm,
    };
}
