import type { CollectionTaskData, TaskFieldConfigData, TaskMaterialItemData, TaskRuleConfigData } from './CollectionTaskData'
import type { DeliveryRecordData } from './DeliveryRecordData'
import type { FileShareData, ShareAccessLogData } from './FileShareData'
import type { FileShareVO } from './FileShareService'
import type { TaskData } from './TaskData'
import { ApiError, apiDownload, apiRequest, apiUpload, authStore, buildApiUrl, isRecoverableApiRouteError, rawUpload } from '@/lib/apiClient'

export interface UploadedFileResult {
  id: string
  fileName: string
  fileSizeMb: number
  sizeBytes: number
  status: string
  transferToken?: string
  previewUrl?: string
  downloadUrl?: string
}

export interface DirectUploadPolicy {
  provider: string
  storageProvider: string
  objectKey: string
  uploadUrl: string
  method: 'PUT'
  headers: Record<string, string>
  expiresIn: number
  uploadExpiresAt: number
  uploadSignature: string
}

export interface CreateSharePayload {
  title: string
  fileIds: Array<string | number>
  transferToken?: string
  password: string
  pickupCode?: string
  expiresAt: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
}

export interface CreateCollectionTaskPayload {
  templateId?: string | null
  name: string
  description: string
  dueAt: string
  submitTargetDescription: string
  accessCode?: string
  fields: TaskFieldConfigData[]
  materials: TaskMaterialItemData[]
  ruleConfig: Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'>
}

export interface FileTransferShareVO extends FileShareVO {
  shareCode: string
  pickupCode?: string
  hasPassword: boolean
  passwordVerified: boolean
  files: UploadedFileResult[]
  createdAt: string
}

export interface CollectionTaskDetailVO {
  task: CollectionTaskData
  fields: TaskFieldConfigData[]
  materials: TaskMaterialItemData[]
  ruleConfig: TaskRuleConfigData
  raw: any
}

export interface FileTransferListResult<T> {
  items: T[]
  total: number
  page: number
  limit: number
}

export interface CollectionTaskQrcodeVO {
  taskId: string
  url: string
  qrcode: string
}

export interface FileShareQrcodeVO {
  shareCode: string
  url: string
  qrcode: string
}

const isBlank = (value: unknown) => value === undefined || value === null || value === ''
const ANONYMOUS_TRANSFER_TOKEN_KEY = 'file_web_anonymous_transfer_token'

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

export const makeShareExpiresAt = (expiresIn: '24h' | '7d' | '30d' | '90d') => {
  const dayMap = {
    '24h': 1,
    '7d': 7,
    '30d': 30,
    '90d': 90,
  }
  return new Date(Date.now() + dayMap[expiresIn] * 24 * 60 * 60 * 1000).toISOString()
}

export const rememberSharePassword = (shareCode: string, password: string) => {
  if (typeof sessionStorage === 'undefined' || !shareCode) return
  sessionStorage.setItem(`file-share-password:${shareCode}`, password)
}

export const getRememberedSharePassword = (shareCode: string) => {
  if (typeof sessionStorage === 'undefined' || !shareCode) return ''
  return sessionStorage.getItem(`file-share-password:${shareCode}`) || ''
}

export const getAnonymousTransferToken = () => {
  if (typeof localStorage === 'undefined') return ''
  const existing = localStorage.getItem(ANONYMOUS_TRANSFER_TOKEN_KEY)
  if (existing) return existing
  const bytes = new Uint8Array(16)
  if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
    crypto.getRandomValues(bytes)
  } else {
    for (let index = 0; index < bytes.length; index++) {
      bytes[index] = Math.floor(Math.random() * 256)
    }
  }
  const token = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
  localStorage.setItem(ANONYMOUS_TRANSFER_TOKEN_KEY, token)
  return token
}

