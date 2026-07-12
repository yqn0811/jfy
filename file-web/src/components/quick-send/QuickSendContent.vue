<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Progress } from '@/components/ui/progress'
import { Textarea } from '@/components/ui/textarea'
import SafeIcon from '@/components/common/SafeIcon.vue'
import FileListItem from '@/components/common/FileListItem.vue'
import { FileShareService } from '@/data/FileShareService'
import {
  FileTransferApi,
  getAnonymousTransferToken,
  makeShareExpiresAt,
  normalizeShareData,
  normalizeShareVO,
  toAbsoluteShareUrl,
  type FileTransferShareVO,
} from '@/data/FileTransferApi'
import { authStore, getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

interface UploadFile {
  id: string
  file: File
  fileName: string
  fileSizeMb: number
  progress: number
  status: 'pending' | 'uploading' | 'success' | 'failed'
  failReason?: string
  retryCount: number
  backendFileId?: string
}

interface ShareSettings {
  expiresIn: '7d' | '30d' | '90d'
  accessPassword: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
}

type SendMode = 'standard' | 'image'
type UtilityPanel = 'pickup' | 'recent' | 'support' | 'security'
type LegalDialogType = 'service' | 'privacy'
type ShareRecord = ReturnType<typeof FileShareService.getAll>[number]

const MAX_FILE_SIZE_MB = 500

const fileInput = ref<HTMLInputElement | null>(null)
const folderInput = ref<HTMLInputElement | null>(null)
const uploadedFiles = ref<UploadFile[]>([])
const isDragging = ref(false)
const isGenerating = ref(false)
const isUploadProgressOpen = ref(false)
const isPickupCodeOpen = ref(false)
const isTextDialogOpen = ref(false)
const textFileName = ref('临时文本.txt')
const textFileContent = ref('')
const termsAccepted = ref(true)
const sendMode = ref<SendMode>('standard')
const activePanel = ref<UtilityPanel>('pickup')
const pickupCode = ref('')
const pickupResult = ref<ShareRecord | null>(null)
const pickupSearched = ref(false)
const legalDialog = ref<LegalDialogType | null>(null)
const generatedShare = ref<FileTransferShareVO | null>(null)
const isShareResultOpen = ref(false)
const shareQrcode = ref('')
const isShareQrcodeLoading = ref(false)

const shareSettings = ref<ShareSettings>({
  expiresIn: '30d',
  accessPassword: generatePassword(),
  maxDownloads: 20,
  allowPreview: true,
  notifyOnDownload: true,
})

const modeOptions: Array<{
  value: SendMode
  label: string
  description: string
  icon: string
}> = [
  {
    value: 'standard',
    label: '常规文件',
    description: '文档、压缩包、视频都能发',
    icon: 'SendHorizontal',
  },
  {
    value: 'image',
    label: '图片发送',
    description: '只选择图片格式，适合素材交付',
    icon: 'Images',
  },
]

const panelTabs: Array<{ value: UtilityPanel; label: string; icon: string }> = [
  { value: 'pickup', label: '取件', icon: 'KeyRound' },
  { value: 'recent', label: '最近', icon: 'CalendarCheck' },
  { value: 'support', label: '客服', icon: 'BadgeHelp' },
  { value: 'security', label: '安全', icon: 'ShieldCheck' },
]

function generatePassword(): string {
  return Math.floor(100000 + Math.random() * 900000).toString()
}

const totalSizeMb = computed(() => {
  return uploadedFiles.value.reduce((sum, file) => sum + file.fileSizeMb, 0)
})

const hasFiles = computed(() => uploadedFiles.value.length > 0)

const allFilesUploaded = computed(() => {
  return hasFiles.value && uploadedFiles.value.every((file) => file.status === 'success')
})

const canGenerateLink = computed(() => {
  return allFilesUploaded.value && termsAccepted.value && !isGenerating.value
})

const generateButtonLabel = computed(() => {
  if (isGenerating.value) return '生成中...'
  if (hasFiles.value && !allFilesUploaded.value) return '文件上传中...'
  return '生成分享链接'
})

const fileAccept = computed(() => {
  return sendMode.value === 'image' ? 'image/*' : '*'
})

const pickerLabel = computed(() => {
  return sendMode.value === 'image' ? '选择图片' : '选择文件'
})

const emptyHint = computed(() => {
  return sendMode.value === 'image' ? '或把图片拖拽到上传区' : '或把文件拖拽到上传区'
})

const expiresLabel = computed(() => {
  if (!authStore.hasToken()) return '24 小时'
  const map: Record<ShareSettings['expiresIn'], string> = {
    '7d': '7 天',
    '30d': '30 天',
    '90d': '90 天',
  }
  return map[shareSettings.value.expiresIn]
})

const sendStats = computed(() => [
  {
    label: '已选文件',
    value: `${uploadedFiles.value.length} 个`,
    icon: 'Files',
    tone: 'blue',
  },
  {
    label: '合计大小',
    value: formatShareSize(totalSizeMb.value),
    icon: 'HardDrive',
    tone: 'green',
  },
  {
    label: '链接有效期',
    value: expiresLabel.value,
    icon: 'Clock3',
    tone: 'amber',
  },
])

const recentShares = computed<ShareRecord[]>(() => {
  const shareMap = new Map<string, ShareRecord>()
  const persistedShares = FileShareService.loadPersisted() ?? []

  persistedShares.forEach((share) => {
    const existing = shareMap.get(share.id)
    if (!existing || new Date(share.updatedAt).getTime() > new Date(existing.updatedAt).getTime()) {
      shareMap.set(share.id, share)
    }
  })

  return Array.from(shareMap.values())
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    .slice(0, 4)
})

const normalizedPickupCode = computed(() => pickupCode.value.trim())

const isLegalDialogOpen = computed({
  get: () => legalDialog.value !== null,
  set: (open: boolean) => {
    if (!open) legalDialog.value = null
  },
})

const shareResultLink = computed(() => {
  if (!generatedShare.value) return ''
  return toAbsoluteShareUrl(generatedShare.value.shareUrl, generatedShare.value.shareCode)
})

const shareResultPassword = computed(() => generatedShare.value?.password || '')
const shareResultPasswordDisplay = computed(() => shareResultPassword.value || '当前记录未返回访问密码')
const shareResultSummary = computed(() => {
  if (!generatedShare.value) return ''
  return `${generatedShare.value.fileCount} 个文件，${formatShareSize(generatedShare.value.totalSizeMb)}`
})

const shareResultExpiresText = computed(() => {
  if (!generatedShare.value?.expiresAt) return authStore.hasToken() ? expiresLabel.value : '24 小时'
  const expiresAt = new Date(generatedShare.value.expiresAt)
  if (Number.isNaN(expiresAt.getTime())) return authStore.hasToken() ? expiresLabel.value : '24 小时'
  const hours = Math.max(1, Math.ceil((expiresAt.getTime() - Date.now()) / (60 * 60 * 1000)))
  if (hours < 48) return `${hours} 小时`
  return `${Math.ceil(hours / 24)} 天`
})

const uploadProgressPercent = computed(() => {
  if (!uploadedFiles.value.length) return 0
  const total = uploadedFiles.value.reduce((sum, file) => sum + file.progress, 0)
  return Math.round(total / uploadedFiles.value.length)
})

const uploadDialogTitle = computed(() => {
  if (!uploadedFiles.value.length) return '等待选择文件'
  if (uploadedFiles.value.some((file) => file.status === 'failed')) return '部分文件上传失败'
  if (allFilesUploaded.value) return '文件已上传完成'
  return '正在上传文件'
})

const uploadDialogDescription = computed(() => {
  if (!uploadedFiles.value.length) return '请选择要发送给对方的文件。'
  if (allFilesUploaded.value) return '文件已准备好，可以继续发送。'
  return `已选择 ${uploadedFiles.value.length} 个文件，正在写入安全存储。`
})

const legalDialogTitle = computed(() => {
  return legalDialog.value === 'privacy' ? '用户隐私政策' : '用户服务协议'
})

const legalDialogPoints = computed(() => {
  if (legalDialog.value === 'privacy') {
    return [
      '仅保存完成传输所需的文件名、链接状态和访问记录。',
      '不会把取件码、访问密码展示给无权限的访客。',
      '你可以在交付记录中管理过期、撤回和下载通知。',
    ]
  }

  return [
    '请勿上传违法、侵权、涉密或其他违规内容。',
    '分享链接默认需要访问密码，生成后可以继续管理有效期。',
    '接收方访问、预览和下载行为会记录在交付记录中。',
  ]
})

const openFilePicker = () => {
  fileInput.value?.click()
}

const openFolderPicker = () => {
  folderInput.value?.click()
}

const openTextDialog = () => {
  textFileName.value = '临时文本.txt'
  textFileContent.value = ''
  isTextDialogOpen.value = true
}

const validateFiles = (files: FileList): boolean => {
  for (let index = 0; index < files.length; index++) {
    const file = files[index]
    if (sendMode.value === 'image' && !file.type.startsWith('image/')) {
      toast.error(`图片发送只支持图片文件: "${file.name}"`)
      return false
    }

    if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
      toast.error(`文件 "${file.name}" 超过最大限制 ${MAX_FILE_SIZE_MB}MB`)
      return false
    }
  }
  return true
}

