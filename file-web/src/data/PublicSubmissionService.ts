import type {
  MissingCheckResultData,
  PublicSubmissionTaskData,
  PublicSubmissionTaskVO,
  ReSubmissionNoticeData,
  SubmissionDraftData,
  SubmissionFileData
} from './PublicSubmissionData'
import type { TaskFieldConfigData, TaskMaterialItemData } from './CollectionTaskData'
import { apiRequest, apiUpload } from '@/lib/apiClient'

export interface SubmissionReceiptVO {
  id: string
  submissionId: string
  taskId: string
  sourceSubmissionId?: string
  receiptToken?: string
  receiptNumber: string
  submittedAt: string
  materialSummary: string
}

export interface PublicSubmissionPayload {
  taskId: string
  accessCode?: string
  sourceSubmissionId?: string
  submitterFields: Record<string, string>
  filesByMaterialId: Record<string, File[]>
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
  const date = new Date(raw.replace(' ', 'T'))
  return Number.isNaN(date.getTime()) ? raw : date.toISOString()
}

const normalizeField = (raw: any, taskId: string): TaskFieldConfigData => ({
  id: toStringValue(pick(raw, ['id'], `field-${taskId}-${pick(raw, ['fieldKey', 'field_key'], Date.now())}`)),
  taskId,
  fieldKey: toStringValue(pick(raw, ['fieldKey', 'field_key', 'key'], 'field')),
  fieldLabel: toStringValue(pick(raw, ['fieldLabel', 'field_label', 'label'], '字段')),
  fieldType: toStringValue(pick(raw, ['fieldType', 'field_type', 'type'], 'text')) as TaskFieldConfigData['fieldType'],
  required: toBoolean(pick(raw, ['required'], true), true),
  placeholder: toStringValue(pick(raw, ['placeholder'])),
  order: toNumber(pick(raw, ['order', 'sort_order'], 0)),
})

const normalizeMaterial = (raw: any, taskId: string): TaskMaterialItemData => {
  const fileTypes = pick<any>(raw, ['fileTypes', 'file_types'], [])
  return {
    id: toStringValue(pick(raw, ['id'], `mat-${taskId}-${pick(raw, ['materialName', 'material_name'], Date.now())}`)),
    taskId,
    materialName: toStringValue(pick(raw, ['materialName', 'material_name', 'name'], '材料')),
    fileTypes: Array.isArray(fileTypes)
      ? fileTypes.map((item) => String(item))
      : String(fileTypes || '')
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean),
    required: toBoolean(pick(raw, ['required'], true), true),
    maxSizeMb: toNumber(pick(raw, ['maxSizeMb', 'max_size_mb'], 100)),
    order: toNumber(pick(raw, ['order', 'sort_order'], 0)),
  }
}

const normalizeTask = (raw: any): PublicSubmissionTaskVO => {
  const taskId = toStringValue(pick(raw, ['taskId', 'task_id', 'id']))
  return {
    id: toStringValue(pick(raw, ['id'], taskId)),
    taskId,
    taskName: toStringValue(pick(raw, ['taskName', 'task_name', 'name'], '收集任务')),
    organizationName: toStringValue(pick(raw, ['organizationName', 'organization_name'], '织序传输助手')),
    description: toStringValue(pick(raw, ['description'])),
    dueAt: toIsoLike(pick(raw, ['dueAt', 'due_at'])),
    accessCodeRequired: toBoolean(pick(raw, ['accessCodeRequired', 'access_code_required'], false), false),
    accessCodeVerified: toBoolean(pick(raw, ['accessCodeVerified', 'access_code_verified'], false), false),
    submitterFields: Array.isArray(raw?.submitterFields)
      ? raw.submitterFields.map((item: any) => normalizeField(item, taskId))
      : Array.isArray(raw?.submitter_fields)
        ? raw.submitter_fields.map((item: any) => normalizeField(item, taskId))
        : [],
    materials: Array.isArray(raw?.materials) ? raw.materials.map((item: any) => normalizeMaterial(item, taskId)) : [],
    status: toStringValue(pick(raw, ['status'], 'active')) as PublicSubmissionTaskVO['status'],
  }
}

