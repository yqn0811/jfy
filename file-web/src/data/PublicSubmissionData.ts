import type { TaskFieldConfigData, TaskMaterialItemData } from './CollectionTaskData'

export interface PublicSubmissionTaskData {
  id: string
  taskId: string
  taskName: string
  organizationName: string
  description: string
  dueAt: string
  accessCodeRequired: boolean
  accessCode?: string
  submitterFieldIds: string[]
  materialItemIds: string[]
  status: 'active' | 'expired' | 'paused'
}

export interface PublicSubmissionTaskVO {
  id: string
  taskId: string
  taskName: string
  organizationName: string
  description: string
  dueAt: string
  accessCodeRequired: boolean
  accessCodeVerified?: boolean
  submitterFields: string[] | TaskFieldConfigData[]
  materials: string[] | TaskMaterialItemData[]
  status: 'active' | 'expired' | 'paused'
}

export interface SubmissionDraftData {
  id: string
  taskId: string
  submitterName: string
  submitterPhone: string
  submitterDepartment: string
  savedAt: string
  stepIndex: number
}

export interface SubmissionFileData {
  id: string
  submissionId: string
  fileName: string
  fileSizeMb: number
  fileType: string
  previewUrl?: string
  status: 'uploaded' | 'failed' | 'pending'
  uploadedAt: string
}

export interface MissingCheckResultData {
  id: string
  submissionId: string
  missingNames: string[]
  summary: string
  checkedAt: string
  state: 'passing' | 'missing' | 'warning'
}

export interface ReSubmissionNoticeData {
  id: string
  submissionId: string
  reason: string
  sentAt: string
  sentBy: string
}
