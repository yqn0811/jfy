export type FavoriteTargetType = 'home' | 'category' | 'product'

export interface FavoriteData {
  id: string
  userId: string
  targetType: FavoriteTargetType
  targetId: string
  createdAt: string
}