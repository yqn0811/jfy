<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
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
import SafeIcon from '@/components/common/SafeIcon.vue'
import { CollectionTaskService } from '@/data/CollectionTaskService'
import type {
  CollectionTaskData,
  TaskFieldConfigData,
  TaskMaterialItemData,
  TaskRuleConfigData,
} from '@/data/CollectionTaskData'
import { FileTransferApi } from '@/data/FileTransferApi'
import { getApiErrorMessage } from '@/lib/apiClient'

const DEFAULT_DUE_DAYS = 3
const CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

interface CreatedCollectionResult {
  task: CollectionTaskData
  accessCode: string
}

const isClient = ref(false)
const isCreating = ref(false)
const isSettingsOpen = ref(false)
const isPickupDialogOpen = ref(false)
const isQrcodeDialogOpen = ref(false)
const isQrcodeLoading = ref(false)
const qrcodeImage = ref('')
const qrcodeUrl = ref('')
const now = ref(Date.now())
let countdownTimer: number | null = null

const form = reactive({
  recipientEmail: '',
  senderEmail: '',
  message: '',
})

const settings = reactive({
  dueDays: DEFAULT_DUE_DAYS,
  materialName: '文件',
  maxSizeMb: 100,
  enableAccessCode: true,
  accessCode: '',
  allowFolderUpload: true,
  autoCreateFolder: false,
})

const createdResult = ref<CreatedCollectionResult | null>(null)

const collectLink = computed(() => {
  const taskId = createdResult.value?.task.id
  if (!taskId) return ''
  if (typeof window === 'undefined') return `/submission-upload?taskId=${encodeURIComponent(taskId)}`
  return `${window.location.origin}/submission-upload?taskId=${encodeURIComponent(taskId)}`
})

const parseDateTime = (value: string) => {
  const normalized = value
    .replace(' ', 'T')
    .replace(/([+-]\d{2})$/, '$1:00')
    .replace(/([+-]\d{2})(\d{2})$/, '$1:$2')
  return new Date(normalized).getTime()
}

const countdownText = computed(() => {
  const dueAt = createdResult.value?.task.dueAt
  if (!dueAt) return ''
  const dueTime = parseDateTime(dueAt)
  if (!Number.isFinite(dueTime)) return ''
  const remaining = Math.max(0, dueTime - now.value)
  const totalSeconds = Math.floor(remaining / 1000)
  const days = Math.floor(totalSeconds / 86400)
  const hours = Math.floor((totalSeconds % 86400) / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  if (days > 0) return `${days} 天，${padTime(hours)}:${padTime(minutes)}:${padTime(seconds)}`
  return `${padTime(hours)}:${padTime(minutes)}:${padTime(seconds)}`
})

const normalizedMaterialName = computed(() => settings.materialName.trim() || '文件')

const padTime = (value: number) => String(value).padStart(2, '0')

const makeAccessCode = () => {
  const bytes = new Uint8Array(4)
  if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
    crypto.getRandomValues(bytes)
  } else {
    for (let index = 0; index < bytes.length; index++) {
      bytes[index] = Math.floor(Math.random() * CODE_ALPHABET.length)
    }
  }
  return Array.from(bytes, (byte) => CODE_ALPHABET[byte % CODE_ALPHABET.length]).join('')
}

const normalizeEmail = (value: string) => value.trim()

