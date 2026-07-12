export interface SubmissionData {
  id: string
  collectionTaskId: string
  submitterName: string
  submitterPhone: string
  submitterDepartment: string
  status: 'draft' | 'submitted' | 'pending_review' | 'need_resubmission' | 'approved'
  reviewState: 'waiting' | 'approved' | 'rejected' | 'mixed'
  hasMissing: boolean
  fileCount: number
  submittedAt: string
  updatedAt: string
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

export interface ReviewLogData {
  id: string
  submissionId: string
  reviewerId: string
  reviewerName: string
  action: 'approve' | 'reject' | 'request_resubmission' | 'resubmit' | 'remind' | 'comment'
  result: 'approved' | 'rejected' | 'pending' | 'resubmission_needed' | 'submitted' | 'recorded'
  remark: string
  createdAt: string
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

export interface SubmissionReceiptData {
  id: string
  submissionId: string
  receiptNumber: string
  submittedAt: string
  materialSummary: string
}