const appendFiles = (files: FileList) => {
  if (!validateFiles(files)) return
  isUploadProgressOpen.value = true

  for (let index = 0; index < files.length; index++) {
    const file = files[index]
    const id = `upload-${Date.now()}-${index}`
    const fileSizeMb = parseFloat((file.size / (1024 * 1024)).toFixed(2))

    uploadedFiles.value.push({
      id,
      file,
      fileName: file.name,
      fileSizeMb,
      progress: 0,
      status: 'pending',
      retryCount: 0,
    })

    uploadFile(id)
  }
}

const handleInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    appendFiles(target.files)
    target.value = ''
  }
}

const handleFolderInputChange = (event: Event) => {
  handleInputChange(event)
}

const handleCreateTextFile = () => {
  const content = textFileContent.value.trim()
  if (!content) {
    toast.error('请先输入文本内容')
    return
  }

  const safeName = (textFileName.value.trim() || '临时文本.txt').replace(/[\\/:*?"<>|\r\n]+/g, '_')
  const fileName = safeName.includes('.') ? safeName : `${safeName}.txt`
  const file = new File([content], fileName, { type: 'text/plain;charset=utf-8' })
  const dataTransfer = new DataTransfer()
  dataTransfer.items.add(file)
  appendFiles(dataTransfer.files)
  isTextDialogOpen.value = false
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = true
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
    appendFiles(event.dataTransfer.files)
  }
}

async function uploadFile(fileId: string) {
  const fileItem = uploadedFiles.value.find((file) => file.id === fileId)
  if (!fileItem) return

  fileItem.status = 'uploading'
  fileItem.progress = 8
  fileItem.failReason = undefined

  const timer = window.setInterval(() => {
    const latest = uploadedFiles.value.find((file) => file.id === fileId)
    if (!latest || latest.status !== 'uploading') {
      window.clearInterval(timer)
      return
    }
    latest.progress = Math.min(88, latest.progress + 12)
  }, 300)

  try {
    const [uploaded] = await FileTransferApi.uploadFiles([fileItem.file])
    const latest = uploadedFiles.value.find((file) => file.id === fileId)
    if (!latest) return
    latest.backendFileId = uploaded?.id
    latest.fileSizeMb = uploaded?.fileSizeMb || latest.fileSizeMb
    latest.status = 'success'
    latest.progress = 100
    toast.success(`文件 "${latest.fileName}" 上传成功`)
  } catch (error) {
    const latest = uploadedFiles.value.find((file) => file.id === fileId)
    if (!latest) return

    latest.status = 'failed'
    latest.progress = 0
    latest.failReason = getApiErrorMessage(error, '上传失败，请重试')
    toast.error(`文件 "${latest.fileName}" 上传失败`)
  } finally {
    window.clearInterval(timer)
  }
}

const handleRetryUpload = (fileId: string) => {
  const fileItem = uploadedFiles.value.find((file) => file.id === fileId)
  if (fileItem) {
    fileItem.retryCount += 1
    fileItem.failReason = undefined
    uploadFile(fileId)
  }
}

const handleRemoveFile = (fileId: string) => {
  uploadedFiles.value = uploadedFiles.value.filter((file) => file.id !== fileId)
}

const handleClearFiles = () => {
  uploadedFiles.value = []
  isUploadProgressOpen.value = false
}

const handleModeSelect = (mode: SendMode) => {
  if (sendMode.value === mode) return
  sendMode.value = mode
  toast.success(mode === 'image' ? '已切换为图片发送' : '已切换为常规文件发送')
}

const handlePanelSelect = (panel: UtilityPanel) => {
  activePanel.value = panel
}

const handlePickupSearch = () => {
  pickupSearched.value = true
  pickupResult.value = null

  if (normalizedPickupCode.value.length < 4) {
    toast.error('请输入取件码或分享口令')
    return
  }

  const keyword = normalizedPickupCode.value.toLowerCase()
  const match = recentShares.value.find((share) => {
    const urlToken = share.shareUrl.split('/').pop()?.toLowerCase()
    return share.password === normalizedPickupCode.value || share.id.toLowerCase() === keyword || urlToken === keyword
  })

  if (!match) {
    toast.error('没有找到对应文件')
    return
  }

  pickupResult.value = match
  toast.success('已找到分享记录')
}

const getShareCodeFromRecord = (share: Pick<ShareRecord, 'shareCode' | 'shareUrl'>) => {
  if (share.shareCode) return share.shareCode
  try {
    return new URL(share.shareUrl || '/', window.location.origin).searchParams.get('shareCode') || ''
  } catch {
    return ''
  }
}

const openShareResultDialog = async (share: ShareRecord | FileTransferShareVO) => {
  const shareCode = getShareCodeFromRecord(share)
  const localPassword = share.password || shareSettings.value.accessPassword
  generatedShare.value = normalizeShareVO({ ...share, shareCode }, localPassword)
  isShareResultOpen.value = true
  shareQrcode.value = ''
  void loadShareQrcode()

  if (!shareCode) return

  try {
    const remoteShare = await FileTransferApi.getOwnerShare(shareCode)
    generatedShare.value = {
      ...remoteShare,
      password: localPassword || remoteShare.password,
    }
    if (!shareQrcode.value) void loadShareQrcode()
  } catch {
    // 本地记录已经足够展示链接、密码和二维码；远端详情失败时不打断 A 端复制分享。
  }
}

const handleOpenShare = (share: ShareRecord) => {
  void openShareResultDialog(share)
}

const handleSupport = () => {
  activePanel.value = 'support'
  toast.success('客服支持已打开')
}

const handleCopySupport = async () => {
  const supportText = '织序传输客服: 400-821-1024, 服务时间 09:00-21:00'
  try {
    await navigator.clipboard.writeText(supportText)
    toast.success('客服信息已复制')
  } catch {
    toast.error('复制失败，请手动记录客服信息')
  }
}

const handleOpenLegal = (type: LegalDialogType) => {
  legalDialog.value = type
}

const handleNoticeHelp = () => {
  activePanel.value = 'security'
  toast.success('已打开安全说明')
}

const copyText = async (text: string, successText: string) => {
  if (!text) {
    toast.error('内容为空，无法复制')
    return
  }
  try {
    await navigator.clipboard.writeText(text)
    toast.success(successText)
  } catch {
    toast.error('复制失败，请手动复制')
  }
}

const handleCopyShareLink = () => {
  copyText(shareResultLink.value, '分享链接已复制')
}

const handleCopySharePassword = () => {
  copyText(shareResultPassword.value, '访问密码已复制')
}

const handleOpenPickupCode = () => {
  if (!generatedShare.value) return
  isPickupCodeOpen.value = true
}

const loadShareQrcode = async () => {
  if (!generatedShare.value?.shareCode || shareQrcode.value || isShareQrcodeLoading.value) return

  try {
    isShareQrcodeLoading.value = true
    const result = await FileTransferApi.getShareQrcode(generatedShare.value.shareCode, shareResultLink.value)
    shareQrcode.value = result.qrcode
  } catch (error) {
    toast.error(getApiErrorMessage(error, '二维码生成失败，已保留分享链接'))
  } finally {
    isShareQrcodeLoading.value = false
  }
}

const handleRefreshShareQrcode = async () => {
  shareQrcode.value = ''
  await loadShareQrcode()
}

const handleDownloadShareQrcode = () => {
  if (!shareQrcode.value) {
    toast.info('二维码尚未生成')
    return
  }
  const link = document.createElement('a')
  link.href = shareQrcode.value
  link.download = `${generatedShare.value?.title || '分享链接'}-二维码.png`.replace(/[\\/:*?"<>|\r\n]+/g, '_')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleCreateAnotherShare = () => {
  isShareResultOpen.value = false
  isPickupCodeOpen.value = false
  generatedShare.value = null
  shareQrcode.value = ''
  uploadedFiles.value = []
  shareSettings.value.accessPassword = generatePassword()
}

const handleManageGeneratedShare = () => {
  if (!generatedShare.value) return
  const params = new URLSearchParams()
  if (generatedShare.value.shareCode) params.set('shareCode', generatedShare.value.shareCode)
  if (generatedShare.value.id) params.set('shareId', generatedShare.value.id)
  isShareResultOpen.value = false
  navigateTo(`/share-result?${params.toString()}`)
}

const handleGenerateLink = async () => {
  if (!termsAccepted.value) {
    toast.error('请先同意用户协议和隐私政策')
    return
  }

  if (!allFilesUploaded.value) {
    toast.error(hasFiles.value ? '文件仍在上传中，请稍后' : '请先选择文件')
    return
  }

  if (!Number.isFinite(shareSettings.value.maxDownloads) || shareSettings.value.maxDownloads < 1) {
    toast.error('最大下载次数不能小于 1')
    return
  }

  isGenerating.value = true

  try {
    const fileIds = uploadedFiles.value
      .map((file) => file.backendFileId)
      .filter(Boolean) as string[]

    if (fileIds.length !== uploadedFiles.value.length) {
      throw new Error('文件仍在上传中，请稍后')
    }

    const share = await FileTransferApi.createShare({
      title: `快速分享 - ${new Date().toLocaleString('zh-CN')}`,
      fileIds,
      transferToken: getAnonymousTransferToken(),
      password: shareSettings.value.accessPassword,
      expiresAt: makeShareExpiresAt(authStore.hasToken() ? shareSettings.value.expiresIn : '24h'),
      maxDownloads: shareSettings.value.maxDownloads,
      allowPreview: shareSettings.value.allowPreview,
      notifyOnDownload: shareSettings.value.notifyOnDownload,
    })

    const allShares = FileShareService.loadPersisted() ?? []
    allShares.push(normalizeShareData(share, shareSettings.value.accessPassword))
    FileShareService.savePersisted(allShares)

    generatedShare.value = share
    isShareResultOpen.value = true
    isUploadProgressOpen.value = false
    shareQrcode.value = ''
    void loadShareQrcode()
    toast.success('分享链接已生成')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '生成分享链接失败，请重试'))
  } finally {
    isGenerating.value = false
  }
}

const openShareResultFromQuery = async () => {
  const params = new URLSearchParams(window.location.search)
  const isLegacyShareResultPage = window.location.pathname.replace(/\.html$/, '').replace(/\/+$/, '') === '/share-result'
  const shouldOpenDialog = params.get('shareResult') === '1' || isLegacyShareResultPage
  if (!shouldOpenDialog) return

  const queryShareId = params.get('shareId') || ''
  const queryShareCode = params.get('shareCode') || params.get('code') || ''
  const localShare = queryShareId
    ? FileShareService.getById(queryShareId)
    : FileShareService.getAll().find((share) => getShareCodeFromRecord(share) === queryShareCode)

  try {
    if (localShare) {
      await openShareResultDialog({
        ...localShare,
        shareCode: queryShareCode || localShare.shareCode,
      })
    } else if (queryShareCode) {
      const remoteShare = await FileTransferApi.getOwnerShare(queryShareCode)
      generatedShare.value = remoteShare
      isShareResultOpen.value = true
      shareQrcode.value = ''
      void loadShareQrcode()
    } else {
      toast.error('没有找到对应的分享记录')
    }
  } catch (error) {
    toast.error(getApiErrorMessage(error, '分享信息加载失败'))
  } finally {
    params.delete('shareResult')
    params.delete('shareId')
    params.delete('shareCode')
    params.delete('code')
    const nextQuery = params.toString()
    window.history.replaceState({}, '', `/quick-send${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`)
  }
}

function formatShareSize(sizeMb: number): string {
  if (sizeMb >= 1024) {
    return `${(sizeMb / 1024).toFixed(2)} GB`
  }
  return `${sizeMb.toFixed(2)} MB`
}

function formatShareDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  requestAnimationFrame(() => {
    if (location.pathname === '/') {
      navigateTo('/quick-send')
    }
  })
  void openShareResultFromQuery()
})
</script>

