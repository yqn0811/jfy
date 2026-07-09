export interface SpaceData {
  id: string
  teamId: string
  name: string
  storageLimitGb: number
  storageUsedGb: number
  memberCount: number
  archiveRule: string
  updatedAt: string
}

export interface SpaceFolderData {
  id: string
  spaceId: string
  parentId?: string | null
  name: string
  path: string
  level: number
}

export interface ArchiveItemData {
  id: string
  spaceId: string
  folderId: string
  sourceTaskId: string
  name: string
  fileCount: number
  storageSizeMb: number
  archivedAt: string
  permissionId: string
  status: 'ready' | 'locked' | 'moved'
}

export interface ArchivePermissionData {
  id: string
  spaceId: string
  name: string
  accessLevel: 'private' | 'team' | 'department'
  allowDownload: boolean
  allowMove: boolean
}

export interface TeamMemberData {
  id: string
  teamId: string
  name: string
  role: 'owner' | 'admin' | 'member' | 'viewer'
  department: string
  avatarUrl: string
  status: 'active' | 'invited' | 'disabled'
  joinedAt: string
}
