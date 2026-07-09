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

export const spaceDataList: SpaceData[] = [
  {
    id: 'space-001',
    teamId: 'team-001',
    name: '织序传输助手演示团队空间',
    storageLimitGb: 500,
    storageUsedGb: 286.4,
    memberCount: 18,
    archiveRule: '归档任务保留 3 年，支持按来源任务恢复。',
    updatedAt: '2026-07-08T09:00:00Z'
  }
]

export const spaceFolderDataList: SpaceFolderData[] = [
  {
    id: 'folder-001',
    spaceId: 'space-001',
    parentId: null,
    name: '全部归档',
    path: '/全部归档',
    level: 0
  },
  {
    id: 'folder-002',
    spaceId: 'space-001',
    parentId: 'folder-001',
    name: 'HR',
    path: '/全部归档/HR',
    level: 1
  },
  {
    id: 'folder-003',
    spaceId: 'space-001',
    parentId: 'folder-001',
    name: '财务',
    path: '/全部归档/财务',
    level: 1
  },
  {
    id: 'folder-004',
    spaceId: 'space-001',
    parentId: 'folder-001',
    name: '摄影',
    path: '/全部归档/摄影',
    level: 1
  },
  {
    id: 'folder-005',
    spaceId: 'space-001',
    parentId: 'folder-003',
    name: '2026 上半年',
    path: '/全部归档/财务/2026 上半年',
    level: 2
  }
]

export const archivePermissionDataList: ArchivePermissionData[] = [
  {
    id: 'perm-001',
    spaceId: 'space-001',
    name: '团队可见',
    accessLevel: 'team',
    allowDownload: true,
    allowMove: true
  },
  {
    id: 'perm-002',
    spaceId: 'space-001',
    name: '仅负责人',
    accessLevel: 'private',
    allowDownload: true,
    allowMove: false
  },
  {
    id: 'perm-003',
    spaceId: 'space-001',
    name: '部门可见',
    accessLevel: 'department',
    allowDownload: false,
    allowMove: false
  }
]

export const archiveItemDataList: ArchiveItemData[] = [
  {
    id: 'archive-001',
    spaceId: 'space-001',
    folderId: 'folder-002',
    sourceTaskId: 'task-001',
    name: 'HR 入职资料收集 - 7 月批次',
    fileCount: 15,
    storageSizeMb: 3400,
    archivedAt: '2026-07-08T09:10:00Z',
    permissionId: 'perm-001',
    status: 'ready'
  },
  {
    id: 'archive-002',
    spaceId: 'space-001',
    folderId: 'folder-004',
    sourceTaskId: 'task-002',
    name: '摄影交付链接 - 客户陈列馆',
    fileCount: 8,
    storageSizeMb: 1840,
    archivedAt: '2026-07-08T09:20:00Z',
    permissionId: 'perm-002',
    status: 'ready'
  },
  {
    id: 'archive-003',
    spaceId: 'space-001',
    folderId: 'folder-003',
    sourceTaskId: 'task-004',
    name: '6 月报销凭证归集',
    fileCount: 26,
    storageSizeMb: 5210,
    archivedAt: '2026-07-07T18:00:00Z',
    permissionId: 'perm-001',
    status: 'locked'
  },
  {
    id: 'archive-004',
    spaceId: 'space-001',
    folderId: 'folder-005',
    sourceTaskId: 'task-006',
    name: '财务审计归档任务',
    fileCount: 11,
    storageSizeMb: 1280,
    archivedAt: '2026-07-01T02:30:00Z',
    permissionId: 'perm-003',
    status: 'moved'
  }
]

export const teamMemberDataList: TeamMemberData[] = [
  {
    id: 'member-001',
    teamId: 'team-001',
    name: '陈雨晴',
    role: 'owner',
    department: '运营',
    avatarUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c667395d-3751-45cc-8a0a-c3b1330cb2b3.png',
    status: 'active',
    joinedAt: '2025-09-12T08:30:00Z'
  },
  {
    id: 'member-002',
    teamId: 'team-001',
    name: '周铭',
    role: 'admin',
    department: '产品',
    avatarUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/9859978a-75e2-4765-82ef-6b6d414b1557.png',
    status: 'active',
    joinedAt: '2025-11-06T09:00:00Z'
  },
  {
    id: 'member-003',
    teamId: 'team-001',
    name: '林知夏',
    role: 'member',
    department: '设计',
    avatarUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/2f8c7fdd-c1e1-4320-a44b-ee798deb5722.png',
    status: 'active',
    joinedAt: '2026-01-18T02:20:00Z'
  }
]

export class SpaceService {
  static getAll(): SpaceData[] {
    return spaceDataList
  }

  static getById(id: string): SpaceData | undefined {
    return spaceDataList.find((item) => item.id === id)
  }

  static getOverview(): SpaceOverview {
    const item = spaceDataList[0]
    return {
      name: item.name,
      storageLimitGb: item.storageLimitGb,
      storageUsedGb: item.storageUsedGb,
      storageUsageRate: item.storageLimitGb === 0 ? 0 : item.storageUsedGb / item.storageLimitGb,
      memberCount: item.memberCount,
      archiveRule: item.archiveRule,
      updatedAt: item.updatedAt
    }
  }

  static getFolders(): SpaceFolderData[] {
    return spaceFolderDataList
  }

  static getArchives(): ArchiveItemData[] {
    return archiveItemDataList
  }

  static queryArchives(params: {
    keyword?: string
    filter?: Partial<Record<'folderId' | 'permission' | 'sourceTaskId' | 'status', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ArchiveItemVO[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return archiveItemDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.name.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal =
            key === 'permission'
              ? item.permissionId
              : (item as any)[key]
          return Array.isArray(val) ? val.includes(String(itemVal)) : String(itemVal) === String(val)
        })
        return matchKeyword && matchFilter
      })
      .map((item) => ({
        id: item.id,
        name: item.name,
        fileCount: item.fileCount,
        storageSizeMb: item.storageSizeMb,
        archivedAt: item.archivedAt,
        status: item.status,
        folderName: spaceFolderDataList.find((folder) => folder.id === item.folderId)?.name ?? '',
        sourceTaskId: item.sourceTaskId,
        permissionName: archivePermissionDataList.find((perm) => perm.id === item.permissionId)?.name ?? ''
      }))
      .sort((a, b) => {
        if (!sortKey) return 0
        const aVal = (a as any)[sortKey]
        const bVal = (b as any)[sortKey]
        if (aVal === bVal) return 0
        const result = aVal > bVal ? 1 : -1
        return sortDirection === 'asc' ? result : -result
      })
  }

  static loadPersisted(): SpaceData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('spaceDataList')
    return raw ? (JSON.parse(raw) as SpaceData[]) : null
  }

  static savePersisted(items: SpaceData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('spaceDataList', JSON.stringify(items))
  }
}
