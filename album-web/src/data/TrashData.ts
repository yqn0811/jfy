export type TrashItemType = 'product' | 'category' | 'image'

export interface TrashData {
  id: string
  itemType: TrashItemType
  sourceId: string
  name: string
  coverUrl: string
  deletedAt: string
  canRestore: boolean
}