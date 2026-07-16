import type {
  MissingCheckResultData,
  ReSubmissionNoticeData,
  ReviewLogData,
  SubmissionData,
  SubmissionFileData,
  SubmissionReceiptData
} from './SubmissionData'
import { apiRequest, buildApiUrl } from '@/lib/apiClient'

export interface SubmissionFileVO {
  id: string
  fileName: string
  fileSizeMb: number
  fileType: string
  previewUrl?: string
  downloadUrl?: string
  status: SubmissionFileData['status']
  uploadedAt: string
}

export interface ReviewLogVO {
  id: string
  reviewerName: string
  action: ReviewLogData['action']
  result: ReviewLogData['result']
  remark: string
  createdAt: string
}

export interface SubmissionDetailVO {
  id: string
  collectionTaskId: string
  submitterName: string
  submitterPhone: string
  submitterDepartment: string
  status: SubmissionData['status']
  reviewState: SubmissionData['reviewState']
  hasMissing: boolean
  fileCount: number
  submittedAt: string
  updatedAt: string
  files: SubmissionFileVO[]
  reviewLogs: ReviewLogVO[]
  missingCheck: MissingCheckResultData | undefined
  resubmissionNotice: ReSubmissionNoticeData | undefined
}

export interface SubmissionReceiptVO {
  id: string
  submissionId: string
  receiptNumber: string
  submittedAt: string
  materialSummary: string
}

export interface SubmissionListResult {
  items: SubmissionData[]
  total: number
  page: number
  limit: number
}

export interface SubmissionRemindResult {
  taskId: string
  remindedCount: number
  submissionIds: string[]
}

const isBlank = (value: unknown) => value === undefined || value === null || value === ''

const pick = <T = any>(source: any, keys: string[], fallback?: T): T => {
  for (const key of keys) {
    if (!isBlank(source?.[key])) return source[key] as T
  }
  return fallback as T
}

const toStringValue = (value: unknown, fallback = '') => {
  if (isBlank(value)) return fallback
  return String(value)
}

const toNumber = (value: unknown, fallback = 0) => {
  const numberValue = Number(value)
  return Number.isFinite(numberValue) ? numberValue : fallback
}

const toBoolean = (value: unknown, fallback = false) => {
  if (value === undefined || value === null || value === '') return fallback
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value !== 0
  if (typeof value === 'string') return !['0', 'false', 'no', 'off'].includes(value.toLowerCase())
  return Boolean(value)
}

const toIsoLike = (value: unknown) => {
  const raw = toStringValue(value)
  if (!raw) return ''
  const normalized = raw
    .replace(' ', 'T')
    .replace(/([+-]\d{2})$/, '$1:00')
    .replace(/([+-]\d{2})(\d{2})$/, '$1:$2')
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? raw : date.toISOString()
}

