import type { BuildingCode, ClassroomOption } from '@/types';

export function inferBuildingCode(code: string): BuildingCode | null {
    const upper = String(code).toUpperCase();
    if (upper.includes('AIA') || upper.includes('IA')) return 'AIA';
    if (upper.includes('BCB') || upper.includes('CB')) return 'BCB';
    if (upper.includes('BGC') || upper.includes('GC')) return 'BGC';
    if (upper.includes('BRA') || upper.includes('RA')) return 'BRA';
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
