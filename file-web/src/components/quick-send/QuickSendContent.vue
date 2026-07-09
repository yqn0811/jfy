
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
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
import FileUploadZone from '@/components/common/FileUploadZone.vue'
import FileListItem from '@/components/common/FileListItem.vue'
import { FileShareService } from '@/data/FileShareService'
import { cn } from '@/lib/utils'

interface UploadFile {
  id: string
  file: File
  fileName: string
  fileSizeMb: number
  progress: number
  status: 'pending' | 'uploading' | 'success' | 'failed'
  failReason?: string
  retryCount: number
}

interface ShareSettings {
  expiresIn: '7d' | '30d' | '90d'
  accessPassword: string
  maxDownloads: number
  allowPreview: boolean
  notifyOnDownload: boolean
}

const isClient = ref(true)
const uploadedFiles = ref<UploadFile[]>([])
const isGenerating = ref(false)
const generatedShare = ref<any>(null)

const shareSettings = ref<ShareSettings>({
  expiresIn: '30d',
  accessPassword: generatePassword(),
  maxDownloads: 20,
  allowPreview: true,
  notifyOnDownload: true,
})

function generatePassword(): string {
  return Math.floor(100000 + Math.random() * 900000).toString()
}

const totalSizeMb = computed(() => {
  return uploadedFiles.value.reduce((sum, f) => sum + f.fileSizeMb, 0)
})

const allFilesUploaded = computed(() => {
  return uploadedFiles.value.length > 0 && uploadedFiles.value.every(f => f.status === 'success')
})

const canGenerateLink = computed(() => {
  return allFilesUploaded.value && !isGenerating.value
})

const handleFilesSelected = (files: FileList) => {
  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const id = `upload-${Date.now()}-${i}`
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

    simulateUpload(id)
  }
}

function simulateUpload(fileId: string) {
  const fileItem = uploadedFiles.value.find(f => f.id === fileId)
  if (!fileItem) return

  fileItem.status = 'uploading'
  fileItem.progress = 0

  const interval = setInterval(() => {
    if (fileItem.progress < 100) {
      fileItem.progress += Math.random() * 30
      if (fileItem.progress > 100) fileItem.progress = 100
    } else {
      clearInterval(interval)
      const shouldFail = Math.random() < 0.1
      if (shouldFail) {
        fileItem.status = 'failed'
        fileItem.failReason = '网络中断，请重试'
        toast.error(`文件 "${fileItem.fileName}" 上传失败`)
      } else {
        fileItem.status = 'success'
        fileItem.progress = 100
        toast.success(`文件 "${fileItem.fileName}" 上传成功`)
      }
    }
  }, 300)
}

const handleRetryUpload = (fileId: string) => {
  const fileItem = uploadedFiles.value.find(f => f.id === fileId)
  if (fileItem) {
    fileItem.retryCount += 1
    fileItem.failReason = undefined
    simulateUpload(fileId)
  }
}

const handleRemoveFile = (fileId: string) => {
  uploadedFiles.value = uploadedFiles.value.filter(f => f.id !== fileId)
}

const handleGenerateLink = async () => {
  if (!canGenerateLink.value) {
    toast.error('请先上传文件')
    return
  }

  isGenerating.value = true

  setTimeout(() => {
    const shareId = `share-${Date.now()}`
    const newShare = {
      id: shareId,
      taskId: '',
      title: `快速分享 - ${new Date().toLocaleString('zh-CN')}`,
      shareUrl: `https://share.zxtransfer.example/s/${Math.random().toString(36).substring(2, 8)}`,
      password: shareSettings.value.accessPassword,
      expiresAt: new Date(Date.now() + (shareSettings.value.expiresIn === '7d' ? 7 : shareSettings.value.expiresIn === '30d' ? 30 : 90) * 24 * 60 * 60 * 1000).toISOString(),
      maxDownloads: shareSettings.value.maxDownloads,
      allowPreview: shareSettings.value.allowPreview,
      notifyOnDownload: shareSettings.value.notifyOnDownload,
      status: 'active' as const,
      fileCount: uploadedFiles.value.length,
      totalSizeMb,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }

    const allShares = FileShareService.getAll()
    allShares.push(newShare)
    FileShareService.savePersisted(allShares)

    isGenerating.value = false
    generatedShare.value = newShare
    toast.success('分享链接已生成')
  }, 1500)
}

const handleBackToWorkbench = () => {
  window.location.href = './workbench.html'
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })
})
</script>

<template>
  <div class="page-body min-h-screen bg-background">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-2">
        <h1 class="text-page-title">快速发文件</h1>
        <Button
          variant="ghost"
          size="sm"
          class="text-muted-foreground hover:text-foreground"
          @click="handleBackToWorkbench"
        >
          <SafeIcon name="X" :size="20" />
        </Button>
      </div>
      <p class="text-caption">上传文件、配置分享权限，一键生成分享链接</p>
    </div>

    <!-- Upload Zone Section -->
    <FileUploadZone
      accept="*"
      :max-size="500"
      :multiple="true"
      class="mb-8"
      @files-selected="handleFilesSelected"
    />

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left: Upload Section (2 cols on desktop) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- File List -->
        <div v-if="uploadedFiles.length > 0">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-item-title">已选择文件</h3>
              <p class="text-caption">
                {{ uploadedFiles.length }} 个文件，共 {{ totalSizeMb.toFixed(2) }} MB
              </p>
            </div>
            <Button
              v-if="uploadedFiles.length > 0"
              variant="ghost"
              size="sm"
              class="text-destructive hover:text-destructive hover:bg-destructive/10"
              @click="uploadedFiles = []"
            >
              清空列表
            </Button>
          </div>

          <div class="space-y-2">
            <FileListItem
              v-for="file in uploadedFiles"
              :key="file.id"
              :file-name="file.fileName"
              :file-size="file.fileSizeMb * 1024 * 1024"
              :status="file.status"
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

      <!-- Right: Empty Placeholder -->
      <div class="lg:col-span-1"></div>
    </div>
  </div>
</template>

<style scoped>
/* Sticky positioning for share settings panel on desktop */
@media (min-width: 1024px) {
  .sticky {
    top: calc(var(--header-height) + 2rem);
  }
}
</style>
