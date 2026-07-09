export interface WorkspaceData {
  id: string
  teamId: string
  currentTeamName: string
  todayPendingCount: number
  expiringSoonCount: number
  needResubmitCount: number
  storageUsedGb: number
  storageLimitGb: number
  updatedAt: string
}
