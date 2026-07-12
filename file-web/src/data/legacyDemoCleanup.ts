import type { FileShareData } from './FileShareData'

const LEGACY_DEMO_STORAGE_KEYS = [
  'taskDataList',
  'workspaceDataList',
  'teamDataList',
  'deliveryRecordDataList',
  'spaceDataList',
  'submissionDataList',
  'reviewLogDataList',
  'submissionDraftDataList',
  'templateDataList',
]

const LEGACY_DEMO_PREFIXED_KEYS = ['submission-draft-task-']

const isLegacyShare = (share: FileShareData) => {
  const id = String(share.id || '')
  const taskId = String(share.taskId || '')
  const shareUrl = String(share.shareUrl || '')
  return (
    /^share-00\d$/.test(id) ||
    /^task-00\d$/.test(taskId) ||
    /zxtransfer\.example|example\.com/i.test(shareUrl)
  )
}

export const cleanupLegacyDemoData = () => {
  if (typeof localStorage === 'undefined') return

  LEGACY_DEMO_STORAGE_KEYS.forEach((key) => localStorage.removeItem(key))

  Object.keys(localStorage).forEach((key) => {
    if (LEGACY_DEMO_PREFIXED_KEYS.some((prefix) => key.startsWith(prefix))) {
      localStorage.removeItem(key)
    }
  })

  const rawShares = localStorage.getItem('fileShareDataList')
  if (!rawShares) return

  try {
    const shares = JSON.parse(rawShares) as FileShareData[]
    if (!Array.isArray(shares)) {
      localStorage.removeItem('fileShareDataList')
      return
    }
    const filtered = shares.filter((share) => !isLegacyShare(share))
    if (filtered.length !== shares.length) {
      localStorage.setItem('fileShareDataList', JSON.stringify(filtered))
    }
  } catch {
    localStorage.removeItem('fileShareDataList')
  }
}