export const toAbsoluteShareUrl = (shareUrl: string, shareCode = '') => {
  const code = shareCode.trim()
  if (!code) return ''

  const raw = (shareUrl || `/share-result?shareCode=${encodeURIComponent(code)}`).replace(/\.html(?=\?|$)/, '')
  const sourceUrl = new URL(raw, typeof window === 'undefined' ? 'https://file.jfyuntu.com' : window.location.origin)
  sourceUrl.pathname = sourceUrl.pathname.replace(/\.html$/, '')
  if (!sourceUrl.searchParams.get('shareCode') && !sourceUrl.searchParams.get('code')) {
    sourceUrl.searchParams.set('shareCode', code)
  }

  if (typeof window === 'undefined') {
    return `${sourceUrl.pathname}${sourceUrl.search}${sourceUrl.hash}`
  }

  const url = new URL(`${sourceUrl.pathname}${sourceUrl.search}${sourceUrl.hash}`, window.location.origin)
  if (!url.searchParams.get('shareCode') && !url.searchParams.get('code')) {
    url.searchParams.set('shareCode', code)
  }
  return url.toString()
}

export const normalizeUploadedFile = (raw: any): UploadedFileResult => {
  const sizeBytes = toNumber(pick(raw, ['sizeBytes', 'size_bytes'], 0))
  const rawSizeMb = toNumber(pick(raw, ['sizeMb', 'size_mb', 'fileSizeMb'], sizeBytes / 1024 / 1024))
  const sizeMb = rawSizeMb > 0 ? rawSizeMb : sizeBytes / 1024 / 1024
  return {
    id: toStringValue(pick(raw, ['id'])),
    fileName: toStringValue(pick(raw, ['fileName', 'file_name', 'originalName', 'original_name'])),
    fileSizeMb: sizeMb,
    sizeBytes,
    status: toStringValue(pick(raw, ['status'], 'uploaded')),
    transferToken: toStringValue(pick(raw, ['transferToken', 'transfer_token', 'ssoSubject', 'sso_subject'])),
    previewUrl: toStringValue(pick(raw, ['previewUrl', 'preview_url'])),
    downloadUrl: toStringValue(pick(raw, ['downloadUrl', 'download_url'])),
  }
}

const normalizeDirectUploadPolicy = (raw: any): DirectUploadPolicy => ({
  provider: toStringValue(pick(raw, ['provider', 'storageProvider', 'storage_provider'])),
  storageProvider: toStringValue(pick(raw, ['storageProvider', 'storage_provider', 'provider'])),
  objectKey: toStringValue(pick(raw, ['objectKey', 'object_key'])),
  uploadUrl: toStringValue(pick(raw, ['uploadUrl', 'upload_url'])),
  method: 'PUT',
  headers: (pick<Record<string, string>>(raw, ['headers'], {}) || {}) as Record<string, string>,
  expiresIn: toNumber(pick(raw, ['expiresIn', 'expires_in'], 0)),
  uploadExpiresAt: toNumber(pick(raw, ['uploadExpiresAt', 'upload_expires_at'], 0)),
  uploadSignature: toStringValue(pick(raw, ['uploadSignature', 'upload_signature'])),
})

export const normalizeShareAccessLog = (raw: any): ShareAccessLogData => ({
  id: toStringValue(pick(raw, ['id'])),
  shareId: toStringValue(pick(raw, ['shareId', 'share_id'])),
  visitorName: toStringValue(pick(raw, ['visitorName', 'visitor_name'], '访客')),
  action: toStringValue(pick(raw, ['action'], 'view')) as ShareAccessLogData['action'],
  occurredAt: toIsoLike(pick(raw, ['occurredAt', 'occurred_at'])),
  ipLabel: toStringValue(pick(raw, ['ipLabel', 'ip_label'])),
})

