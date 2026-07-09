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
import SafeIcon from '@/components/common/SafeIcon.vue'
import FileListItem from '@/components/common/FileListItem.vue'
import { FileShareService } from '@/data/FileShareService'
import {
  FileTransferApi,
  makeShareExpiresAt,
  normalizeShareData,
} from '@/data/FileTransferApi'
import { getApiErrorMessage } from '@/lib/apiClient'
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
const LOCAL_FALLBACK_ENABLED =
  import.meta.env.DEV ||
  import.meta.env.PUBLIC_ENABLE_MOCK === '1' ||
  import.meta.env.PUBLIC_ENABLE_MOCK === 'true'

const fileInput = ref<HTMLInputElement | null>(null)
const uploadedFiles = ref<UploadFile[]>([])
const isDragging = ref(false)
const isGenerating = ref(false)
const termsAccepted = ref(true)
const sendMode = ref<SendMode>('standard')
const activePanel = ref<UtilityPanel>('pickup')
const pickupCode = ref('')
const pickupResult = ref<ShareRecord | null>(null)
const pickupSearched = ref(false)
const isLoggedIn = ref(false)
const legalDialog = ref<LegalDialogType | null>(null)

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

  ;[...persistedShares, ...FileShareService.getAll()].forEach((share) => {
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

    if (LOCAL_FALLBACK_ENABLED) {
      latest.status = 'success'
      latest.progress = 100
      latest.backendFileId = undefined
      toast.success(`文件 "${latest.fileName}" 已加入本地发送`)
      return
    }

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

const handleOpenShare = (shareId: string) => {
  navigateTo(`/share-result?shareId=${shareId}`)
}

const handleLogin = () => {
  if (isLoggedIn.value) {
    toast.success('当前已登录')
    return
  }
  isLoggedIn.value = true
  toast.success('已模拟登录，可在线预览')
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
      password: shareSettings.value.accessPassword,
      expiresAt: makeShareExpiresAt(shareSettings.value.expiresIn),
      maxDownloads: shareSettings.value.maxDownloads,
      allowPreview: shareSettings.value.allowPreview,
      notifyOnDownload: shareSettings.value.notifyOnDownload,
    })

    const allShares = FileShareService.getAll()
    allShares.push(normalizeShareData(share, shareSettings.value.accessPassword))
    FileShareService.savePersisted(allShares)

    toast.success('分享链接已生成')
    navigateTo(`/share-result?shareCode=${encodeURIComponent(share.shareCode)}&shareId=${encodeURIComponent(share.id)}`)
  } catch (error) {
    if (!LOCAL_FALLBACK_ENABLED) {
      toast.error(getApiErrorMessage(error, '生成分享链接失败，请重试'))
      return
    }

    const localShare = createLocalShareRecord()
    const allShares = FileShareService.getAll()
    allShares.push(localShare)
    FileShareService.savePersisted(allShares)
    toast.success('分享链接已生成')
    navigateTo(`/share-result?shareId=${localShare.id}`)
  } finally {
    isGenerating.value = false
  }
}

function createLocalShareRecord(): ShareRecord {
  const shareId = `share-${Date.now()}`
  return {
    id: shareId,
    taskId: '',
    title: `快速分享 - ${new Date().toLocaleString('zh-CN')}`,
    shareUrl: `https://share.zxtransfer.example/s/${Math.random().toString(36).substring(2, 8)}`,
    password: shareSettings.value.accessPassword,
    expiresAt: makeShareExpiresAt(shareSettings.value.expiresIn),
    maxDownloads: shareSettings.value.maxDownloads,
    allowPreview: shareSettings.value.allowPreview,
    notifyOnDownload: shareSettings.value.notifyOnDownload,
    status: 'active',
    fileCount: uploadedFiles.value.length,
    totalSizeMb: totalSizeMb.value,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
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
})
</script>

<template>
  <div class="page-body quick-send-page min-h-[calc(100vh-var(--header-height))]">
    <div class="page-container quick-send-container">
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
              <Button class="choose-file-button" @click="openFilePicker">
                <SafeIcon name="FilePlus2" :size="18" />
                {{ pickerLabel }}
              </Button>
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
                  <Button variant="outline" size="sm" @click="openFilePicker">
                    继续添加
                  </Button>
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
            <p class="login-row">
              <template v-if="isLoggedIn">
                已登录，接收方可按权限在线预览。
              </template>
              <template v-else>
                未登录，对方无法在线预览。
                <button type="button" @click="handleLogin">立即登录</button>
              </template>
            </p>
          </div>

          <div v-if="hasFiles" class="settings-card">
            <div class="settings-header">
              <div>
                <h2 class="text-item-title">分享设置</h2>
                <p class="text-caption mt-1">配置有效期、访问密码和下载限制</p>
              </div>
              <Button :disabled="!canGenerateLink" @click="handleGenerateLink">
                <SafeIcon v-if="isGenerating" name="Loader2" :size="16" class="animate-spin" />
                {{ isGenerating ? '生成中...' : '生成分享链接' }}
              </Button>
            </div>

            <div class="settings-grid">
              <div class="space-y-2">
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
              <Button size="sm" @click="handleOpenShare(pickupResult.id)">
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
                @click="handleOpenShare(share.id)"
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
          <p>以下内容用于演示当前产品交互，实际条款可接入正式协议文本。</p>
          <ul>
            <li v-for="point in legalDialogPoints" :key="point">{{ point }}</li>
          </ul>
        </div>
        <DialogFooter>
          <Button @click="legalDialog = null">知道了</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
