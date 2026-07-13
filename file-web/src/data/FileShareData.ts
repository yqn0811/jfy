export interface FileShareData {
  id: string
  shareCode?: string
  taskId: string
  title: string
  shareUrl: string
  password: string
  expiresAt: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
  status: 'draft' | 'generating' | 'active' | 'expired'
  fileCount: number
  totalSizeMb: number
  createdAt: string
  updatedAt: string
}

export interface UploadFileItemData {
  id: string
  shareId: string
  fileName: string
  fileSizeMb: number
  progress: number
  status: 'queued' | 'uploading' | 'success' | 'failed'
  failReason?: string
  retryCount: number
  uploadedAt?: string
}

export interface ShareSettingData {
  id: string
  shareId: string
  expiresIn: '24h' | '7d' | '30d' | '90d'
  accessPassword: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
}

export interface ShareAccessLogData {
  id: string
  shareId: string
  visitorName: string
  action: 'view' | 'download' | 'copy_link' | 'copy_password' | 'pickup'
  occurredAt: string
  ipLabel: string
}
