export type ResourceLibraryStatus = 'recent' | 'used' | 'unused'

export interface ResourceLibraryData {
  id: string
  name: string
  url: string
  thumbnailUrl: string
  sizeLabel: string
  sizeBytes: number
  uploadedAt: string
  status: ResourceLibraryStatus
  usedByProductId?: string
}