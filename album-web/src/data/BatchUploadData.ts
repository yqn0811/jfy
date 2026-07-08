export type BatchUploadExpire = 'permanent' | '1d' | '7d' | '30d'

export interface BatchUploadData {
  id: string
  productId: string
  uploadUrl: string
  passwordEnabled: boolean
  password?: string
  expire: BatchUploadExpire
  isClosed: boolean
  createdAt: string
  updatedAt: string
}