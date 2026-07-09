import type { FileShareData, ShareAccessLogData, ShareSettingData, UploadFileItemData } from './FileShareData'

export interface FileShareVO {
  id: string
  title: string
  shareUrl: string
  password: string
  expiresAt: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
  status: FileShareData['status']
  fileCount: number
  totalSizeMb: number
  downloadCount: number
  recentLogs: ShareAccessLogData[]
}

export const fileShareDataList: FileShareData[] = [
  {
    id: 'share-001',
    taskId: 'task-002',
    title: '摄影交付链接 - 客户陈列馆',
    shareUrl: 'https://share.zxtransfer.example/s/9k2m4q',
    password: '482916',
    expiresAt: '2026-07-18T18:00:00Z',
    maxDownloads: 20,
    allowPreview: true,
    notifyOnDownload: true,
    status: 'active',
    fileCount: 8,
    totalSizeMb: 1840,
    createdAt: '2026-07-07T10:20:00Z',
    updatedAt: '2026-07-07T10:32:00Z'
  },
  {
    id: 'share-002',
    taskId: 'task-001',
    title: 'HR 入职资料收集 - 7 月批次',
    shareUrl: 'https://share.zxtransfer.example/s/1h7p8r',
    password: '603214',
    expiresAt: '2026-07-09T12:00:00Z',
    maxDownloads: 12,
    allowPreview: false,
    notifyOnDownload: true,
    status: 'active',
    fileCount: 5,
    totalSizeMb: 620,
    createdAt: '2026-07-01T03:00:00Z',
    updatedAt: '2026-07-06T06:10:00Z'
  },
  {
    id: 'share-003',
    taskId: 'task-005',
    title: '合同签署页补交通知',
    shareUrl: 'https://share.zxtransfer.example/s/2x8n5w',
    password: '714805',
    expiresAt: '2026-07-06T12:00:00Z',
    maxDownloads: 5,
    allowPreview: true,
    notifyOnDownload: false,
    status: 'expired',
    fileCount: 3,
    totalSizeMb: 220,
    createdAt: '2026-06-22T04:20:00Z',
    updatedAt: '2026-07-07T09:00:00Z'
  }
]

export const uploadFileItemDataList: UploadFileItemData[] = [
  {
    id: 'upload-001',
    shareId: 'share-001',
    fileName: '客户陈列馆_封面图_01.jpg',
    fileSizeMb: 12.4,
    progress: 100,
    status: 'success',
    uploadedAt: '2026-07-07T10:24:00Z',
    retryCount: 0
  },
  {
    id: 'upload-002',
    shareId: 'share-001',
    fileName: '客户陈列馆_成片_07.mp4',
    fileSizeMb: 820,
    progress: 64,
    status: 'uploading',
    retryCount: 0
  },
  {
    id: 'upload-003',
    shareId: 'share-001',
    fileName: '客户陈列馆_清单.xlsx',
    fileSizeMb: 1.8,
    progress: 100,
    status: 'success',
    uploadedAt: '2026-07-07T10:25:00Z',
    retryCount: 0
  },
  {
    id: 'upload-004',
    shareId: 'share-002',
    fileName: '身份证正面.jpg',
    fileSizeMb: 3.2,
    progress: 100,
    status: 'success',
    uploadedAt: '2026-07-01T03:18:00Z',
    retryCount: 0
  },
  {
    id: 'upload-005',
    shareId: 'share-002',
    fileName: '离职证明.pdf',
    fileSizeMb: 1.1,
    progress: 100,
    status: 'failed',
    failReason: '网络中断，请重试',
    retryCount: 1
  }
]

export const shareSettingDataList: ShareSettingData[] = [
  {
    id: 'setting-001',
    shareId: 'share-001',
    expiresIn: '30d',
    accessPassword: '482916',
    maxDownloads: 20,
    allowPreview: true,
    notifyOnDownload: true
  },
  {
    id: 'setting-002',
    shareId: 'share-002',
    expiresIn: '7d',
    accessPassword: '603214',
    maxDownloads: 12,
    allowPreview: false,
    notifyOnDownload: true
  }
]

export const shareAccessLogDataList: ShareAccessLogData[] = [
  {
    id: 'log-001',
    shareId: 'share-001',
    visitorName: '陈女士',
    action: 'view',
    occurredAt: '2026-07-07T11:05:00Z',
    ipLabel: '上海'
  },
  {
    id: 'log-002',
    shareId: 'share-001',
    visitorName: '陈女士',
    action: 'download',
    occurredAt: '2026-07-07T11:08:00Z',
    ipLabel: '上海'
  },
  {
    id: 'log-003',
    shareId: 'share-002',
    visitorName: '张老师',
    action: 'copy_link',
    occurredAt: '2026-07-01T03:20:00Z',
    ipLabel: '北京'
  }
]

export class FileShareService {
  static getAll(): FileShareData[] {
    return fileShareDataList
  }

  static getById(id: string): FileShareData | undefined {
    return fileShareDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'visibility' | 'allowPreview' | 'hasPassword', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): FileShareData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return fileShareDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.title.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = key === 'hasPassword' ? Boolean(item.password) : (item as any)[key]
          return Array.isArray(val) ? val.includes(String(itemVal)) : String(itemVal) === String(val)
        })
        return matchKeyword && matchFilter
      })
      .sort((a, b) => {
        if (!sortKey) return 0
        const aVal = (a as any)[sortKey]
        const bVal = (b as any)[sortKey]
        if (aVal === bVal) return 0
        const result = aVal > bVal ? 1 : -1
        return sortDirection === 'asc' ? result : -result
      })
  }

  static getShareVOById(id: string): FileShareVO | undefined {
    const share = this.getById(id)
    if (!share) return undefined
    return {
      id: share.id,
      title: share.title,
      shareUrl: share.shareUrl,
      password: share.password,
      expiresAt: share.expiresAt,
      maxDownloads: share.maxDownloads,
      allowPreview: share.allowPreview,
      notifyOnDownload: share.notifyOnDownload,
      status: share.status,
      fileCount: share.fileCount,
      totalSizeMb: share.totalSizeMb,
      downloadCount: shareAccessLogDataList.filter((log) => log.shareId === share.id && log.action === 'download').length,
      recentLogs: shareAccessLogDataList.filter((log) => log.shareId === share.id).slice(0, 5)
    }
  }

  static getAccessLogsByShareId(shareId: string): ShareAccessLogData[] {
    return shareAccessLogDataList.filter((item) => item.shareId === shareId)
  }

  static loadPersisted(): FileShareData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('fileShareDataList')
    return raw ? (JSON.parse(raw) as FileShareData[]) : null
  }

  static savePersisted(items: FileShareData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('fileShareDataList', JSON.stringify(items))
  }
}
