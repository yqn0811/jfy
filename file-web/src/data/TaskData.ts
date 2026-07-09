export interface TaskData {
  id: string
  teamId: string
  templateId?: string | null
  name: string
  type: 'send' | 'collection' | 'archive'
  status: 'draft' | 'collecting' | 'pending_review' | 'need_resubmission' | 'approved' | 'archived' | 'expired'
  submitProgress: number
  storageUsedGb: number
  storageLimitGb: number
  dueAt: string
  lastUpdatedAt: string
  createdAt: string
  ownerId: string
  overdueLevel: 'normal' | 'warning' | 'critical'
  isArchived: boolean
}
