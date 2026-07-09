import type { WorkspaceData } from './WorkspaceData'

export interface WorkspaceOverview {
  teamName: string
  todayPendingCount: number
  expiringSoonCount: number
  needResubmitCount: number
  storageUsedGb: number
  storageLimitGb: number
  storageUsageRate: number
  lastUpdatedAt: string
}

export const workspaceDataList: WorkspaceData[] = [
  {
    id: 'workspace-001',
    teamId: 'team-001',
    currentTeamName: '织序传输助手演示团队',
    todayPendingCount: 12,
    expiringSoonCount: 3,
    needResubmitCount: 5,
    storageUsedGb: 286.4,
    storageLimitGb: 500,
    updatedAt: '2026-07-08T09:00:00Z'
  }
]

export class WorkspaceService {
  static getAll(): WorkspaceData[] {
    return workspaceDataList
  }

  static getById(id: string): WorkspaceData | undefined {
    return workspaceDataList.find((item) => item.id === id)
  }

  static getOverview(): WorkspaceOverview {
    const item = workspaceDataList[0]
    return {
      teamName: item.currentTeamName,
      todayPendingCount: item.todayPendingCount,
      expiringSoonCount: item.expiringSoonCount,
      needResubmitCount: item.needResubmitCount,
      storageUsedGb: item.storageUsedGb,
      storageLimitGb: item.storageLimitGb,
      storageUsageRate: item.storageLimitGb === 0 ? 0 : item.storageUsedGb / item.storageLimitGb,
      lastUpdatedAt: item.updatedAt
    }
  }

  static loadPersisted(): WorkspaceData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('workspaceDataList')
    return raw ? (JSON.parse(raw) as WorkspaceData[]) : null
  }

  static savePersisted(items: WorkspaceData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('workspaceDataList', JSON.stringify(items))
  }
}