const isValidEmail = (value: string) => {
  const email = normalizeEmail(value)
  return !email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const buildTaskName = () => {
  const recipient = normalizeEmail(form.recipientEmail)
  if (recipient) return `向 ${recipient} 收集文件`
  return `收集文件 ${new Date().toLocaleString('zh-CN', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })}`
}

const buildDescription = () => {
  const message = form.message.trim()
  return message || '请按页面要求上传文件。'
}

const buildTargetDescription = () => {
  const parts = [
    normalizeEmail(form.recipientEmail) ? `对方邮箱：${normalizeEmail(form.recipientEmail)}` : '',
    normalizeEmail(form.senderEmail) ? `我的邮箱：${normalizeEmail(form.senderEmail)}` : '',
  ].filter(Boolean)
  return parts.join('\n')
}

const buildDueAt = () => {
  const dueDays = Number.isFinite(Number(settings.dueDays)) ? Math.max(1, Math.min(30, Number(settings.dueDays))) : DEFAULT_DUE_DAYS
  return new Date(Date.now() + dueDays * 24 * 60 * 60 * 1000).toISOString()
}

const buildFields = (): TaskFieldConfigData[] => [
  {
    id: 'collection-email-field',
    fieldKey: 'submitter_email',
    fieldLabel: '您的邮箱地址',
    fieldType: 'email',
    required: false,
    placeholder: '可不填',
    order: 0,
  },
]

const buildMaterials = (): TaskMaterialItemData[] => [
  {
    id: 'collection-file-material',
    materialName: normalizedMaterialName.value,
    fileTypes: [],
    required: true,
    maxSizeMb: Math.max(1, Math.min(2048, Number(settings.maxSizeMb) || 100)),
    order: 0,
  },
]

const buildRuleConfig = (): Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'> => ({
  namingRule: '原文件名',
  allowResubmission: true,
  enableAICheck: false,
  anonymousSubmit: true,
  allowPreview: false,
  reminderBeforeDueHours: 24,
})

const persistCreatedTask = (task: CollectionTaskData) => {
  const allTasks = CollectionTaskService.getAll()
  const existingIndex = allTasks.findIndex((item) => item.id === task.id)
  if (existingIndex >= 0) {
    allTasks[existingIndex] = task
  } else {
    allTasks.push(task)
  }
  CollectionTaskService.savePersisted(allTasks)
}

const copyText = async (text: string, successMessage: string) => {
  if (!text) {
    toast.info('暂无可复制内容')
    return
  }
  try {
    await navigator.clipboard.writeText(text)
    toast.success(successMessage)
  } catch {
    toast.error('复制失败，请手动复制')
  }
}

const handleCreateTask = async () => {
  if (!isValidEmail(form.recipientEmail) || !isValidEmail(form.senderEmail)) {
    toast.error('请填写正确的邮箱地址')
    return
  }

  isCreating.value = true
  try {
    const accessCode = settings.enableAccessCode ? (settings.accessCode.trim() || makeAccessCode()) : ''
    settings.accessCode = accessCode
    const created = await FileTransferApi.createCollectionTask({
      templateId: null,
      name: buildTaskName(),
      description: buildDescription(),
      dueAt: buildDueAt(),
      submitTargetDescription: buildTargetDescription(),
      accessCode,
      fields: buildFields(),
      materials: buildMaterials(),
      ruleConfig: buildRuleConfig(),
    })

    persistCreatedTask(created.task)
    createdResult.value = {
      task: created.task,
      accessCode,
    }
    qrcodeImage.value = ''
    qrcodeUrl.value = ''
    toast.success('已完成您的指示')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '创建收集任务失败，请重试'))
  } finally {
    isCreating.value = false
  }
}

const handleContinueCollect = () => {
  form.recipientEmail = ''
  form.senderEmail = ''
  form.message = ''
  settings.accessCode = settings.enableAccessCode ? makeAccessCode() : ''
  createdResult.value = null
  qrcodeImage.value = ''
  qrcodeUrl.value = ''
}

const handleOpenPickupCode = () => {
  if (!createdResult.value?.accessCode) {
    toast.info('当前收集任务未启用取件码')
    return
  }
  isPickupDialogOpen.value = true
}

const handleRegenerateAccessCode = () => {
  if (!createdResult.value) return
  toast.info('取件码已随任务创建生成，重新生成需要重新创建收集任务')
}