export const normalizeShareVO = (raw: any, password = ''): FileTransferShareVO => {
  const shareCode = toStringValue(pick(raw, ['shareCode', 'share_code']))
  const id = toStringValue(pick(raw, ['id', 'shareId', 'share_id'], shareCode || `share-${Date.now()}`))
  const files = Array.isArray(raw?.files) ? raw.files.map(normalizeUploadedFile) : []
  const totalSizeBytes = toNumber(pick(raw, ['totalSizeBytes', 'total_size_bytes'], 0))
  const rawTotalSizeMb = toNumber(pick(raw, ['totalSizeMb', 'total_size_mb'], totalSizeBytes / 1024 / 1024))
  const totalSizeMb = rawTotalSizeMb > 0 ? rawTotalSizeMb : totalSizeBytes / 1024 / 1024
  const recentLogs = Array.isArray(pick(raw, ['recentLogs', 'recent_logs'], []))
    ? pick<any[]>(raw, ['recentLogs', 'recent_logs'], []).map(normalizeShareAccessLog)
    : []
  return {
    id,
    shareCode,
    pickupCode: toStringValue(pick(raw, ['pickupCode', 'pickup_code'])),
    title: toStringValue(pick(raw, ['title'], '快速分享')),
    shareUrl: toAbsoluteShareUrl(toStringValue(pick(raw, ['shareUrl', 'share_url'])), shareCode),
    password,
    expiresAt: toIsoLike(pick(raw, ['expiresAt', 'expires_at'])),
    maxDownloads: toNumber(pick(raw, ['maxDownloads', 'max_downloads'], 0)),
    allowPreview: toBoolean(pick(raw, ['allowPreview', 'allow_preview'], true), true),
    notifyOnDownload: toBoolean(pick(raw, ['notifyOnDownload', 'notify_on_download'], false), false),
    status: toStringValue(pick(raw, ['status'], 'active')) as FileShareData['status'],
    fileCount: toNumber(pick(raw, ['fileCount', 'file_count'], Array.isArray(raw?.files) ? raw.files.length : 0)),
    totalSizeMb,
    downloadCount: toNumber(pick(raw, ['downloadCount', 'download_count'], 0)),
    recentLogs,
    hasPassword: toBoolean(pick(raw, ['hasPassword', 'has_password'], Boolean(password)), Boolean(password)),
    passwordVerified: toBoolean(pick(raw, ['passwordVerified', 'password_verified'], true), true),
    files,
    createdAt: toIsoLike(pick(raw, ['createdAt', 'created_at'], new Date().toISOString())),
  }
}

export const normalizeShareData = (raw: any, password = ''): FileShareData => {
  const vo = normalizeShareVO(raw, password)
  return {
    id: vo.id,
    shareCode: vo.shareCode,
    taskId: toStringValue(pick(raw, ['taskId', 'task_id'])),
    title: vo.title,
    shareUrl: vo.shareUrl,
    password: vo.password,
    expiresAt: vo.expiresAt,
    maxDownloads: vo.maxDownloads,
    allowPreview: vo.allowPreview,
    notifyOnDownload: vo.notifyOnDownload,
    status: vo.status,
    fileCount: vo.fileCount,
    totalSizeMb: vo.totalSizeMb,
    createdAt: toIsoLike(pick(raw, ['createdAt', 'created_at'], new Date().toISOString())),
    updatedAt: toIsoLike(pick(raw, ['updatedAt', 'updated_at'], new Date().toISOString())),
  }
}

const normalizeTaskStatus = (raw: any): CollectionTaskData['status'] => {
  const status = toStringValue(raw, 'collecting')
  if (status === 'active') return 'collecting'
  return status as CollectionTaskData['status']
}

const normalizeShareRecordStatus = (raw: any): DeliveryRecordData['status'] => {
  const status = toStringValue(raw, 'active')
  if (status === 'active') return 'approved'
  if (status === 'generating') return 'pending_review'
  if (status === 'draft') return 'draft'
  if (status === 'expired') return 'expired'
  return 'approved'
}

const normalizeField = (raw: any, taskId: string): TaskFieldConfigData => ({
  id: toStringValue(pick(raw, ['id'], `field-${taskId}-${pick(raw, ['fieldKey', 'field_key', 'fieldLabel', 'field_label'], Date.now())}`)),
  taskId,
  fieldKey: toStringValue(pick(raw, ['fieldKey', 'field_key', 'key'], 'field')),
  fieldLabel: toStringValue(pick(raw, ['fieldLabel', 'field_label', 'label'], '字段')),
  fieldType: toStringValue(pick(raw, ['fieldType', 'field_type', 'type'], 'text')) as TaskFieldConfigData['fieldType'],
  required: toBoolean(pick(raw, ['required'], true), true),
  placeholder: toStringValue(pick(raw, ['placeholder'])),
  order: toNumber(pick(raw, ['order', 'sort_order'], 0)),
})

const normalizeMaterial = (raw: any, taskId: string): TaskMaterialItemData => {
  const types = pick<any>(raw, ['fileTypes', 'file_types'], [])
  return {
    id: toStringValue(pick(raw, ['id'], `mat-${taskId}-${pick(raw, ['materialName', 'material_name'], Date.now())}`)),
    taskId,
    materialName: toStringValue(pick(raw, ['materialName', 'material_name', 'name'], '材料')),
    fileTypes: Array.isArray(types)
      ? types.map((item) => String(item))
      : String(types || '')
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean),
    required: toBoolean(pick(raw, ['required'], true), true),
    maxSizeMb: toNumber(pick(raw, ['maxSizeMb', 'max_size_mb'], 100)),
    order: toNumber(pick(raw, ['order', 'sort_order'], 0)),
  }
}

