export interface StorageUsageData {
  id: string
  planName: string
  totalCapacityMb: number
  usedCapacityMb: number
  monthlyTrafficGb: number
  usedTrafficGb: number
  concurrentRights: number
  expiresAt: string
  status: 'normal' | 'warning' | 'insufficient'
  updatedAt: string
}