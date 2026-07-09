export interface TeamData {
  id: string
  name: string
  code: string
  status: 'active' | 'paused' | 'archived'
  plan: 'starter' | 'pro' | 'business'
  memberCount: number
  storageLimitGb: number
  storageUsedGb: number
  createdAt: string
  updatedAt: string
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