export const normalizeCollectionTaskDetail = (raw: any): CollectionTaskDetailVO => {
  const id = toStringValue(pick(raw, ['id'], `task-${Date.now()}`))
  const fields = Array.isArray(raw?.fields) ? raw.fields.map((item: any) => normalizeField(item, id)) : []
  const materials = Array.isArray(raw?.materials) ? raw.materials.map((item: any) => normalizeMaterial(item, id)) : []
  const ruleConfig: TaskRuleConfigData = {
    id: `rule-${id}`,
    taskId: id,
    namingRule: toStringValue(pick(raw, ['namingRule', 'naming_rule'])),
    allowResubmission: toBoolean(pick(raw, ['allowResubmission', 'allow_resubmission'], true), true),
    enableAICheck: toBoolean(pick(raw, ['enableAICheck', 'enable_ai_check'], false), false),
    anonymousSubmit: toBoolean(pick(raw, ['anonymousSubmit', 'anonymous_submit'], false), false),
    allowPreview: toBoolean(pick(raw, ['allowPreview', 'allow_preview'], false), false),
    reminderBeforeDueHours: toNumber(pick(raw, ['reminderBeforeDueHours', 'reminder_before_due_hours'], 24)),
  }

  return {
    task: {
      id,
      teamId: toStringValue(pick(raw, ['teamId', 'team_id'])),
      templateId: pick(raw, ['templateId', 'template_id'], null),
      name: toStringValue(pick(raw, ['name'], '收集任务')),
      description: toStringValue(pick(raw, ['description'])),
      status: normalizeTaskStatus(pick(raw, ['status'], 'collecting')),
      dueAt: toIsoLike(pick(raw, ['dueAt', 'due_at'])),
      submitTargetDescription: toStringValue(pick(raw, ['submitTargetDescription', 'submit_target_description'])),
      submitterFieldIds: fields.map((item) => item.id),
      materialItemIds: materials.map((item) => item.id),
      ruleConfigId: ruleConfig.id,
      createdAt: toIsoLike(pick(raw, ['createdAt', 'created_at'], new Date().toISOString())),
      updatedAt: toIsoLike(pick(raw, ['updatedAt', 'updated_at'], new Date().toISOString())),
      archivedAt: pick(raw, ['archivedAt', 'archived_at'], null),
      ownerId: toStringValue(pick(raw, ['ownerId', 'owner_id', 'ownerUserId', 'owner_user_id'])),
      accessCodeRequired: toBoolean(pick(raw, ['accessCodeRequired', 'access_code_required'], false), false),
    },
    fields,
    materials,
    ruleConfig,
    raw,
  }
}

const normalizeListResult = <T>(raw: any, mapper: (item: any) => T): FileTransferListResult<T> => {
  const items = Array.isArray(raw?.items) ? raw.items : Array.isArray(raw) ? raw : []
  return {
    items: items.map(mapper),
    total: toNumber(pick(raw, ['total'], items.length), items.length),
    page: toNumber(pick(raw, ['page'], 1), 1),
    limit: toNumber(pick(raw, ['limit'], items.length || 20), items.length || 20),
  }
}

export const collectionTaskToTaskData = (task: CollectionTaskData, raw: any = {}): TaskData => {
  const totalSizeBytes = toNumber(pick(raw, ['totalSizeBytes', 'total_size_bytes'], 0))
  const fileCount = toNumber(pick(raw, ['fileCount', 'file_count'], 0))
  const submitCount = toNumber(pick(raw, ['submitCount', 'submit_count'], 0))
  const progress = submitCount > 0 || fileCount > 0 ? Math.min(1, Math.max(0, submitCount / Math.max(submitCount, fileCount, 1))) : 0
  const dueTime = task.dueAt ? new Date(task.dueAt).getTime() : 0
  const hoursUntilDue = dueTime ? (dueTime - Date.now()) / 36e5 : Number.POSITIVE_INFINITY

  return {
    id: task.id,
    teamId: task.teamId,
    templateId: task.templateId,
    name: task.name,
    type: 'collection',
    status: task.status,
    submitProgress: progress,
    storageUsedGb: totalSizeBytes / 1024 / 1024 / 1024,
    storageLimitGb: 0,
    dueAt: task.dueAt,
    lastUpdatedAt: task.updatedAt || task.createdAt,
    createdAt: task.createdAt,
    ownerId: task.ownerId,
    overdueLevel: hoursUntilDue < 0 ? 'critical' : hoursUntilDue < 24 ? 'warning' : 'normal',
    isArchived: task.status === 'archived' || Boolean(task.archivedAt),
  }
}