.quick-send-page {
  position: relative;
  overflow-x: hidden;
  background:
    linear-gradient(90deg, rgb(15 23 42 / 0.035) 1px, transparent 1px),
    linear-gradient(180deg, rgb(15 23 42 / 0.035) 1px, transparent 1px),
    linear-gradient(135deg, #f5f8fb 0%, #eef5f1 48%, #f7f4ed 100%);
  background-size: 32px 32px, 32px 32px, auto;
}

.quick-send-page::before {
  position: absolute;
  inset: 0;
  content: '';
  pointer-events: none;
  background:
    linear-gradient(116deg, transparent 0 58%, rgb(39 98 118 / 0.08) 58% 58.35%, transparent 58.35%),
    linear-gradient(116deg, transparent 0 72%, rgb(177 111 54 / 0.09) 72% 72.3%, transparent 72.3%);
}

.quick-send-container {
  position: relative;
  z-index: 1;
  min-height: calc(100vh - var(--header-height) - 48px);
}

.quick-workspace {
  display: grid;
  grid-template-columns: minmax(220px, 280px) minmax(420px, 1fr) minmax(280px, 360px);
  gap: 20px;
  align-items: start;
  width: 100%;
  padding: 32px 0;
}

.control-panel,
.transfer-card,
.settings-card,
.utility-panel {
  border: 1px solid rgb(15 23 42 / 0.1);
  border-radius: 8px;
  background: rgb(255 255 255 / 0.92);
  box-shadow: 0 18px 42px rgb(15 23 42 / 0.09);
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
  background: rgb(43 118 105 / 0.1);
  color: #20685c;
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
  color: #172033;
  font-size: 30px;
  font-weight: 800;
  line-height: 1.18;
  letter-spacing: 0;
}

.intro-block p,
.panel-heading p,
.transfer-empty p,
.hint-list,
.legal-copy,
.login-row {
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
  background: #fff;
  padding: 12px;
  text-align: left;
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
}

.mode-option:hover,
.mode-option.is-active {
  border-color: rgb(43 118 105 / 0.45);
  box-shadow: 0 10px 22px rgb(15 23 42 / 0.08);
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
  background: rgb(43 118 105 / 0.1);
  color: #20685c;
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
  color: #20685c;
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
  background: rgb(57 105 164 / 0.1);
  color: #3969a4;
}

.tone-green {
  background: rgb(43 118 105 / 0.1);
  color: #20685c;
}

.tone-amber {
  background: rgb(177 111 54 / 0.12);
  color: #9b5f2c;
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
  background: rgb(255 255 255 / 0.9);
  box-shadow: 0 8px 22px rgb(15 23 42 / 0.08);
}

.command-pill {
  border-color: rgb(15 23 42 / 0.1);
  color: #344054;
}

.command-icon {
  border: 1px solid rgb(15 23 42 / 0.08);
  border-radius: 999px;
  color: #344054;
}

.transfer-card {
  display: flex;
  min-height: 520px;
  flex-direction: column;
  padding: 24px;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}

.transfer-card.is-dragging {
  border-color: rgb(43 118 105 / 0.55);
  box-shadow: 0 22px 46px rgb(43 118 105 / 0.18);
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
  color: #172033;
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
  border: 1px dashed rgb(43 118 105 / 0.42);
  border-radius: 8px;
  background:
    linear-gradient(135deg, rgb(43 118 105 / 0.12), rgb(57 105 164 / 0.08)),
    #fff;
  color: #20685c;
}

.choose-file-button {
  min-width: 152px;
}

.format-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}

.format-row span {
  border: 1px solid rgb(15 23 42 / 0.08);
  border-radius: 999px;
  background: rgb(248 250 252 / 0.9);
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
  gap: 8px;
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
  border: 0;
  border-radius: 0 0 8px 8px;
  background: #b16f36;
  color: white;
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
  accent-color: #20685c;
}

.terms-row button,
.login-row button {
  color: #172033;
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
  color: #20685c;
  box-shadow: 0 4px 12px rgb(15 23 42 / 0.08);
}

.panel-section {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 18px;
}

.panel-heading h2 {
  color: #172033;
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
  color: #20685c;
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
  border-color: rgb(43 118 105 / 0.45);
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

@media (max-width: 1180px) {
  .quick-workspace {
    grid-template-columns: minmax(220px, 280px) minmax(420px, 1fr);
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
}
</style>