<template>
  <div class="page-body quick-send-page min-h-[calc(100vh-var(--header-height))]">
    <div class="app-shell quick-send-container">
      <section class="quick-workspace" aria-label="快速发文件工作区">
        <aside class="control-panel">
          <div class="intro-block">
            <span class="intro-kicker">快速发送</span>
            <h1>把文件发给对方</h1>
            <p>选择文件、设置访问方式，然后生成一个可追踪的分享链接。</p>
          </div>

          <div class="mode-list" aria-label="发送模式">
            <button
              v-for="mode in modeOptions"
              :key="mode.value"
              type="button"
              class="mode-option"
              :class="{ 'is-active': sendMode === mode.value }"
              @click="handleModeSelect(mode.value)"
            >
              <span class="mode-icon">
                <SafeIcon :name="mode.icon" :size="18" />
              </span>
              <span class="mode-copy">
                <span>{{ mode.label }}</span>
                <small>{{ mode.description }}</small>
              </span>
              <SafeIcon v-if="sendMode === mode.value" name="Check" :size="16" class="mode-check" />
            </button>
          </div>

          <div class="stat-grid">
            <div
              v-for="stat in sendStats"
              :key="stat.label"
              class="stat-tile"
              :class="`tone-${stat.tone}`"
            >
              <SafeIcon :name="stat.icon" :size="18" />
              <span>{{ stat.label }}</span>
              <strong>{{ stat.value }}</strong>
            </div>
          </div>

          <Button variant="outline" class="panel-link-button" @click="handlePanelSelect('recent')">
            <SafeIcon name="History" :size="16" />
            查看最近发送
          </Button>
        </aside>

        <main class="transfer-column">
          <div class="command-bar" aria-label="快捷操作">
            <Button variant="outline" class="command-pill" @click="handlePanelSelect('pickup')">
              <SafeIcon name="KeyRound" :size="16" />
              钥匙串 / 取件码
            </Button>
            <Button variant="ghost" size="icon" class="command-icon" title="图片发送" @click="handleModeSelect('image')">
              <SafeIcon name="Image" :size="19" />
            </Button>
            <Button variant="ghost" size="icon" class="command-icon" title="最近发送" @click="handlePanelSelect('recent')">
              <SafeIcon name="CalendarCheck" :size="19" />
            </Button>
            <Button variant="ghost" size="icon" class="command-icon" title="客服支持" @click="handleSupport">
              <SafeIcon name="BadgeHelp" :size="19" />
            </Button>
          </div>

          <input
            ref="fileInput"
            type="file"
            class="hidden"
            multiple
            :accept="fileAccept"
            @change="handleInputChange"
          />
          <input
            ref="folderInput"
            type="file"
            class="hidden"
            multiple
            webkitdirectory
            directory
            @change="handleFolderInputChange"
          />

          <div
            class="transfer-card"
            :class="{ 'is-dragging': isDragging }"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
          >
            <div class="transfer-card-header">
              <div>
                <span class="send-badge">
                  {{ sendMode === 'image' ? '图片模式' : '发文件' }}
                </span>
                <h2>简单两步完成发送</h2>
              </div>
              <Button v-if="hasFiles" variant="outline" size="sm" @click="openFilePicker">
                <SafeIcon name="Plus" :size="15" />
                继续添加
              </Button>
            </div>

            <div v-if="!hasFiles" class="transfer-empty">
              <div class="upload-symbol">
                <SafeIcon :name="sendMode === 'image' ? 'ImagePlus' : 'FileUp'" :size="42" />
              </div>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button class="choose-file-button">
                    <SafeIcon name="FilePlus2" :size="18" />
                    {{ pickerLabel }}
                    <SafeIcon name="ChevronDown" :size="16" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center" class="source-menu w-64">
                  <DropdownMenuItem class="source-menu-item" @click="openFilePicker">
                    <SafeIcon name="FileText" :size="18" />
                    <span>
                      <strong>单个、多个文件</strong>
                      <small>选择本地文件上传</small>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem class="source-menu-item" @click="openFolderPicker">
                    <SafeIcon name="FolderOpen" :size="18" />
                    <span>
                      <strong>整个文件夹</strong>
                      <small>保留文件夹中的文件结构</small>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem class="source-menu-item" @click="toast.info('空间选择稍后接入')">
                    <SafeIcon name="Cloud" :size="18" />
                    <span>
                      <strong>从空间选择</strong>
                      <small>选择已归档文件</small>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem class="source-menu-item" @click="openTextDialog">
                    <SafeIcon name="PencilLine" :size="18" />
                    <span>
                      <strong>写文本</strong>
                      <small>生成一个文本文件发送</small>
                    </span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
              <p>{{ emptyHint }}</p>
              <div class="format-row">
                <span>最大 500MB</span>
                <span>密码访问</span>
                <span>可追踪下载</span>
              </div>
            </div>

            <div v-else class="transfer-filled">
              <div class="selected-summary">
                <div>
                  <p class="text-item-title">已选择 {{ uploadedFiles.length }} 个文件</p>
                  <p class="text-caption">合计 {{ totalSizeMb.toFixed(2) }} MB</p>
                </div>
                <div class="selected-actions">
                  <Button
                    class="generate-link-button"
                    :disabled="!canGenerateLink"
                    @click="handleGenerateLink"
                  >
                    <SafeIcon v-if="isGenerating" name="Loader2" :size="16" class="animate-spin" />
                    <SafeIcon v-else name="Link2" :size="16" />
                    {{ generateButtonLabel }}
                  </Button>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="outline" size="sm">
                        继续添加
                        <SafeIcon name="ChevronDown" :size="14" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                      <DropdownMenuItem class="gap-2" @click="openFilePicker">
                        <SafeIcon name="FileText" :size="16" />
                        添加文件
                      </DropdownMenuItem>
                      <DropdownMenuItem class="gap-2" @click="openFolderPicker">
                        <SafeIcon name="FolderOpen" :size="16" />
                        添加文件夹
                      </DropdownMenuItem>
                      <DropdownMenuItem class="gap-2" @click="openTextDialog">
                        <SafeIcon name="PencilLine" :size="16" />
                        写文本
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                  <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="handleClearFiles">
                    清空
                  </Button>
                </div>
              </div>

              <div class="file-list-scroll">
                <FileListItem
                  v-for="file in uploadedFiles"
                  :key="file.id"
                  :file-name="file.fileName"
                  :file-size="file.fileSizeMb * 1024 * 1024"
                  :status="file.status === 'failed' ? 'error' : file.status"
                  :progress="Math.round(file.progress)"
                  :error-message="file.failReason"
                >
                  <template #actions>
                    <Button
                      v-if="file.status === 'failed'"
                      variant="ghost"
                      size="sm"
                      class="h-7 px-2 text-primary hover:bg-primary/10"
                      @click="handleRetryUpload(file.id)"
                    >
                      重试
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7 text-muted-foreground hover:text-destructive"
                      @click="handleRemoveFile(file.id)"
                    >
                      <SafeIcon name="Trash2" :size="16" />
                    </Button>
                  </template>
                </FileListItem>
              </div>
            </div>
          </div>

          <button type="button" class="notice-bar" @click="handleNoticeHelp">
            <SafeIcon name="AlertTriangle" :size="16" />
            <span>发送违法、违规等有害信息，会受到司法严惩。</span>
            <SafeIcon name="ChevronRight" :size="16" />
          </button>

          <div class="flow-footer">
            <label class="terms-row">
              <input v-model="termsAccepted" type="checkbox" />
              <span>
                同意
                <button type="button" @click.stop="handleOpenLegal('service')">《用户服务协议》</button>
                和
                <button type="button" @click.stop="handleOpenLegal('privacy')">《用户隐私政策》</button>
              </span>
            </label>
          </div>

          <div v-if="hasFiles" class="settings-card">
            <div class="settings-header">
              <div>
                <h2 class="text-item-title">分享设置</h2>
                <p class="text-caption mt-1">
                  {{ authStore.hasToken() ? '配置有效期、访问密码和下载限制' : '未登录文件有效期为 24 小时，到期后自动清理' }}
                </p>
              </div>
              <Button :disabled="!canGenerateLink" @click="handleGenerateLink">
                <SafeIcon v-if="isGenerating" name="Loader2" :size="16" class="animate-spin" />
                <SafeIcon v-else name="Link2" :size="16" />
                {{ generateButtonLabel }}
              </Button>
            </div>

            <div class="settings-grid">
              <div v-if="authStore.hasToken()" class="space-y-2">
                <Label class="text-label">有效期</Label>
                <Select v-model="shareSettings.expiresIn">
                  <SelectTrigger class="h-9">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="7d">7 天</SelectItem>
                    <SelectItem value="30d">30 天</SelectItem>
                    <SelectItem value="90d">90 天</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div v-else class="space-y-2">
                <Label class="text-label">有效期</Label>
                <div class="anonymous-expiry-box">
                  <SafeIcon name="Clock3" :size="16" />
                  24 小时
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-label">访问密码</Label>
                <Input v-model="shareSettings.accessPassword" class="h-9 font-mono" />
              </div>

              <div class="space-y-2">
                <Label class="text-label">最大下载次数</Label>
                <Input v-model.number="shareSettings.maxDownloads" type="number" min="1" class="h-9" />
              </div>
            </div>

            <div class="settings-switches">
              <div class="switch-row">
                <div>
                  <p class="text-label">在线预览</p>
                  <p class="text-caption mt-1">允许接收方预览文件</p>
                </div>
                <Switch v-model:checked="shareSettings.allowPreview" />
              </div>

              <div class="switch-row">
                <div>
                  <p class="text-label">下载通知</p>
                  <p class="text-caption mt-1">有人下载时提醒我</p>
                </div>
                <Switch v-model:checked="shareSettings.notifyOnDownload" />
              </div>
            </div>
          </div>
        </main>

        <aside class="utility-panel">
          <div class="panel-tabs" aria-label="辅助面板">
            <button
              v-for="tab in panelTabs"
              :key="tab.value"
              type="button"
              class="panel-tab"
              :class="{ 'is-active': activePanel === tab.value }"
              @click="handlePanelSelect(tab.value)"
            >
              <SafeIcon :name="tab.icon" :size="16" />
              <span>{{ tab.label }}</span>
            </button>
          </div>

          <div v-if="activePanel === 'pickup'" class="panel-section">
            <div class="panel-heading">
              <h2>取件码查询</h2>
              <p>输入取件码、分享 ID 或链接末尾口令。</p>
            </div>

            <form class="pickup-form" @submit.prevent="handlePickupSearch">
              <Label for="pickup-code" class="text-label">取件码</Label>
              <div class="pickup-input-row">
                <Input id="pickup-code" v-model="pickupCode" placeholder="例如 482916" />
                <Button type="submit">查询</Button>
              </div>
            </form>

            <div v-if="pickupResult" class="result-panel">
              <div>
                <strong>{{ pickupResult.title }}</strong>
                <span>{{ pickupResult.fileCount }} 个文件 · {{ formatShareSize(pickupResult.totalSizeMb) }}</span>
              </div>
              <Button size="sm" @click="handleOpenShare(pickupResult)">
                打开
              </Button>
            </div>

            <div v-else-if="pickupSearched" class="empty-panel">
              <SafeIcon name="SearchX" :size="22" />
              <span>没有找到匹配的分享记录</span>
            </div>

            <div class="hint-list">
              <div>
                <SafeIcon name="KeyRound" :size="16" />
                <span>取件码可使用分享密码</span>
              </div>
              <div>
                <SafeIcon name="Link2" :size="16" />
                <span>也支持输入链接最后一段口令</span>
              </div>
            </div>
          </div>

          <div v-else-if="activePanel === 'recent'" class="panel-section">
            <div class="panel-heading">
              <h2>最近发送</h2>
              <p>快速打开已生成的分享链接。</p>
            </div>

            <div class="recent-list">
              <button
                v-for="share in recentShares"
                :key="share.id"
                type="button"
                class="recent-item"
                @click="handleOpenShare(share)"
              >
                <span>
                  <strong>{{ share.title }}</strong>
                  <small>{{ formatShareDate(share.createdAt) }} · {{ share.fileCount }} 个文件</small>
                </span>
                <SafeIcon name="ArrowRight" :size="16" />
              </button>
            </div>

            <Button variant="outline" class="panel-link-button" @click="navigateTo('/delivery-records')">
              <SafeIcon name="History" :size="16" />
              全部交付记录
            </Button>
          </div>

          <div v-else-if="activePanel === 'support'" class="panel-section">
            <div class="panel-heading">
              <h2>客服支持</h2>
              <p>遇到无法上传、取件码失效或分享异常，可以联系人工协助。</p>
            </div>

            <div class="support-card">
              <SafeIcon name="Headphones" :size="22" />
              <div>
                <strong>400-821-1024</strong>
                <span>09:00-21:00 在线</span>
              </div>
            </div>

            <div class="support-actions">
              <Button @click="handleCopySupport">
                <SafeIcon name="Copy" :size="16" />
                复制客服信息
              </Button>
              <Button variant="outline" @click="handlePanelSelect('pickup')">
                帮我取件
              </Button>
            </div>
          </div>

          <div v-else class="panel-section">
            <div class="panel-heading">
              <h2>安全说明</h2>
              <p>默认启用访问密码、有效期和下载次数限制。</p>
            </div>

            <div class="security-list">
              <div>
                <SafeIcon name="ShieldCheck" :size="18" />
                <span>分享链接可设置有效期</span>
              </div>
              <div>
                <SafeIcon name="LockKeyhole" :size="18" />
                <span>访问密码默认自动生成</span>
              </div>
              <div>
                <SafeIcon name="BellRing" :size="18" />
                <span>下载通知可随时关闭</span>
              </div>
            </div>
          </div>
        </aside>
      </section>
    </div>

    <Dialog v-model:open="isLegalDialogOpen">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>{{ legalDialogTitle }}</DialogTitle>
          <DialogDescription>
            请阅读当前传输服务的关键说明。
          </DialogDescription>
        </DialogHeader>
        <div class="legal-copy">
          <p>以下内容为当前传输服务的关键说明。</p>
          <ul>
            <li v-for="point in legalDialogPoints" :key="point">{{ point }}</li>
          </ul>
        </div>
        <DialogFooter>
          <Button @click="legalDialog = null">知道了</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="isTextDialogOpen">
      <DialogContent class="max-w-xl">
        <DialogHeader>
          <DialogTitle>写文本发送</DialogTitle>
          <DialogDescription>
            文本会作为一个 .txt 文件加入本次发送。
          </DialogDescription>
        </DialogHeader>
        <div class="text-file-form">
          <div class="space-y-2">
            <Label class="text-label">文件名</Label>
            <Input v-model="textFileName" placeholder="例如 说明.txt" />
          </div>
          <div class="space-y-2">
            <Label class="text-label">文本内容</Label>
            <Textarea v-model="textFileContent" class="min-h-40" placeholder="输入要发送给对方的文字内容" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isTextDialogOpen = false">取消</Button>
          <Button @click="handleCreateTextFile">加入发送</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="isUploadProgressOpen">
      <DialogContent class="upload-progress-dialog max-w-2xl">
        <DialogHeader>
          <DialogTitle class="upload-progress-title">
            <SafeIcon :name="allFilesUploaded ? 'CheckCircle2' : 'Loader2'" :size="22" :class="!allFilesUploaded && 'animate-spin'" />
            {{ uploadDialogTitle }}
          </DialogTitle>
          <DialogDescription>
            {{ uploadDialogDescription }}
          </DialogDescription>
        </DialogHeader>
        <div class="upload-progress-body">
          <div class="upload-size-orb">
            <strong>{{ formatShareSize(totalSizeMb) }}</strong>
          </div>
          <div class="upload-progress-copy">
            <p>{{ allFilesUploaded ? '已完成 100%' : `即将完成...${uploadProgressPercent}%` }}</p>
            <Progress :model-value="uploadProgressPercent" class="h-2" />
          </div>
          <div class="upload-progress-list">
            <div v-for="file in uploadedFiles" :key="file.id" class="upload-progress-item">
              <SafeIcon :name="file.status === 'success' ? 'CheckCircle2' : file.status === 'failed' ? 'CircleAlert' : 'Loader2'" :size="16" :class="file.status === 'uploading' && 'animate-spin'" />
              <span>{{ file.fileName }}</span>
              <strong>{{ file.status === 'failed' ? '失败' : `${Math.round(file.progress)}%` }}</strong>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" :disabled="!allFilesUploaded" @click="isUploadProgressOpen = false">
            {{ allFilesUploaded ? '继续设置' : '上传中' }}
          </Button>
          <Button :disabled="!canGenerateLink" @click="handleGenerateLink">
            发送
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="isShareResultOpen">
      <DialogContent class="share-result-dialog max-w-5xl">
        <DialogHeader>
          <DialogTitle class="share-result-title">
            <span class="share-result-icon">
              <SafeIcon name="Check" :size="20" />
            </span>
            文件发送成功
          </DialogTitle>
          <DialogDescription>
            {{ shareResultSummary }}，已生成公共链接和访问密码。
          </DialogDescription>
        </DialogHeader>

        <div v-if="generatedShare" class="share-result-body">
          <section class="share-success-panel">
            <div class="success-hero-icon">
              <SafeIcon name="ThumbsUp" :size="58" />
            </div>
            <h2>{{ shareResultSummary }}，已全部发送成功</h2>
            <p>
              过期时间：<strong>{{ shareResultExpiresText }}</strong>
            </p>
            <div class="share-success-actions">
              <Button variant="outline" @click="handleCopyShareLink">
                <SafeIcon name="MessageCircle" :size="18" />
                社交分享
              </Button>
              <Button variant="secondary" @click="handleOpenPickupCode">
                <SafeIcon name="KeyRound" :size="18" />
                生成取件码
              </Button>
            </div>
            <div class="public-link-row">
              <span>公共链接：</span>
              <button type="button" @click="handleCopyShareLink">{{ shareResultLink }}</button>
              <Button variant="ghost" size="sm" @click="handleCopyShareLink">
                <SafeIcon name="Copy" :size="15" />
                复制
              </Button>
              <Button variant="ghost" size="sm" @click="loadShareQrcode">
                <SafeIcon name="QrCode" :size="15" />
                二维码
              </Button>
            </div>
          </section>

          <aside class="share-manage-panel">
            <div class="share-result-field">
              <Label class="text-label">链接地址</Label>
              <div class="share-copy-row">
                <Input :model-value="shareResultLink" readonly class="font-mono text-sm" />
                <Button variant="outline" @click="handleCopyShareLink">
                  <SafeIcon name="Copy" :size="16" />
                  复制
                </Button>
              </div>
            </div>

            <div class="share-result-field">
              <Label class="text-label">访问密码</Label>
              <div class="share-copy-row">
                <Input :model-value="shareResultPasswordDisplay" readonly class="font-mono text-sm" />
                <Button variant="outline" @click="handleCopySharePassword">
                  <SafeIcon name="Copy" :size="16" />
                  复制
                </Button>
              </div>
            </div>

            <div class="share-result-meta">
              <div>
                <span>有效期</span>
                <strong>{{ shareResultExpiresText }}</strong>
              </div>
              <div>
                <span>文件数量</span>
                <strong>{{ generatedShare.fileCount }} 个</strong>
              </div>
              <div>
                <span>总大小</span>
                <strong>{{ formatShareSize(generatedShare.totalSizeMb) }}</strong>
              </div>
            </div>
          </aside>

          <aside class="share-qrcode-panel">
            <div class="share-qrcode-box">
              <SafeIcon
                v-if="isShareQrcodeLoading"
                name="Loader2"
                :size="34"
                class="animate-spin text-muted-foreground"
              />
              <img
                v-else-if="shareQrcode"
                :src="shareQrcode"
                alt="分享二维码"
              />
              <button v-else type="button" @click="loadShareQrcode">
                <SafeIcon name="QrCode" :size="42" />
                <span>生成二维码</span>
              </button>
            </div>
            <div class="share-qrcode-actions">
              <Button variant="outline" size="sm" @click="handleRefreshShareQrcode">
                <SafeIcon name="RotateCw" :size="15" />
                刷新
              </Button>
              <Button variant="outline" size="sm" :disabled="!shareQrcode" @click="handleDownloadShareQrcode">
                <SafeIcon name="Download" :size="15" />
                下载
              </Button>
            </div>
          </aside>
        </div>

        <DialogFooter class="gap-2 sm:justify-between">
          <Button variant="ghost" size="icon" title="分享设置" @click="handleManageGeneratedShare">
            <SafeIcon name="Settings" :size="18" />
          </Button>
          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <Button variant="outline" @click="handleManageGeneratedShare">
              查看 / 管理文件
            </Button>
            <Button @click="handleCreateAnotherShare">
              <SafeIcon name="Send" :size="16" />
              发送新文件
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="isPickupCodeOpen">
      <DialogContent class="pickup-code-dialog max-w-lg">
        <DialogHeader>
          <DialogTitle>取件码</DialogTitle>
          <DialogDescription>
            对方也可以用访问密码作为取件码打开文件。
          </DialogDescription>
        </DialogHeader>
        <div class="pickup-code-body">
          <strong>{{ shareResultPasswordDisplay }}</strong>
          <p>有效期：{{ shareResultExpiresText }}</p>
          <Button variant="outline" @click="handleCopySharePassword">
            <SafeIcon name="Copy" :size="16" />
            复制取件码
          </Button>
        </div>
        <p class="pickup-code-note">
          取件码与访问密码一致，用于接收方打开公开链接后的密码验证。
        </p>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
