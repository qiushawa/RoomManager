/**
 * 教室相關工具函式
 */

import type { Building, Room } from '@/types';

/**
 * 根據教室代碼查找教室資料
 */
export const findRoomByCode = (
    buildings: Building[],
    code?: string
): Room | null => {
    if (!code) return null;

    for (const building of buildings) {
        const found = building.rooms.find((r) => r.code === code);
        if (found) return found;
    }
    return null;
};
