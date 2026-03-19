import type { BuildingCode, ClassroomOption } from '@/types';

export function inferBuildingCode(code: string): BuildingCode | null {
    const upper = String(code).toUpperCase();
    if (upper.includes('CB')) return 'CB';
    if (upper.includes('GC')) return 'GC';
    if (upper.includes('RA')) return 'RA';
    return null;
}

export function getRoomBuildingCode(room: ClassroomOption): BuildingCode | null {
    if (room.building_code === 'CB' || room.building_code === 'GC' || room.building_code === 'RA') {
        return room.building_code;
    }
    return inferBuildingCode(room.code);
}

export function weekdayLabel(day: number): string {
    const labels = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
    return labels[day - 1] ?? `週${day}`;
}
