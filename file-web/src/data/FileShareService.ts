import type { FileShareData, ShareAccessLogData, ShareSettingData, UploadFileItemData } from './FileShareData'

export interface FileShareVO {
  id: string
  shareCode?: string
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

export const fileShareDataList: FileShareData[] = []

export const uploadFileItemDataList: UploadFileItemData[] = []

export const shareSettingDataList: ShareSettingData[] = []

export const shareAccessLogDataList: ShareAccessLogData[] = []

const getShareCodeFromRecord = (share: Pick<FileShareData, 'shareCode' | 'shareUrl'>) => {
  if (share.shareCode) return share.shareCode
  try {
    const params = new URL(share.shareUrl || '/', window.location.origin).searchParams
    return params.get('shareCode') || params.get('code') || ''
  } catch {
    return ''
  }
}

const isValidShareRecord = (share: FileShareData) => {
  const shareCode = getShareCodeFromRecord(share)
  if (!shareCode) return false
  if (!share.shareUrl || /\/share-result\/?$/.test(share.shareUrl) || /\/share-result\.html\/?$/.test(share.shareUrl)) return false
  if (share.fileCount <= 0) return false
  return true
}

export class FileShareService {
  static getAll(): FileShareData[] {
    const shareMap = new Map<string, FileShareData>()
    fileShareDataList.forEach((item) => shareMap.set(item.id, item))
    this.loadPersisted()?.forEach((item) => shareMap.set(item.id, item))
    return Array.from(shareMap.values())
  }

  static getById(id: string): FileShareData | undefined {
    return this.getAll().find((item) => item.id === id)
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

    return this.getAll()
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
      shareCode: share.shareCode,
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
    if (!raw) return null
    const items = JSON.parse(raw) as FileShareData[]
    const filtered = items.filter((item) => {
      const shareUrl = String(item.shareUrl || '')
      const id = String(item.id || '')
      const taskId = String(item.taskId || '')
      return (
        !/^share-00\d$/.test(id) &&
        !/^task-00\d$/.test(taskId) &&
        !/zxtransfer\.example|example\.com/i.test(shareUrl) &&
        isValidShareRecord(item)
      )
    })
    if (filtered.length !== items.length) {
      localStorage.setItem('fileShareDataList', JSON.stringify(filtered))
    }
    return filtered
  }

  static savePersisted(items: FileShareData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('fileShareDataList', JSON.stringify(items))
  }
}