const normalizeReceipt = (raw: any): SubmissionReceiptVO => ({
  id: toStringValue(pick(raw, ['id', 'submissionId', 'submission_id'])),
  submissionId: toStringValue(pick(raw, ['submissionId', 'submission_id', 'id'])),
  taskId: toStringValue(pick(raw, ['taskId', 'task_id'])),
  sourceSubmissionId: toStringValue(pick(raw, ['sourceSubmissionId', 'source_submission_id'])),
  receiptToken: toStringValue(pick(raw, ['receiptToken', 'receipt_token'])),
  receiptNumber: toStringValue(pick(raw, ['receiptNumber', 'receipt_number'], '')),
  submittedAt: toIsoLike(pick(raw, ['submittedAt', 'submitted_at'], new Date().toISOString())),
  materialSummary: toStringValue(pick(raw, ['materialSummary', 'material_summary'], '已提交材料')),
})

export const publicSubmissionTaskDataList: PublicSubmissionTaskData[] = []
export const submissionDraftDataList: SubmissionDraftData[] = []
export const publicSubmissionFileDataList: SubmissionFileData[] = []
export const publicMissingCheckResultDataList: MissingCheckResultData[] = []
export const publicReSubmissionNoticeDataList: ReSubmissionNoticeData[] = []

export class PublicSubmissionService {
  static async getPublicTask(taskId: string, accessCode = ''): Promise<PublicSubmissionTaskVO> {
    const data = await apiRequest<any>('file/collection/tasks/public', {
      params: { id: taskId, accessCode },
      auth: false,
    })
    return normalizeTask(data)
  }

  static async verifyAccessCode(taskId: string, accessCode: string): Promise<PublicSubmissionTaskVO> {
    const data = await apiRequest<any>('file/collection/tasks/verify_access_code', {
      method: 'POST',
      body: { id: taskId, accessCode },
      auth: false,
    })
    return normalizeTask(data)
  }

  static async submitPublic(payload: PublicSubmissionPayload): Promise<SubmissionReceiptVO> {
    const form = new FormData()
    form.append('taskId', payload.taskId)
    form.append('task_id', payload.taskId)
    form.append('accessCode', payload.accessCode || '')
    form.append('access_code', payload.accessCode || '')
    form.append('sourceSubmissionId', payload.sourceSubmissionId || '')
    form.append('source_submission_id', payload.sourceSubmissionId || '')
    const submitterFieldsJson = JSON.stringify(payload.submitterFields || {})
    form.append('submitterFields', submitterFieldsJson)
    form.append('submitter_fields', submitterFieldsJson)

    Object.entries(payload.filesByMaterialId).forEach(([materialId, files]) => {
      files.forEach((file) => {
        form.append('files[]', file, file.name)
        form.append('materialIds[]', materialId)
        form.append('material_ids[]', materialId)
        form.append('originalNames[]', file.name)
        form.append('original_names[]', file.name)
      })
    })

    const data = await apiUpload<any>('file/collection/submissions', form, '')
    return normalizeReceipt(data)
  }

  static async getReceipt(submissionId: string, receiptToken = ''): Promise<SubmissionReceiptVO> {
    const data = await apiRequest<any>('file/collection/submissions/receipt', {
      params: { id: submissionId, receiptToken },
      auth: false,
    })
    return normalizeReceipt(data)
  }

  static getTaskVOById(_taskId: string): PublicSubmissionTaskVO | undefined {
    return undefined
  }

  static getDraftByTaskId(_taskId: string): SubmissionDraftData | undefined {
    return undefined
  }

  static loadPersisted(): SubmissionDraftData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('submissionDraftDataList')
    }
    return null
  }

  static savePersisted(_items: SubmissionDraftData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('submissionDraftDataList')
    }
  }

  static submit(_taskId: string): SubmissionReceiptVO | undefined {
    return undefined
  }
}
