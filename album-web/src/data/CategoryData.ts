export type CategoryVisibility = 'public' | 'private' | 'shared'
export type CategoryLayout = 'grid' | 'list'

export interface CategoryData {
  id: string
  homeId: string
  parentId?: string
  name: string
  intro: string
  coverUrl: string
  productCount: number
  childCount: number
  visibility: CategoryVisibility
  layout: CategoryLayout
  isTop: boolean
  children?: CategoryData[]
  updatedAt: string
  createdAt: string
}