const handleShowQRCode = async () => {
  qrcodeUrl.value = collectLink.value
  isQrcodeDialogOpen.value = true
  if (qrcodeImage.value || !createdResult.value?.task.id) return

  try {
    isQrcodeLoading.value = true
    const result = await FileTransferApi.getCollectionTaskQrcode(createdResult.value.task.id, collectLink.value)
    qrcodeImage.value = result.qrcode
    qrcodeUrl.value = result.url || collectLink.value
  } catch (error) {
    toast.error(getApiErrorMessage(error, '二维码生成失败，已保留收集链接'))
  } finally {
    isQrcodeLoading.value = false
  }
}

const handleSocialShare = () => {
  copyText(collectLink.value, '公共链接已复制，请粘贴到 QQ 或微信')
}

onMounted(() => {
  isClient.value = true
  if (!settings.accessCode) {
    settings.accessCode = makeAccessCode()
  }
  countdownTimer = window.setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onBeforeUnmount(() => {
  if (countdownTimer !== null) {
    window.clearInterval(countdownTimer)
    countdownTimer = null
  }
})
</script>

<template>
  <div v-if="isClient" class="collection-experience">
    <section v-if="!createdResult" class="collection-card collection-form-card">
      <div class="line-field">
        <input
          v-model="form.recipientEmail"
          type="email"
          autocomplete="email"
          placeholder="对方的邮箱地址（可不填）"
        />
      </div>

      <div class="line-field">
        <input
          v-model="form.senderEmail"
          type="email"
          autocomplete="email"
          placeholder="您的邮箱地址（可不填）"
        />
      </div>

      <div class="line-field">
        <input
          v-model="form.message"
          maxlength="120"
          placeholder="留个言（可不填）"
        />
      </div>

      <div class="collection-actions">
        <Button
          type="button"
          variant="outline"
          class="settings-button"
          aria-label="收集设置"
          title="收集设置"
          @click="isSettingsOpen = true"
        >
          <SafeIcon name="Settings" :size="26" />
        </Button>
        <Button
          type="button"
          class="primary-collect-button"
          :disabled="isCreating"
          @click="handleCreateTask"
        >
          <SafeIcon v-if="isCreating" name="Loader2" :size="20" class="mr-2 animate-spin" />
          {{ isCreating ? '创建中...' : '收集文件' }}
        </Button>
      </div>
    </section>

    <section v-else class="collection-card collection-success-card">
      <div class="success-hero">
        <div class="success-icon">
          <SafeIcon name="CheckCircle2" :size="82" />
        </div>
        <h1>已完成您的指示</h1>
        <div class="deadline-row">
          <SafeIcon name="Clock3" :size="22" />
          <span>截止时间：</span>
          <strong>{{ countdownText }}</strong>
        </div>
      </div>

      <div class="success-action-grid">
        <Button variant="secondary" class="success-tile" @click="handleSocialShare">
          <span class="social-badges" aria-hidden="true">
            <span>Q</span>
            <span>微</span>
          </span>
          社交分享
        </Button>
        <Button variant="secondary" class="success-tile" @click="handleOpenPickupCode">
          <span class="tile-icon"><SafeIcon name="KeyRound" :size="24" /></span>
          生成取件码
        </Button>
      </div>

      <div class="public-link-row">
        <span>公共链接：</span>
        <strong>{{ collectLink }}</strong>
        <div class="link-actions">
          <Button variant="ghost" size="sm" @click="copyText(collectLink, '公共链接已复制')">
            <SafeIcon name="Copy" :size="17" />
            复制
          </Button>
          <Button variant="ghost" size="sm" @click="handleShowQRCode">
            <SafeIcon name="QrCode" :size="17" />
            二维码
          </Button>
        </div>
      </div>

      <Button class="continue-button" @click="handleContinueCollect">
        <SafeIcon name="FileUp" :size="20" class="mr-2" />
        继续收集文件
      </Button>
    </section>

    <div class="risk-banner">
      <span>收集违法、违规等有害信息，会受到司法严惩。</span>
      <SafeIcon name="BadgeHelp" :size="22" />
    </div>

    <Dialog :open="isSettingsOpen" @update:open="isSettingsOpen = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>收集设置</DialogTitle>
          <DialogDescription>默认配置已经适合快速收文件，需要时再调整。</DialogDescription>
        </DialogHeader>

        <div class="settings-panel">
          <div class="settings-row">
            <div>
              <Label for="due-days">有效期</Label>
              <p>到期后停止收集</p>
            </div>
            <div class="settings-control">
              <Input id="due-days" v-model="settings.dueDays" type="number" min="1" max="30" />
              <span>天</span>
            </div>
          </div>

          <div class="settings-row">
            <div>
              <Label for="material-name">文件项名称</Label>
              <p>提交页展示给上传人</p>
            </div>
            <Input id="material-name" v-model="settings.materialName" class="settings-input" />
          </div>

          <div class="settings-row">
            <div>
              <Label for="max-size">单文件最大</Label>
              <p>按 MB 限制上传文件</p>
            </div>
            <div class="settings-control">
              <Input id="max-size" v-model="settings.maxSizeMb" type="number" min="1" max="2048" />
              <span>MB</span>
            </div>
          </div>

          <div class="settings-row">
            <div>
              <Label>取件码</Label>
              <p>打开收集链接时需要输入</p>
            </div>
            <Switch v-model:checked="settings.enableAccessCode" />
          </div>

          <div v-if="settings.enableAccessCode" class="access-code-preview">
            <span>{{ settings.accessCode }}</span>
            <Button variant="outline" size="sm" @click="settings.accessCode = makeAccessCode()">
              重新生成
            </Button>
          </div>

          <div class="settings-row">
            <div>
              <Label>允许上传文件夹</Label>
              <p>管理侧展示该规则</p>
            </div>
            <Switch v-model:checked="settings.allowFolderUpload" />
          </div>

          <div class="settings-row">
            <div>
              <Label>自动创建文件夹</Label>
              <p>默认关闭</p>
            </div>
            <Switch v-model:checked="settings.autoCreateFolder" />
          </div>
        </div>

        <DialogFooter>
          <Button @click="isSettingsOpen = false">完成</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="isPickupDialogOpen" @update:open="isPickupDialogOpen = $event">
      <DialogContent class="pickup-dialog max-w-lg">
        <DialogHeader>
          <DialogTitle class="text-center">取件码</DialogTitle>
          <DialogDescription class="text-center">
            提交人打开公共链接后，可输入取件码继续上传文件。
          </DialogDescription>
        </DialogHeader>

        <div class="pickup-body">
          <strong>{{ createdResult.accessCode }}</strong>
          <div class="deadline-row justify-center">
            <SafeIcon name="Clock3" :size="20" />
            <span>有效期：</span>
            <b>{{ countdownText }}</b>
          </div>
          <Button variant="outline" @click="handleRegenerateAccessCode">
            <SafeIcon name="RotateCw" :size="16" class="mr-2" />
            重新生成
          </Button>
        </div>

        <p class="pickup-note">
          取件码是一种私密、安全、快捷传文件的方式。收集文件时，对方打开公共链接后输入取件码即可提交文件。
        </p>

        <DialogFooter>
          <Button variant="outline" @click="copyText(createdResult.accessCode, '取件码已复制')">
            <SafeIcon name="Copy" :size="16" class="mr-2" />
            复制取件码
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="isQrcodeDialogOpen" @update:open="isQrcodeDialogOpen = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>收集二维码</DialogTitle>
          <DialogDescription>扫码打开公共链接，若启用取件码仍需输入取件码。</DialogDescription>
        </DialogHeader>

        <div class="qrcode-box">
          <SafeIcon v-if="isQrcodeLoading" name="Loader2" :size="40" class="animate-spin text-muted-foreground" />
          <img v-else-if="qrcodeImage" :src="qrcodeImage" alt="收集二维码" />
          <div v-else class="qrcode-fallback">
            <SafeIcon name="QrCode" :size="48" />
            <p>二维码暂不可用，请复制公共链接。</p>
          </div>
        </div>

        <div class="qrcode-link">{{ qrcodeUrl || collectLink }}</div>

        <DialogFooter>
          <Button variant="outline" @click="copyText(qrcodeUrl || collectLink, '公共链接已复制')">
            <SafeIcon name="Copy" :size="16" class="mr-2" />
            复制链接
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
.collection-experience {
  position: relative;
  min-height: calc(100vh - var(--header-height));
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0;
  padding: 72px 16px 0;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(244, 249, 255, 0.08), rgba(139, 179, 215, 0.25)),
    linear-gradient(180deg, #8db9ed 0%, #c9dff5 48%, #e7edf4 49%, #d6e4f0 100%);
}

.collection-experience::before,
.collection-experience::after {
  content: "";
  position: absolute;
  pointer-events: none;
}

.collection-experience::before {
  left: 0;
  right: 0;
  top: 0;
  height: 48%;
  background:
    linear-gradient(170deg, rgba(255, 255, 255, 0.72) 0 12%, transparent 12% 100%),
    linear-gradient(12deg, transparent 0 62%, rgba(255, 255, 255, 0.52) 62% 72%, transparent 72% 100%);
  opacity: 0.55;
}

.collection-experience::after {
  left: -4%;
  right: -4%;
  bottom: 0;
  height: 150px;
  background:
    repeating-linear-gradient(90deg, rgba(72, 105, 133, 0.36) 0 12px, rgba(255, 255, 255, 0.34) 12px 18px, transparent 18px 44px),
    linear-gradient(180deg, transparent 0, rgba(73, 101, 124, 0.34) 100%);
}

.collection-card {
  position: relative;
  z-index: 1;
  width: min(760px, 100%);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 18px 50px rgba(27, 66, 112, 0.18);
}

.collection-form-card {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  padding: 96px 40px 38px;
}

.line-field {
  border-bottom: 1px solid rgba(152, 164, 178, 0.45);
}

.line-field + .line-field {
  margin-top: 34px;
}

.line-field:focus-within {
  border-bottom-color: hsl(var(--primary));
}

.line-field input {
  width: 100%;
  height: 42px;
  border: 0;
  outline: 0;
  background: transparent;
  color: hsl(var(--foreground));
  font-size: 24px;
  letter-spacing: 0;
}

.line-field input::placeholder {
  color: rgba(83, 91, 102, 0.55);
}

.collection-actions {
  margin-top: 88px;
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 28px;
}

.settings-button,
.primary-collect-button,
.continue-button {
  height: 64px;
  border-radius: 8px;
}

.settings-button {
  border-color: hsl(var(--primary) / 0.45);
  color: hsl(var(--primary));
  background: rgba(255, 255, 255, 0.92);
}

.primary-collect-button,
.continue-button {
  font-size: 22px;
}

.collection-success-card {
  min-height: 620px;
  padding: 70px 40px 40px;
}

.success-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  text-align: center;
}

.success-icon {
  color: hsl(var(--primary));
}

.success-hero h1 {
  color: hsl(var(--primary));
  font-size: 28px;
  line-height: 1.2;
  font-weight: 500;
}

.deadline-row {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(66, 72, 80, 0.66);
  font-size: 19px;
}

.deadline-row svg {
  color: hsl(var(--success));
}

.deadline-row strong,
.deadline-row b {
  color: rgba(38, 43, 50, 0.82);
  font-size: 22px;
  letter-spacing: 0;
}

.success-action-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 28px;
  margin-top: 82px;
}

