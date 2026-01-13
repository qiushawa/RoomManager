
// 教室型別
export interface Classroom {
    id: number
    name: string
    active: boolean
}


// 檢視用型別
export type ClassroomSummary = Pick<Classroom, 'id' | 'name'>
export interface BuildingView {
    name: string
    classrooms: ClassroomSummary[]
}