.quick-send-page {
  position: relative;
  overflow-x: hidden;
  background: hsl(var(--background));
}

.quick-send-page::before {
  display: none;
}

.quick-send-container {
  position: relative;
  z-index: 1;
  min-height: calc(100vh - var(--header-height) - 48px);
}

.quick-workspace {
  display: grid;
  grid-template-columns: minmax(300px, 360px) minmax(640px, 1fr) minmax(340px, 420px);
  gap: 24px;
  align-items: start;
  width: 100%;
  padding: 32px 0;
}

.control-panel,
.transfer-card,
.settings-card,
.utility-panel {
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--card));
  box-shadow: var(--shadow-soft);
}

.control-panel,
.utility-panel {
  position: sticky;
  top: calc(var(--header-height) + 24px);
}

.control-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
}

.intro-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.intro-kicker,
.send-badge {
  display: inline-flex;
  width: fit-content;
  align-items: center;
  border-radius: 999px;
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
}

.intro-kicker {
  padding: 7px 10px;
}

.send-badge {
  padding: 6px 9px;
}

.intro-block h1 {
  color: hsl(var(--foreground));
  font-size: 30px;
  font-weight: 800;
  line-height: 1.18;
  letter-spacing: 0;
}

.intro-block p,
.panel-heading p,
.transfer-empty p,
.hint-list,
.legal-copy {
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  line-height: 1.6;
}

