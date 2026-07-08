export type ProductVisibility = 'public' | 'private' | 'shared'
export type ProductImageType = 'colorChart' | 'detailChart'

export interface ProductData {
  id: string
  homeId: string
  categoryId?: string
  ownerUserId: string
  name: string
  intro: string
  coverUrl: string
  visibility: ProductVisibility
  hideDetailImage: boolean
  isHot: boolean
  sortOrder: number
  colorChartCount: number
  detailChartCount: number
  updatedAt: string
  createdAt: string
}