.success-tile {
  height: 76px;
  justify-content: center;
  gap: 18px;
  border-radius: 8px;
  color: rgba(42, 47, 54, 0.72);
  font-size: 21px;
}

.social-badges {
  display: inline-flex;
  align-items: center;
}

.social-badges span,
.tile-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
}

.social-badges span:first-child {
  background: #38aeea;
}

.social-badges span:last-child {
  margin-left: -8px;
  background: #47c043;
}

.tile-icon {
  background: hsl(var(--primary));
}

.public-link-row {
  margin-top: 74px;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: start;
  gap: 8px;
  color: rgba(86, 92, 102, 0.58);
  font-size: 20px;
}

.public-link-row strong {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(86, 92, 102, 0.58);
  font-weight: 500;
}

.link-actions {
  display: grid;
  gap: 6px;
}

.link-actions button {
  color: rgba(86, 92, 102, 0.7);
}

.continue-button {
  width: 100%;
  margin-top: 80px;
}

.risk-banner {
  position: relative;
  z-index: 1;
  width: min(640px, calc(100% - 64px));
  min-height: 62px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  border-radius: 0 0 8px 8px;
  background: #f3a217;
  color: #fff;
  box-shadow: 0 10px 28px rgba(96, 62, 0, 0.2);
  font-size: 22px;
}