export const shareToTaskData = (share: FileTransferShareVO | FileShareData): TaskData => {
  const expiresTime = share.expiresAt ? new Date(share.expiresAt).getTime() : 0
  const hoursUntilDue = expiresTime ? (expiresTime - Date.now()) / 36e5 : Number.POSITIVE_INFINITY
  const status = share.status === 'expired' || (expiresTime > 0 && expiresTime < Date.now()) ? 'expired' : 'approved'
  const shareCode = 'shareCode' in share ? share.shareCode || '' : ''
  const id = share.id

  return {
    id,
    teamId: '',
    templateId: null,
    name: share.title,
    type: 'send',
    status,
    submitProgress: 1,
    storageUsedGb: share.totalSizeMb / 1024,
    storageLimitGb: 0,
    dueAt: share.expiresAt,
    lastUpdatedAt: 'updatedAt' in share ? share.updatedAt : share.createdAt,
    createdAt: 'createdAt' in share ? share.createdAt : new Date().toISOString(),
    ownerId: '',
    overdueLevel: hoursUntilDue < 0 ? 'critical' : hoursUntilDue < 24 ? 'warning' : 'normal',
    isArchived: false,
    shareId: share.id,
    shareCode,
    shareUrl: share.shareUrl,
  }
}

export const shareToDeliveryRecord = (share: FileTransferShareVO | FileShareData): DeliveryRecordData => ({
  id: share.id,
  name: share.title,
  type: 'sent',
  status: share.status === 'expired' ? 'expired' : normalizeShareRecordStatus(share.status),
  fileCount: share.fileCount,
  storageSizeMb: share.totalSizeMb,
  createdAt: 'createdAt' in share ? share.createdAt : new Date().toISOString(),
  expiresAt: share.expiresAt,
  lastActorName: '我',
  isArchived: false,
  shareId: share.id,
  shareCode: 'shareCode' in share ? share.shareCode || '' : '',
  shareUrl: share.shareUrl,
})

export const collectionTaskToDeliveryRecord = (detail: CollectionTaskDetailVO): DeliveryRecordData => {
  const raw = detail.raw || {}
  return {
    id: detail.task.id,
    name: detail.task.name,
    type: detail.task.status === 'archived' ? 'archived' : 'collected',
    status: detail.task.status,
    fileCount: toNumber(pick(raw, ['fileCount', 'file_count'], 0)),
    storageSizeMb: toNumber(pick(raw, ['totalSizeMb', 'total_size_mb'], toNumber(pick(raw, ['totalSizeBytes', 'total_size_bytes'], 0)) / 1024 / 1024)),
    createdAt: detail.task.createdAt,
    expiresAt: detail.task.dueAt,
    lastActorName: '我',
    isArchived: detail.task.status === 'archived' || Boolean(detail.task.archivedAt),
  }
}

export class FileTransferApi {
  static async uploadFiles(files: File[]): Promise<UploadedFileResult[]> {
    const form = new FormData()
    form.append('transferToken', getAnonymousTransferToken())
    files.forEach((file) => {
      form.append('files[]', file, file.name)
      form.append('original_names[]', file.name)
    })
    const data = await apiUpload<{ items?: any[]; file_ids?: Array<string | number> }>(
      'file/files/upload',
      form,
      undefined,
      { optionalAuth: true }
    )
    return (data.items || []).map(normalizeUploadedFile)
  }

