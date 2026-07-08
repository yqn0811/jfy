export type BrowsingTargetType = 'home' | 'category' | 'product'

export interface BrowsingRecordData {
  id: string
  userId: string
  targetType: BrowsingTargetType
  targetId: string
  viewedAt: string
}