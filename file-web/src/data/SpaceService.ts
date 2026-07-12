import type {
  ArchiveItemData,
  ArchivePermissionData,
  SpaceData,
  SpaceFolderData,
  TeamMemberData
} from './SpaceData'

export interface SpaceOverview {
  name: string
  storageLimitGb: number
  storageUsedGb: number
  storageUsageRate: number
  memberCount: number
  archiveRule: string
  updatedAt: string
}

export interface ArchiveItemVO {
  id: string
  name: string
  fileCount: number
  storageSizeMb: number
  archivedAt: string
  status: ArchiveItemData['status']
  folderName: string
  sourceTaskId: string
  permissionName: string
}

export const spaceDataList: SpaceData[] = []
export const spaceFolderDataList: SpaceFolderData[] = []
export const archivePermissionDataList: ArchivePermissionData[] = []
export const archiveItemDataList: ArchiveItemData[] = []
export const teamMemberDataList: TeamMemberData[] = []

export const emptySpaceOverview: SpaceOverview = {
  name: '暂无空间数据',
  storageLimitGb: 0,
  storageUsedGb: 0,
  storageUsageRate: 0,
  memberCount: 0,
  archiveRule: '归档能力待接入真实数据后展示。',
  updatedAt: '',
}

export class SpaceService {
  static getAll(): SpaceData[] {
    return []
  }

  static getById(_id: string): SpaceData | undefined {
    return undefined
  }

  static getOverview(): SpaceOverview {
    return { ...emptySpaceOverview }
  }

  static getFolders(): SpaceFolderData[] {
    return []
  }

  static getArchives(): ArchiveItemData[] {
    return []
  }

  static queryArchives(_params: {
    keyword?: string
    filter?: Partial<Record<'folderId' | 'permission' | 'sourceTaskId' | 'status', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ArchiveItemVO[] {
    return []
  }

  static loadPersisted(): SpaceData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('spaceDataList')
    }
    return null
  }

  static savePersisted(_items: SpaceData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('spaceDataList')
    }
  }
}
