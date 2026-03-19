import type { HighlightInfo, OccupiedData, Period, Building } from './index';

export interface HomePageFilters {
    date: string;
    room_code?: string;
    highlight?: HighlightInfo | null;
}

export interface HomePageProps {
    buildings: Building[];
    periods: Period[];
    allOccupiedData: Record<string, OccupiedData>;
    filters: HomePageFilters;
}
