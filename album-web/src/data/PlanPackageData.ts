export interface PlanPackageData {
  id: string
  name: string
  packageType?: 'resource_storage' | 'traffic_monthly' | 'membership' | string
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