const toApiUrl = (value: string) => {
  if (!value) return ''
  if (/^https?:\/\//i.test(value)) return value
  return buildApiUrl(value.replace(/^\/?api\//, ''))
}

const normalizeSubmission = (raw: any): SubmissionData => ({
  id: toStringValue(pick(raw, ['id'])),
  collectionTaskId: toStringValue(pick(raw, ['collectionTaskId', 'collection_task_id', 'taskId', 'task_id'])),
  submitterName: toStringValue(pick(raw, ['submitterName', 'submitter_name'], '未填写')),
  submitterPhone: toStringValue(pick(raw, ['submitterPhone', 'submitter_phone'])),
  submitterDepartment: toStringValue(pick(raw, ['submitterDepartment', 'submitter_department'])),
  status: toStringValue(pick(raw, ['status'], 'pending_review')) as SubmissionData['status'],
  reviewState: toStringValue(pick(raw, ['reviewState', 'review_state'], 'waiting')) as SubmissionData['reviewState'],
  hasMissing: toBoolean(pick(raw, ['hasMissing', 'has_missing'], false), false),
  fileCount: toNumber(pick(raw, ['fileCount', 'file_count'], 0)),
  submittedAt: toIsoLike(pick(raw, ['submittedAt', 'submitted_at'], new Date().toISOString())),
  updatedAt: toIsoLike(pick(raw, ['updatedAt', 'updated_at'], new Date().toISOString())),
})

const normalizeFile = (raw: any): SubmissionFileVO => {
  const fileId = toStringValue(pick(raw, ['id']))
  const downloadUrl = toStringValue(pick(raw, ['downloadUrl', 'download_url']))
  const previewUrl = toStringValue(pick(raw, ['previewUrl', 'preview_url']))
  return {
    id: fileId,
    fileName: toStringValue(pick(raw, ['fileName', 'file_name'], '文件')),
    fileSizeMb: toNumber(pick(raw, ['fileSizeMb', 'file_size_mb'], 0)),
    fileType: toStringValue(pick(raw, ['fileType', 'file_type'])),
    previewUrl: toApiUrl(previewUrl),
    downloadUrl: toApiUrl(downloadUrl) || (fileId ? buildApiUrl('file/files/download', { file_id: fileId }) : ''),
    status: toStringValue(pick(raw, ['status'], 'uploaded')) as SubmissionFileData['status'],
    uploadedAt: toIsoLike(pick(raw, ['uploadedAt', 'uploaded_at'], new Date().toISOString())),
  }
}

const normalizeReviewLog = (raw: any): ReviewLogVO => ({
  id: toStringValue(pick(raw, ['id'])),
  reviewerName: toStringValue(pick(raw, ['reviewerName', 'reviewer_name'], '管理员')),
  action: toStringValue(pick(raw, ['action'], 'comment')) as ReviewLogData['action'],
  result: toStringValue(pick(raw, ['result'], 'pending')) as ReviewLogData['result'],
  remark: toStringValue(pick(raw, ['remark'])),
  createdAt: toIsoLike(pick(raw, ['createdAt', 'created_at'], new Date().toISOString())),
})

const normalizeMissingCheck = (raw: any): MissingCheckResultData | undefined => {
  if (!raw || typeof raw !== 'object') return undefined
  return {
    id: toStringValue(pick(raw, ['id'])),
    submissionId: toStringValue(pick(raw, ['submissionId', 'submission_id'])),
    missingNames: Array.isArray(raw.missingNames) ? raw.missingNames : Array.isArray(raw.missing_names) ? raw.missing_names : [],
    summary: toStringValue(pick(raw, ['summary'])),
    checkedAt: toIsoLike(pick(raw, ['checkedAt', 'checked_at'], new Date().toISOString())),
    state: toStringValue(pick(raw, ['state'], 'passing')) as MissingCheckResultData['state'],
  }
}

const normalizeSubmissionDetail = (raw: any): SubmissionDetailVO => {
  const base = normalizeSubmission(raw)
  const reviewLogs = Array.isArray(raw?.reviewLogs)
    ? raw.reviewLogs.map(normalizeReviewLog)
    : Array.isArray(raw?.review_logs)
      ? raw.review_logs.map(normalizeReviewLog)
      : []
  const latestRejectLog = reviewLogs.find((log: ReviewLogVO) => log.action === 'reject')

  return {
    ...base,
    files: Array.isArray(raw?.files) ? raw.files.map(normalizeFile) : [],
    reviewLogs,
    missingCheck: normalizeMissingCheck(pick(raw, ['missingCheck', 'missing_check'])),
    resubmissionNotice: latestRejectLog
      ? {
          id: `resubmission-${base.id}`,
          submissionId: base.id,
          reason: latestRejectLog.remark || '请按要求补交材料',
          sentAt: latestRejectLog.createdAt || base.updatedAt,
          sentBy: latestRejectLog.reviewerName || '管理员',
        }
      : undefined,
  }
}

export const submissionDataList: SubmissionData[] = []
export const submissionFileDataList: SubmissionFileData[] = []
export const reviewLogDataList: ReviewLogData[] = []
export const missingCheckResultDataList: MissingCheckResultData[] = []
export const reSubmissionNoticeDataList: ReSubmissionNoticeData[] = []
export const submissionReceiptDataList: SubmissionReceiptData[] = []

export class SubmissionService {
  static async listRemote(params: {
    taskId: string | number
    keyword?: string
    status?: string
    page?: number
    limit?: number
  }): Promise<SubmissionListResult> {
    const data = await apiRequest<any>('file/collection/submissions', {
      params: {
        taskId: params.taskId,
        keyword: params.keyword,
        status: params.status,
        page: params.page,
        limit: params.limit,
      },
    })
    const items = Array.isArray(data?.items) ? data.items : []
    return {
      items: items.map(normalizeSubmission),
      total: toNumber(pick(data, ['total'], items.length), items.length),
      page: toNumber(pick(data, ['page'], 1), 1),
      limit: toNumber(pick(data, ['limit'], items.length || 20), items.length || 20),
    }
  }

  static async getDetailRemote(submissionId: string | number): Promise<SubmissionDetailVO> {
    const data = await apiRequest<any>('file/collection/submissions/detail', {
      params: { id: submissionId },
    })
    return normalizeSubmissionDetail(data)
  }

  static async approveRemote(submissionId: string | number, remark = ''): Promise<SubmissionDetailVO> {
    const data = await apiRequest<any>('file/collection/submissions/approve', {
      method: 'POST',
      body: { id: submissionId, remark },
    })
    return normalizeSubmissionDetail(data)
  }

  static async rejectRemote(submissionId: string | number, remark: string): Promise<SubmissionDetailVO> {
    const data = await apiRequest<any>('file/collection/submissions/reject', {
      method: 'POST',
      body: { id: submissionId, remark },
    })
    return normalizeSubmissionDetail(data)
  }

  static async remindRemote(params: {
    taskId: string | number
    submissionIds: Array<string | number>
    remark?: string
  }): Promise<SubmissionRemindResult> {
    const data = await apiRequest<any>('file/collection/submissions/remind', {
      method: 'POST',
      body: {
        taskId: params.taskId,
        submissionIds: params.submissionIds,
        remark: params.remark || '',
      },
    })
    return {
      taskId: toStringValue(pick(data, ['taskId', 'task_id'], params.taskId)),
      remindedCount: toNumber(pick(data, ['remindedCount', 'reminded_count'], params.submissionIds.length)),
      submissionIds: Array.isArray(data?.submissionIds)
        ? data.submissionIds.map(String)
        : Array.isArray(data?.submission_ids)
          ? data.submission_ids.map(String)
          : params.submissionIds.map(String),
    }
  }

  static getAll(): SubmissionData[] {
    return []
  }

  static getById(_id: string): SubmissionData | undefined {
    return undefined
  }

  static query(_params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'collectionTaskId' | 'reviewState' | 'hasMissing' | 'submitterDepartment', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): SubmissionData[] {
    return []
  }

  static getDetailVOById(_id: string): SubmissionDetailVO | undefined {
    return undefined
  }

  static getSubmissionFileVOListBySubmissionId(_submissionId: string): SubmissionFileVO[] {
    return []
  }

  static getReceiptVOById(_id: string): SubmissionReceiptVO | undefined {
    return undefined
  }

  static loadPersisted(): SubmissionData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('submissionDataList')
    }
    return null
  }

  static savePersisted(_items: SubmissionData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('submissionDataList')
    }
  }
}
