import type { BuildingCode, ClassroomOption } from '@/types';

export function inferBuildingCode(code: string): BuildingCode | null {
    const upper = String(code).toUpperCase();
    if (upper.startsWith('AIA') || upper.startsWith('IA')) return 'AIA';
    if (upper.startsWith('BCB') || upper.startsWith('CB')) return 'BCB';
    if (upper.startsWith('BGC') || upper.startsWith('GC')) return 'BGC';
    if (upper.startsWith('BRA') || upper.startsWith('RA')) return 'BRA';
    return null;
}

export function getRoomBuildingCode(room: ClassroomOption): BuildingCode | null {
    const rawCode = String(room.building_code ?? '').toUpperCase();
    if (rawCode === 'BCB' || rawCode === 'BGC' || rawCode === 'BRA' || rawCode === 'AIA') {
        return rawCode;
    }
    if (rawCode === 'CB') {
        return 'BCB';
    }
    if (rawCode === 'GC') {
        return 'BGC';
    }
    if (rawCode === 'RA') {
        return 'BRA';
    }

    return inferBuildingCode(room.code);
}

export function weekdayLabel(day: number): string {
    const labels = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
    return labels[day - 1] ?? `週${day}`;
}