  static async uploadFileDirect(file: File): Promise<UploadedFileResult> {
    const transferToken = getAnonymousTransferToken()
    const policy = normalizeDirectUploadPolicy(await apiRequest<any>('file/files/direct_upload_policy', {
      method: 'POST',
      body: {
        originalName: file.name,
        storageProvider: 'ten_cos',
        mimeType: file.type || 'application/octet-stream',
        sizeBytes: file.size,
        transferToken,
      },
      auth: authStore.hasToken(),
    }))
    if (!policy.uploadUrl || !policy.objectKey || !policy.storageProvider || !policy.uploadSignature) {
      throw new ApiError('直传凭证生成失败，请重试')
    }

    await rawUpload(policy.uploadUrl, file, {
      method: policy.method,
      headers: policy.headers,
    })

    const data = await apiRequest<any>('file/files/register', {
      method: 'POST',
      body: {
        originalName: file.name,
        objectKey: policy.objectKey,
        storageProvider: policy.storageProvider,
        mimeType: file.type || 'application/octet-stream',
        sizeBytes: file.size,
        status: 'uploaded',
        transferToken,
        uploadSignature: policy.uploadSignature,
        uploadExpiresAt: policy.uploadExpiresAt,
      },
      auth: authStore.hasToken(),
    })
    return normalizeUploadedFile(data)
  }

  static async uploadFileWithBestPath(file: File): Promise<UploadedFileResult> {
    try {
      return await this.uploadFileDirect(file)
    } catch (error) {
      if (
        isRecoverableApiRouteError(error) ||
        (error instanceof ApiError && ['直传暂未开启', '对象存储配置不完整', '文件存储类型不支持'].some((text) => error.message.includes(text)))
      ) {
        const [uploaded] = await this.uploadFiles([file])
        if (!uploaded) throw new ApiError('上传失败，请重试')
        return uploaded
      }
      throw error
    }
  }

  static async createShare(payload: CreateSharePayload): Promise<FileTransferShareVO> {
    const data = await apiRequest<any>('file/shares', {
      method: 'POST',
      body: {
        title: payload.title,
        fileIds: payload.fileIds,
        transferToken: payload.transferToken || getAnonymousTransferToken(),
        password: payload.password,
        pickupCode: payload.pickupCode || payload.password,
        expiresAt: payload.expiresAt,
        maxDownloads: payload.maxDownloads,
        allowPreview: payload.allowPreview,
        notifyOnDownload: payload.notifyOnDownload,
      },
    })
    const shareCode = toStringValue(pick(data, ['shareCode', 'share_code']))
    if (!shareCode) {
      throw new Error('分享码生成失败，请重试')
    }
    rememberSharePassword(shareCode, payload.password)
    return normalizeShareVO(data, payload.password)
  }

  static async getOwnerShare(shareCode: string): Promise<FileTransferShareVO> {
    const isLoggedIn = authStore.hasToken()
    const data = await apiRequest<any>('file/shares/detail', {
      params: isLoggedIn ? { code: shareCode } : { code: shareCode, transferToken: getAnonymousTransferToken() },
      auth: isLoggedIn,
    })
    return normalizeShareVO(data, getRememberedSharePassword(shareCode))
  }

  static async getPublicShare(shareCode: string, password = ''): Promise<FileTransferShareVO> {
    const data = await apiRequest<any>('file/shares/public', {
      params: { code: shareCode, password },
      auth: false,
    })
    return normalizeShareVO(data, password || getRememberedSharePassword(shareCode))
  }

  static async getShareByPickupCode(pickupCode: string): Promise<FileTransferShareVO> {
    const code = pickupCode.trim()
    const data = await apiRequest<any>('file/shares/pickup', {
      params: { code },
      auth: false,
    })
    const share = normalizeShareVO(data, code)
    if (share.shareCode) {
      rememberSharePassword(share.shareCode, code)
    }
    return share
  }

  static async verifySharePassword(shareCode: string, password: string): Promise<FileTransferShareVO> {
    const data = await apiRequest<any>('file/shares/public', {
      params: { code: shareCode, password },
      auth: false,
    })
    rememberSharePassword(shareCode, password)
    return normalizeShareVO(data, password)
  }

  static async getShareQrcode(shareCode: string, url: string): Promise<FileShareQrcodeVO> {
    const data = await apiRequest<any>('file/shares/qrcode', {
      method: 'POST',
      body: { code: shareCode, url },
      auth: false,
    })
    return {
      shareCode: toStringValue(pick(data, ['shareCode', 'share_code'], shareCode)),
      url: toStringValue(pick(data, ['url'], url)),
      qrcode: toStringValue(pick(data, ['qrcode', 'qrCode', 'qr_code'])),
    }
  }

