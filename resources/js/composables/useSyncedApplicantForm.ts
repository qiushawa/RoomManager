import type { ApplicantForm } from '@/types';
import { reactive, watch } from 'vue';

export function useSyncedApplicantForm(
    form: ApplicantForm,
    onUpdate: (value: ApplicantForm) => void,
) {
    const localForm = reactive<ApplicantForm>({ ...form });

    watch(
        () => form,
        (newVal) => {
            Object.assign(localForm, newVal);
        },
        { deep: true },
    );

    watch(
        localForm,
        (newVal) => {
            onUpdate({ ...newVal });
        },
        { deep: true },
    );

    return {
        localForm,
    };
}
