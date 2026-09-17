export interface PlanPackageData {
  id: string
  name: string
  capacityMb: number
  price: string
  originalPrice?: string
  concurrentRights: number
  trafficGb: number
  durationLabel: string
  features?: string[]
  isRecommended: boolean
  createdAt: string
}
