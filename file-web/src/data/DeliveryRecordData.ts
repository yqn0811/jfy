export interface DeliveryRecordData {
  id: string
  name: string
  type: 'sent' | 'received' | 'collected' | 'archived'
  status: 'draft' | 'collecting' | 'pending_review' | 'need_resubmission' | 'approved' | 'archived' | 'expired'
  fileCount: number
  storageSizeMb: number
  createdAt: string
  expiresAt: string
  lastActorName: string
  isArchived: boolean
  shareId?: string
  shareCode?: string
  shareUrl?: string
}

export interface DeliveryRecordVO {
  id: string
  name: string
  type: DeliveryRecordData['type']
  status: DeliveryRecordData['status']
  fileCount: number
  storageSizeMb: number
  createdAt: string
  expiresAt: string
  lastActorName: string
  shareId?: string
  shareCode?: string
  shareUrl?: string
}