  static async listShares(params: { keyword?: string; status?: string; page?: number; limit?: number } = {}) {
    const isLoggedIn = authStore.hasToken()
    const data = await apiRequest<any>('file/shares', {
      params: isLoggedIn ? params : { ...params, transferToken: getAnonymousTransferToken() },
      auth: isLoggedIn,
    })
    return normalizeListResult(data, (item) => normalizeShareVO(item, getRememberedSharePassword(toStringValue(pick(item, ['shareCode', 'share_code'])))))
  }

  static getSharedDownloadUrl(fileId: string | number, shareCode: string, password = '', pickupCode = '') {
    return buildApiUrl('file/shares/download', { file_id: fileId, code: shareCode, password, pickup_code: pickupCode })
  }

  static getOwnerDownloadUrl(fileId: string | number) {
    return buildApiUrl('file/files/download', { file_id: fileId })
  }

  static async downloadOwnerFile(fileId: string | number, filename?: string) {
    return apiDownload('file/files/download', { params: { file_id: fileId }, filename })
  }

  static async createCollectionTask(payload: CreateCollectionTaskPayload): Promise<CollectionTaskDetailVO> {
    const data = await apiRequest<any>('file/collection/tasks', {
      method: 'POST',
      body: {
        templateId: payload.templateId,
        name: payload.name,
        description: payload.description,
        dueAt: payload.dueAt,
        submitTargetDescription: payload.submitTargetDescription,
        accessCode: payload.accessCode || '',
        fields: payload.fields.map((field, index) => ({
          fieldKey: field.fieldKey || `field_${index + 1}`,
          fieldLabel: field.fieldLabel,
          fieldType: field.fieldType,
          required: field.required,
          placeholder: field.placeholder,
          order: field.order || index,
        })),
        materials: payload.materials.map((material, index) => ({
          materialName: material.materialName,
          fileTypes: material.fileTypes,
          required: material.required,
          maxSizeMb: material.maxSizeMb,
          order: material.order || index,
        })),
        namingRule: payload.ruleConfig.namingRule,
        allowResubmission: payload.ruleConfig.allowResubmission,
        enableAICheck: payload.ruleConfig.enableAICheck,
        anonymousSubmit: payload.ruleConfig.anonymousSubmit,
        allowPreview: payload.ruleConfig.allowPreview,
        reminderBeforeDueHours: payload.ruleConfig.reminderBeforeDueHours,
      },
    })
    return normalizeCollectionTaskDetail(data)
  }

  static async getCollectionTask(taskId: string | number): Promise<CollectionTaskDetailVO> {
    const data = await apiRequest<any>('file/collection/tasks/detail', { params: { id: taskId } })
    return normalizeCollectionTaskDetail(data)
  }

  static async archiveCollectionTask(taskId: string | number): Promise<CollectionTaskDetailVO> {
    const data = await apiRequest<any>('file/collection/tasks/archive', {
      method: 'POST',
      body: { id: taskId },
    })
    return normalizeCollectionTaskDetail(data)
  }

  static async getCollectionTaskQrcode(taskId: string | number, url: string): Promise<CollectionTaskQrcodeVO> {
    const data = await apiRequest<any>('file/collection/tasks/qrcode', {
      method: 'POST',
      body: { taskId, url },
    })
    return {
      taskId: toStringValue(pick(data, ['taskId', 'task_id'], taskId)),
      url: toStringValue(pick(data, ['url'], url)),
      qrcode: toStringValue(pick(data, ['qrcode', 'qrCode', 'qr_code'])),
    }
  }

  static getCollectionTaskSubmissionsDownloadUrl(taskId: string | number) {
    return buildApiUrl('file/collection/tasks/submissions/download', { taskId })
  }

  static async downloadCollectionTaskSubmissions(taskId: string | number, filename?: string) {
    return apiDownload('file/collection/tasks/submissions/download', {
      params: { taskId },
      filename,
    })
  }

  static async listCollectionTasks(params: { keyword?: string; status?: string; page?: number; limit?: number } = {}) {
    const data = await apiRequest<any>('file/collection/tasks', { params })
    return normalizeListResult(data, normalizeCollectionTaskDetail)
  }
}