.mode-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.mode-option {
  display: flex;
  width: 100%;
  min-height: 76px;
  align-items: center;
  gap: 12px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--card));
  padding: 12px;
  text-align: left;
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
}

.mode-option:hover,
.mode-option.is-active {
  border-color: hsl(var(--primary) / 0.45);
  box-shadow: var(--shadow-soft);
}

.mode-option.is-active {
  transform: translateY(-1px);
}

.mode-icon {
  display: grid;
  width: 36px;
  height: 36px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 8px;
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.mode-copy {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 4px;
}

.mode-copy span {
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 700;
}

.mode-copy small {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
  line-height: 1.4;
}

.mode-check {
  color: hsl(var(--primary));
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.stat-tile {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 6px;
  border-radius: 8px;
  padding: 10px;
}

.stat-tile svg {
  flex: 0 0 auto;
}

.stat-tile span {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.stat-tile strong {
  overflow-wrap: anywhere;
  color: hsl(var(--foreground));
  font-size: 14px;
}

.tone-blue {
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.tone-green {
  background: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
}

.tone-amber {
  background: hsl(var(--warning) / 0.12);
  color: hsl(var(--warning));
}

.panel-link-button {
  width: 100%;
}

.transfer-column {
  min-width: 0;
}

.command-bar {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
  margin-bottom: 14px;
}

.command-pill,
.command-icon {
  background: hsl(var(--card));
  box-shadow: var(--shadow-soft);
}

.command-pill {
  border-color: hsl(var(--border));
  color: hsl(var(--foreground));
}

.command-icon {
  border: 1px solid hsl(var(--border));
  border-radius: 999px;
  color: hsl(var(--foreground));
}

.transfer-card {
  display: flex;
  min-height: 520px;
  flex-direction: column;
  padding: 24px;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}

.transfer-card.is-dragging {
  border-color: hsl(var(--primary) / 0.55);
  box-shadow: 0 14px 30px hsl(var(--primary) / 0.14);
  transform: translateY(-2px);
}

.transfer-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.transfer-card-header h2 {
  margin-top: 10px;
  color: hsl(var(--foreground));
  font-size: 22px;
  font-weight: 800;
  line-height: 1.25;
  letter-spacing: 0;
}

.transfer-empty {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
  padding: 42px 16px 58px;
  text-align: center;
}

.upload-symbol {
  display: grid;
  width: 96px;
  height: 96px;
  place-items: center;
  border: 1px dashed hsl(var(--primary) / 0.42);
  border-radius: 8px;
  background: hsl(var(--primary) / 0.06);
  color: hsl(var(--primary));
}

.choose-file-button {
  min-width: 152px;
}

.source-menu {
  padding: 8px;
}

.source-menu-item {
  display: flex;
  min-height: 58px;
  align-items: center;
  gap: 12px;
  border-radius: 8px;
}

.source-menu-item span {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 3px;
}

.source-menu-item strong {
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 700;
}

.source-menu-item small {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.format-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}

.format-row span {
  border: 1px solid hsl(var(--border));
  border-radius: 999px;
  background: hsl(var(--muted) / 0.45);
  padding: 6px 10px;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.transfer-filled {
  display: flex;
  min-height: 0;
  flex: 1;
  flex-direction: column;
  gap: 16px;
  padding-top: 22px;
}

.selected-summary {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.selected-actions {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
}

.generate-link-button {
  min-width: 142px;
}

.file-list-scroll {
  display: flex;
  max-height: 360px;
  flex-direction: column;
  gap: 8px;
  overflow-y: auto;
  padding-right: 2px;
}

.notice-bar {
  display: flex;
  width: calc(100% - 36px);
  min-height: 44px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 0 auto;
  border: 1px solid hsl(var(--warning) / 0.22);
  border-top: 0;
  border-radius: 0 0 8px 8px;
  background: hsl(var(--warning) / 0.1);
  color: hsl(32 72% 28%);
  font-size: 14px;
  line-height: 1.35;
  text-align: center;
}

.flow-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  margin-top: 16px;
}

.terms-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  line-height: 1.6;
  text-align: center;
}

.terms-row input {
  width: 16px;
  height: 16px;
  accent-color: hsl(var(--primary));
}

.terms-row button {
  color: hsl(var(--foreground));
  font-weight: 600;
}

.settings-card {
  margin-top: 22px;
  padding: 20px;
}

.settings-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 18px;
}

.anonymous-expiry-box {
  display: flex;
  height: 36px;
  align-items: center;
  gap: 8px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--muted) / 0.35);
  padding: 0 12px;
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 600;
}

.settings-switches {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-top: 18px;
}

.switch-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.utility-panel {
  display: flex;
  min-height: 520px;
  flex-direction: column;
  gap: 18px;
  padding: 18px;
}

.panel-tabs {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 6px;
  border-radius: 8px;
  background: hsl(var(--muted) / 0.65);
  padding: 5px;
}

.panel-tab {
  display: flex;
  min-width: 0;
  min-height: 44px;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 6px;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
  font-weight: 600;
  transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.panel-tab.is-active {
  background: white;
  color: hsl(var(--primary));
  box-shadow: var(--shadow-soft);
}

.panel-section {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 18px;
}

.panel-heading h2 {
  color: hsl(var(--foreground));
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0;
}

.panel-heading p {
  margin-top: 6px;
}

.pickup-form {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pickup-input-row {
  display: flex;
  gap: 8px;
}

.pickup-input-row input {
  min-width: 0;
}

.result-panel,
.empty-panel,
.support-card {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--muted) / 0.35);
  padding: 12px;
}

.result-panel {
  justify-content: space-between;
}

.result-panel div,
.support-card div {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 4px;
}

.result-panel strong,
.support-card strong {
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 14px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.result-panel span,
.support-card span {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.empty-panel {
  justify-content: center;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.hint-list,
.security-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.hint-list div,
.security-list div {
  display: flex;
  align-items: center;
  gap: 8px;
}

.hint-list svg,
.security-list svg {
  color: hsl(var(--primary));
}

.recent-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.recent-item {
  display: flex;
  width: 100%;
  min-height: 70px;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: white;
  padding: 12px;
  text-align: left;
  transition: border-color 0.18s, transform 0.18s;
}

.recent-item:hover {
  border-color: hsl(var(--primary) / 0.45);
  transform: translateY(-1px);
}

.recent-item span {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 5px;
}

.recent-item strong {
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 14px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recent-item small {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.support-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.legal-copy {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.legal-copy ul {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-left: 18px;
}

.legal-copy li {
  list-style: disc;
}

.text-file-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.upload-progress-dialog {
  gap: 22px;
}

.upload-progress-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.upload-progress-title svg {
  color: hsl(var(--primary));
}

.upload-progress-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 22px;
  padding: 10px 0 18px;
}

.upload-size-orb {
  display: grid;
  width: 128px;
  height: 128px;
  place-items: center;
  border: 10px solid hsl(var(--primary) / 0.15);
  border-radius: 999px;
  background: hsl(var(--primary) / 0.72);
  color: white;
}

.upload-size-orb strong {
  font-size: 20px;
  font-weight: 800;
}

.upload-progress-copy {
  display: flex;
  width: min(100%, 420px);
  flex-direction: column;
  gap: 12px;
  text-align: center;
}

.upload-progress-copy p {
  color: hsl(var(--foreground));
  font-size: 22px;
  font-weight: 700;
}

.upload-progress-list {
  display: flex;
  width: min(100%, 520px);
  max-height: 180px;
  flex-direction: column;
  overflow: auto;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
}

.upload-progress-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid hsl(var(--border));
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.upload-progress-item:last-child {
  border-bottom: 0;
}

.upload-progress-item span {
  overflow: hidden;
  color: hsl(var(--foreground));
  text-overflow: ellipsis;
  white-space: nowrap;
}

.upload-progress-item strong {
  font-size: 12px;
}

.share-result-dialog {
  gap: 20px;
}

.share-result-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.share-result-icon {
  display: inline-grid;
  width: 32px;
  height: 32px;
  place-items: center;
  border-radius: 999px;
  background: hsl(var(--success));
  color: white;
}

.share-result-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
  gap: 20px;
  align-items: stretch;
}

.share-success-panel,
.share-manage-panel,
.share-qrcode-panel {
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--card));
}

.share-success-panel {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
  padding: 42px 24px;
  text-align: center;
}

.success-hero-icon {
  display: grid;
  width: 110px;
  height: 110px;
  place-items: center;
  border-radius: 999px;
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.share-success-panel h2 {
  color: hsl(var(--primary));
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 0;
}

.share-success-panel p {
  color: hsl(var(--muted-foreground));
  font-size: 15px;
}

.share-success-panel p strong {
  color: hsl(var(--warning));
}

.share-success-actions {
  display: grid;
  width: min(100%, 520px);
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.public-link-row {
  display: flex;
  width: min(100%, 720px);
  min-width: 0;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.public-link-row button:first-of-type {
  min-width: 0;
  max-width: 360px;
  overflow: hidden;
  color: hsl(var(--primary));
  text-overflow: ellipsis;
  white-space: nowrap;
}

.share-manage-panel {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 16px;
  padding: 18px;
}

.share-result-field {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 8px;
}

.share-copy-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 8px;
}

.share-copy-row input {
  min-width: 0;
}

.share-result-meta {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.share-result-meta div {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 6px;
  border-radius: 8px;
  background: hsl(var(--muted) / 0.45);
  padding: 12px;
}

.share-result-meta span {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.share-result-meta strong {
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 15px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.share-qrcode-panel {
  grid-column: 2;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px;
}

.share-qrcode-box {
  display: grid;
  aspect-ratio: 1;
  width: 100%;
  place-items: center;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--muted) / 0.3);
  overflow: hidden;
}

.share-qrcode-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 12px;
  background: white;
}

.share-qrcode-box button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.share-qrcode-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.pickup-code-dialog {
  gap: 22px;
}

.pickup-code-body {
  display: flex;
  min-height: 220px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.pickup-code-body strong {
  color: hsl(var(--primary));
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 56px;
  font-weight: 800;
  letter-spacing: 0.18em;
}

.pickup-code-body p,
.pickup-code-note {
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  line-height: 1.7;
  text-align: center;
}

@media (max-width: 1400px) {
  .quick-workspace {
    grid-template-columns: minmax(260px, 320px) minmax(520px, 1fr);
  }

  .utility-panel {
    grid-column: 1 / -1;
    min-height: auto;
  }

  .control-panel,
  .utility-panel {
    position: static;
  }

  .panel-section {
    min-height: 220px;
  }
}

@media (max-width: 1180px) {
  .quick-workspace {
    grid-template-columns: minmax(230px, 280px) minmax(480px, 1fr);
  }
}

@media (max-width: 820px) {
  .quick-workspace {
    grid-template-columns: 1fr;
    padding: 16px 0 28px;
  }

  .transfer-column {
    order: 1;
  }

  .control-panel {
    order: 2;
  }

  .utility-panel {
    order: 3;
  }

  .control-panel {
    gap: 16px;
  }

  .intro-block h1 {
    font-size: 26px;
  }

  .command-bar {
    justify-content: flex-start;
  }

  .transfer-card {
    min-height: 430px;
    padding: 18px;
  }

  .transfer-card-header,
  .settings-header,
  .selected-summary {
    flex-direction: column;
  }

  .settings-grid,
  .settings-switches {
    grid-template-columns: 1fr;
  }

  .share-result-body {
    grid-template-columns: 1fr;
  }

  .share-qrcode-panel {
    grid-column: auto;
    max-width: 260px;
  }
}

@media (max-width: 520px) {
  .quick-send-page {
    background-size: 24px 24px, 24px 24px, auto;
  }

  .stat-grid,
  .panel-tabs {
    grid-template-columns: 1fr;
  }

  .mode-option {
    min-height: 70px;
  }

  .command-pill {
    width: 100%;
  }

  .notice-bar {
    width: calc(100% - 20px);
    padding: 10px 12px;
  }

  .terms-row {
    align-items: flex-start;
  }

  .pickup-input-row,
  .selected-actions {
    flex-direction: column;
    width: 100%;
  }

  .selected-actions > * {
    width: 100%;
  }

  .share-copy-row,
  .share-result-meta {
    grid-template-columns: 1fr;
  }

  .share-qrcode-panel {
    max-width: none;
  }
}
</style>
