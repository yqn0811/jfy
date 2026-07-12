import type { TeamData } from './TeamData'

export const teamDataList: TeamData[] = []

export class TeamService {
  static getAll(): TeamData[] {
    return teamDataList
  }

  static getById(_id: string): TeamData | undefined {
    return undefined
  }

  static query(_params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'plan', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TeamData[] {
    return []
  }

  static loadPersisted(): TeamData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('teamDataList')
    }
    return null
  }

  static savePersisted(_items: TeamData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('teamDataList')
    }
  }
}
