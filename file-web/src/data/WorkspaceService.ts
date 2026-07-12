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

export const workspaceDataList: WorkspaceData[] = []

export const emptyWorkspaceOverview: WorkspaceOverview = {
  teamName: '暂无工作区数据',
  todayPendingCount: 0,
  expiringSoonCount: 0,
  needResubmitCount: 0,
  storageUsedGb: 0,
  storageLimitGb: 0,
  storageUsageRate: 0,
  lastUpdatedAt: '',
}

export class WorkspaceService {
  static getAll(): WorkspaceData[] {
    return workspaceDataList
  }

  static getById(_id: string): WorkspaceData | undefined {
    return undefined
  }

  static getOverview(): WorkspaceOverview {
    return { ...emptyWorkspaceOverview }
  }

  static loadPersisted(): WorkspaceData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('workspaceDataList')
    }
    return null
  }

  static savePersisted(_items: WorkspaceData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('workspaceDataList')
    }
  }
}
