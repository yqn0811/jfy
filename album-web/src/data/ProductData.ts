export type ProductVisibility = 'public' | 'private' | 'shared'
export type ProductImageType = 'colorChart' | 'detailChart'

export interface ProductData {
  id: string
  homeId: string
  categoryId?: string
  categoryIds?: string[]
  categoryName?: string
  categoryNames?: string[]
  ownerUserId: string
  name: string
  intro: string
  coverUrl: string
  visibility: ProductVisibility
  hideDetailImage: boolean
  allowDownload?: boolean
  isHot: boolean
  sortOrder: number
  colorChartCount: number
  detailChartCount: number
  updatedAt: string
  createdAt: string
}
