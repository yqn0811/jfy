import { ProductImageType } from './ProductData'

export interface ProductImageData {
  id: string
  productId: string
  type: ProductImageType
  name: string
  url: string
  thumbnailUrl: string
  sizeLabel: string
  sizeBytes: number
  sortOrder: number
  isOriginalLarge: boolean
  createdAt: string
}