.settings-panel {
  display: grid;
  gap: 16px;
}

.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid hsl(var(--border));
}

.settings-row p {
  margin-top: 4px;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.settings-control {
  width: 132px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
}

.settings-input {
  width: 168px;
}

.access-code-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-radius: 8px;
  border: 1px solid hsl(var(--primary) / 0.24);
  background: hsl(var(--primary) / 0.06);
  padding: 12px;
}

.access-code-preview span {
  color: hsl(var(--primary));
  font-size: 26px;
  font-weight: 700;
  letter-spacing: 0.18em;
}

.pickup-body {
  display: grid;
  justify-items: center;
  gap: 24px;
  padding: 44px 0 18px;
}

.pickup-body strong {
  color: hsl(var(--primary));
  font-size: 52px;
  line-height: 1;
  font-weight: 500;
  letter-spacing: 0.12em;
}

.pickup-note {
  color: hsl(var(--muted-foreground));
  line-height: 1.8;
  text-align: center;
}

.qrcode-box {
  display: flex;
  aspect-ratio: 1;
  width: min(280px, 100%);
  align-items: center;
  justify-content: center;
  justify-self: center;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--muted) / 0.35);
  padding: 20px;
}

.qrcode-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.qrcode-fallback {
  display: grid;
  justify-items: center;
  gap: 10px;
  color: hsl(var(--muted-foreground));
  text-align: center;
}

.qrcode-link {
  overflow-wrap: anywhere;
  border-radius: 8px;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--muted) / 0.25);
  padding: 10px 12px;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

@media (max-width: 768px) {
  .collection-experience {
    min-height: calc(100vh - var(--header-height));
    padding: 28px 12px 0;
  }

  .collection-form-card {
    padding: 52px 20px 24px;
  }

  .collection-success-card {
    min-height: 560px;
    padding: 56px 20px 24px;
  }

  .line-field input {
    font-size: 20px;
  }

  .collection-actions,
  .success-action-grid,
  .public-link-row {
    grid-template-columns: 1fr;
  }

  .collection-actions {
    margin-top: 48px;
    gap: 14px;
  }

  .settings-button {
    width: 100%;
  }

  .success-action-grid {
    margin-top: 48px;
    gap: 14px;
  }

  .public-link-row {
    margin-top: 42px;
    font-size: 16px;
  }

  .link-actions {
    grid-template-columns: 1fr 1fr;
  }

  .continue-button {
    margin-top: 42px;
  }

  .risk-banner {
    width: calc(100% - 32px);
    padding: 12px 14px;
    font-size: 16px;
    text-align: center;
  }
}
</style>
