export type OrderStatus = 'pending' | 'success' | 'failed'

export interface OrderData {
  id: string
  packageId: string
  orderNo: string
  amount: string
  status: OrderStatus
  createdAt: string
  updatedAt: string
}