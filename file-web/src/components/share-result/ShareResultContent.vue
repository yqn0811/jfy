<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { FileShareService, type FileShareVO } from '@/data/FileShareService'
import { FileTransferApi, getRememberedSharePassword, type FileTransferShareVO } from '@/data/FileTransferApi'
import { getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

const currentShare = ref<FileShareVO | null>(null)
const shareId = ref('')
const shareCode = ref('')
const pickupCode = ref('')
const accessPassword = ref('')
const isReceiverMode = ref(false)
const receiverVerified = ref(false)
const isLoadingShare = ref(true)
const isVerifyingPassword = ref(false)
const qrcodeImage = ref('')
const isQrcodeLoading = ref(false)

const createReceiverGateShare = (code: string): FileTransferShareVO => ({
  id: code,
  shareCode: code,
  title: '分享的文件',
  shareUrl: `/share-result?shareCode=${encodeURIComponent(code)}`,
  password: '',
  expiresAt: '',
  maxDownloads: 0,
  allowPreview: false,
  notifyOnDownload: false,
  status: 'active',
  fileCount: 0,
  totalSizeMb: 0,
  downloadCount: 0,
  recentLogs: [],
  hasPassword: true,
  passwordVerified: false,
  files: [],
  createdAt: '',
})

onMounted(async () => {
  const params = new URLSearchParams(window.location.search)
  const paramShareId = params.get('shareId') || ''
  const paramShareCode = params.get('shareCode') || params.get('code') || ''
  const paramPickupCode = params.get('pickupCode') || params.get('pickup_code') || params.get('pickup') || ''

  try {
    if (paramPickupCode) {
      isReceiverMode.value = true
      pickupCode.value = paramPickupCode
      accessPassword.value = paramPickupCode
      const share = await FileTransferApi.getShareByPickupCode(paramPickupCode)
      currentShare.value = share
      shareId.value = share.id
      shareCode.value = share.shareCode
      pickupCode.value = share.pickupCode || paramPickupCode
      receiverVerified.value = true
      return
    }

    if (paramShareCode) {
      isReceiverMode.value = !paramShareId
      shareCode.value = paramShareCode
      const localShare = paramShareId ? FileShareService.getShareVOById(paramShareId) : undefined
      const rememberedPassword = isReceiverMode.value ? '' : getRememberedSharePassword(paramShareCode) || localShare?.password || ''
      accessPassword.value = rememberedPassword

      try {
        const share = isReceiverMode.value && !rememberedPassword
          ? createReceiverGateShare(paramShareCode)
          : isReceiverMode.value
            ? await FileTransferApi.getPublicShare(paramShareCode, rememberedPassword)
            : rememberedPassword
              ? await FileTransferApi.getPublicShare(paramShareCode, rememberedPassword)
              : await FileTransferApi.getOwnerShare(paramShareCode)
        currentShare.value = {
          ...share,
          password: localShare?.password || share.password,
        }
        shareId.value = share.id
        receiverVerified.value = isReceiverMode.value ? Boolean(rememberedPassword && share.passwordVerified) : true
        return
      } catch (error) {
        if (localShare && !isReceiverMode.value) {
          currentShare.value = localShare
          shareId.value = localShare.id
          receiverVerified.value = true
          return
        }
        if (isReceiverMode.value) {
          currentShare.value = createReceiverGateShare(paramShareCode)
          shareId.value = paramShareCode
          receiverVerified.value = false
          return
        }
        throw error
      }
    }

    if (paramShareId) {
      const localShare = FileShareService.getShareVOById(paramShareId)
      if (localShare) {
        currentShare.value = localShare
        shareId.value = localShare.id
        const code = localShare.shareCode || new URL(localShare.shareUrl || '/', window.location.origin).searchParams.get('shareCode') || ''
        shareCode.value = code
        accessPassword.value = getRememberedSharePassword(code) || localShare.password || ''
        return
      }
    }
  } catch (error) {
    toast.error(getApiErrorMessage(error, '分享信息加载失败'))
  } finally {
    isLoadingShare.value = false
  }
})

const remoteShare = computed(() => currentShare.value as FileTransferShareVO | null)
const fileList = computed(() => remoteShare.value?.files || [])

const isExpired = computed(() => {
  if (!currentShare.value?.expiresAt) return false
  return new Date(currentShare.value.expiresAt) < new Date()
})

const expiresText = computed(() => {
  if (!currentShare.value?.expiresAt) return '-'
  if (isExpired.value) return '已过期'
  const expiresAt = new Date(currentShare.value.expiresAt)
  if (Number.isNaN(expiresAt.getTime())) return '-'
  const seconds = Math.max(0, Math.floor((expiresAt.getTime() - Date.now()) / 1000))
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  if (hours >= 24) return `${Math.ceil(hours / 24)} 天`
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
})

const downloadLimitText = computed(() => {
  const share = currentShare.value
  if (!share?.maxDownloads) return '不限次数'
  return `${share.downloadCount}/${share.maxDownloads} 次`
})

const receiverStatusText = computed(() => {
  if (isExpired.value) return '分享已过期'
  if (!receiverVerified.value) return '等待输入取件码'
  return '取件码已验证'
})

const receiverExpiresText = computed(() => {
  return receiverVerified.value ? expiresText.value : '验证后显示'
})

const receiverMetaItems = computed(() => {
  const share = currentShare.value
  if (!share) return []
  return [
    { label: '文件数量', value: `${share.fileCount} 个` },
    { label: '总大小', value: formatFileSize(share.totalSizeMb) },
    { label: '已下载', value: `${share.downloadCount} 次` },
    { label: '下载限制', value: downloadLimitText.value },
  ]
})

const receiverNeedsPassword = computed(() => Boolean(isReceiverMode.value && currentShare.value && !receiverVerified.value))

const canShowReceiverFiles = computed(() => {
  return Boolean(isReceiverMode.value && currentShare.value && receiverVerified.value && !isExpired.value)
})

const shareLink = computed(() => {
  if (!currentShare.value) return ''
  if (/^https?:\/\//i.test(currentShare.value.shareUrl)) return currentShare.value.shareUrl
  return new URL(currentShare.value.shareUrl.replace(/\.html(?=\?|$)/, ''), window.location.origin).toString()
})

const formatFileSize = (mb: number) => {
  if (mb > 0 && mb < 1 / 1024) return `${Math.max(1, Math.round(mb * 1024 * 1024))} B`
  if (mb > 0 && mb < 1) return `${Math.max(1, Math.round(mb * 1024))} KB`
  if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`
  return `${mb.toFixed(2)} MB`
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const handleVerifyPassword = async () => {
  if (!shareCode.value) return
  const password = accessPassword.value.trim().toUpperCase()
  if (!password) {
    toast.error('请输入取件码')
    return
  }

  isVerifyingPassword.value = true
  try {
    const share = await FileTransferApi.verifySharePassword(shareCode.value, password)
    currentShare.value = share
    accessPassword.value = password
    pickupCode.value = share.pickupCode || password
    receiverVerified.value = true
    toast.success('取件码验证通过')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '取件码不正确'))
  } finally {
    isVerifyingPassword.value = false
  }
}

const getFileDownloadUrl = (fileId: string) => {
  if (shareCode.value) {
    return FileTransferApi.getSharedDownloadUrl(
      fileId,
      shareCode.value,
      accessPassword.value || getRememberedSharePassword(shareCode.value),
      pickupCode.value
    )
  }
  if (pickupCode.value) {
    return FileTransferApi.getSharedDownloadUrl(fileId, '', '', pickupCode.value)
  }
  return FileTransferApi.getOwnerDownloadUrl(fileId)
}

const handleDownloadFile = (fileId: string) => {
  window.open(getFileDownloadUrl(fileId), '_blank')
}

const handleDownloadAll = () => {
  const files = fileList.value
  if (!files.length) {
    toast.info('暂无可下载文件')
    return
  }
  files.forEach((file) => handleDownloadFile(file.id))
}

const handleContinueSending = () => {
  navigateTo('/quick-send')
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

const handleCopyLink = () => {
  copyText(shareLink.value, '链接已复制')
}

const handleCopyPickupCode = () => {
  copyText(remoteShare.value?.pickupCode || pickupCode.value || accessPassword.value, '取件码已复制')
}

const loadQrcode = async () => {
  if (!shareCode.value || qrcodeImage.value) return
  try {
    isQrcodeLoading.value = true
    const result = await FileTransferApi.getShareQrcode(shareCode.value, shareLink.value)
    qrcodeImage.value = result.qrcode
  } catch (error) {
    toast.error(getApiErrorMessage(error, '二维码生成失败'))
  } finally {
    isQrcodeLoading.value = false
  }
}
</script>

<template>
  <div class="share-page">
    <div v-if="currentShare" class="share-shell">
      <template v-if="isReceiverMode">
        <section class="receiver-layout">
          <main class="receiver-main">
            <section v-if="receiverNeedsPassword" class="password-panel">
              <SafeIcon name="LockKeyhole" :size="42" />
              <h1>需要取件码才能访问</h1>
              <form class="password-form" @submit.prevent="handleVerifyPassword">
                <Input
                  v-model="accessPassword"
                  type="password"
                  autocomplete="current-password"
                  placeholder="请输入分享者提供的取件码"
                />
                <Button type="submit" :disabled="isVerifyingPassword">
                  <SafeIcon v-if="isVerifyingPassword" name="Loader2" :size="16" class="animate-spin" />
                  打开
                </Button>
              </form>
            </section>

            <section v-else class="receiver-file-panel">
              <div class="receiver-summary">
                <div class="receiver-title">
                  <div class="receiver-avatar">
                    <SafeIcon name="Users" :size="34" />
                  </div>
                  <div>
                    <h1>分享的文件</h1>
                    <p>共 {{ currentShare.fileCount }} 个文件，总大小：{{ formatFileSize(currentShare.totalSizeMb) }}</p>
                  </div>
                </div>
                <Badge :class="isExpired ? 'expired-badge' : 'active-badge'">
                  {{ isExpired ? '已过期' : `过期时间 ${expiresText}` }}
                </Badge>
              </div>
              <div class="receiver-actions">
                <span class="receiver-state">
                  <SafeIcon :name="isExpired ? 'Clock3' : 'ShieldCheck'" :size="17" />
                  {{ receiverStatusText }}
                </span>
                <Button :disabled="isExpired || fileList.length === 0" @click="handleDownloadAll">
                  <SafeIcon name="Download" :size="17" />
                  下载全部
                </Button>
              </div>
              <div v-if="canShowReceiverFiles" class="receiver-files">
                <div class="receiver-files-head">
                  <strong>文件列表</strong>
                  <span>{{ fileList.length }} 个文件</span>
                </div>
                <div v-for="file in fileList" :key="file.id" class="receiver-file-row">
                  <SafeIcon name="FileText" :size="22" />
                  <div class="receiver-file-info">
                    <strong>{{ file.fileName }}</strong>
                    <span>{{ formatFileSize(file.fileSizeMb) }}</span>
                  </div>
                  <Button variant="ghost" size="sm" :disabled="isExpired" @click="handleDownloadFile(file.id)">
                    下载
                  </Button>
                </div>
                <div v-if="fileList.length === 0" class="empty-file-box">
                  <SafeIcon name="FileQuestion" :size="34" />
                  <span>暂无可下载文件</span>
                </div>
              </div>
              <div v-else class="empty-file-box">
                <SafeIcon name="Clock3" :size="34" />
                <span>分享已过期，文件不可下载</span>
              </div>
              <div class="receiver-meta-grid">
                <div v-for="item in receiverMetaItems" :key="item.label" class="receiver-meta-item">
                  <span>{{ item.label }}</span>
                  <strong>{{ item.value }}</strong>
                </div>
              </div>
            </section>
          </main>

          <aside class="receiver-side">
            <div class="receiver-info-card">
              <h2>取件信息</h2>
              <dl>
                <div>
                  <dt>访问状态</dt>
                  <dd>{{ receiverStatusText }}</dd>
                </div>
                <div>
                  <dt>有效期</dt>
                  <dd>{{ receiverExpiresText }}</dd>
                </div>
                <div v-if="receiverVerified">
                  <dt>文件数量</dt>
                  <dd>{{ currentShare.fileCount }} 个</dd>
                </div>
              </dl>
            </div>
            <div class="receiver-info-card">
              <h2>下载信息</h2>
              <dl v-if="receiverVerified">
                <div>
                  <dt>已下载</dt>
                  <dd>{{ currentShare.downloadCount }} 次</dd>
                </div>
                <div>
                  <dt>次数限制</dt>
                  <dd>{{ downloadLimitText }}</dd>
                </div>
              </dl>
              <p v-else>验证通过后显示文件和下载信息。</p>
            </div>
          </aside>
        </section>
      </template>

      <template v-else>
        <section class="owner-layout">
          <aside class="owner-sidebar">
            <button class="owner-nav active">
              <SafeIcon name="Send" :size="17" />
              我发送的
            </button>
          </aside>

          <main class="owner-main">
            <header class="owner-header">
              <div class="owner-title">
                <div class="owner-avatar">
                  <SafeIcon name="Users" :size="34" />
                </div>
                <div>
                  <h1>发文件</h1>
                  <p>共 {{ currentShare.fileCount }} 个文件，总大小：{{ formatFileSize(currentShare.totalSizeMb) }}</p>
                </div>
              </div>
              <div class="owner-header-actions">
                <span>
                  <SafeIcon name="Clock3" :size="16" />
                  过期时间：<strong>{{ expiresText }}</strong>
                </span>
                <Button :disabled="fileList.length === 0" @click="handleDownloadAll">
                  <SafeIcon name="Download" :size="16" />
                  下载
                </Button>
                <Button variant="outline" @click="handleContinueSending">
                  发送新文件
                </Button>
              </div>
            </header>

            <section class="owner-content">
              <div class="owner-toolbar">
                <div>
                  <SafeIcon name="FolderOpen" :size="18" />
                  全部文件
                </div>
                <div class="owner-view-icons">
                  <SafeIcon name="Files" :size="18" />
                  <SafeIcon name="LayoutDashboard" :size="18" />
                </div>
              </div>
              <div class="owner-file-grid">
                <article v-for="file in fileList" :key="file.id" class="owner-file-card">
                  <div class="owner-file-icon">
                    <SafeIcon name="FileText" :size="52" />
                  </div>
                  <strong>{{ file.fileName }}</strong>
                  <span>{{ formatFileSize(file.fileSizeMb) }}</span>
                  <Button variant="ghost" size="sm" @click="handleDownloadFile(file.id)">
                    下载
                  </Button>
                </article>
                <div v-if="fileList.length === 0" class="empty-file-box">
                  <SafeIcon name="FileQuestion" :size="34" />
                  <span>暂无可查看的文件</span>
                </div>
              </div>
            </section>
          </main>

          <aside class="owner-manage">
            <header>
              <h2>管理</h2>
              <Button variant="ghost" size="icon" @click="navigateTo('/delivery-records')">
                <SafeIcon name="X" :size="18" />
              </Button>
            </header>
            <div class="manage-section">
              <div class="manage-row">
                <span>取件码</span>
                <Switch :checked="Boolean(currentShare.password || accessPassword)" disabled />
              </div>
              <Input :model-value="remoteShare?.pickupCode || currentShare.password || accessPassword || '未设置取件码'" readonly />
              <Button variant="outline" @click="handleCopyPickupCode">
                <SafeIcon name="Copy" :size="15" />
                复制取件码
              </Button>
            </div>
            <div class="manage-section">
              <span>生成公共链接</span>
              <button class="manage-link" type="button" @click="handleCopyLink">{{ shareLink }}</button>
              <div class="manage-inline-actions">
                <Button variant="ghost" size="sm" @click="handleCopyLink">
                  <SafeIcon name="Copy" :size="15" />
                  复制
                </Button>
                <Button variant="ghost" size="sm" @click="loadQrcode">
                  <SafeIcon name="QrCode" :size="15" />
                  二维码
                </Button>
              </div>
              <div v-if="isQrcodeLoading || qrcodeImage" class="owner-qrcode-box">
                <SafeIcon v-if="isQrcodeLoading" name="Loader2" :size="28" class="animate-spin" />
                <img v-else :src="qrcodeImage" alt="分享二维码" />
              </div>
            </div>
            <div class="manage-section">
              <span>统计</span>
              <p>下载次数：{{ currentShare.downloadCount }}</p>
              <p>生成时间：{{ formatDate(remoteShare?.createdAt || '') }}</p>
            </div>
          </aside>
        </section>
      </template>
    </div>

    <div v-else class="share-loading">
      <SafeIcon :name="isLoadingShare ? 'Loader2' : 'Link2Off'" :size="42" :class="isLoadingShare && 'animate-spin'" />
      <p>{{ isLoadingShare ? '加载中...' : '没有找到分享链接' }}</p>
      <Button v-if="!isLoadingShare" variant="outline" @click="navigateTo('/quick-send')">返回发文件</Button>
    </div>
  </div>
</template>

<style scoped>
.share-page {
  min-height: calc(100vh - var(--header-height));
  background: hsl(var(--background));
}

.share-shell {
  width: min(100%, var(--app-shell-width));
  min-width: 1120px;
  margin: 0 auto;
  padding: 24px 32px;
}

.receiver-main,
.owner-main,
.owner-manage,
.owner-sidebar,
.receiver-side {
  min-width: 0;
}

.receiver-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  align-items: start;
  gap: 24px;
}

.receiver-main {
  min-height: 0;
}

.receiver-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.password-panel,
.receiver-file-panel,
.owner-content,
.owner-header,
.owner-manage,
.receiver-info-card {
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--card));
  box-shadow: var(--shadow-soft);
}

.password-panel {
  display: flex;
  min-height: 420px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 22px;
}

.password-panel svg {
  color: hsl(var(--primary));
}

.password-panel h1 {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 0;
}

.password-form {
  display: flex;
  width: min(100%, 420px);
  flex-direction: column;
  gap: 16px;
}

.receiver-file-panel {
  overflow: hidden;
}

.receiver-summary,
.owner-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 24px;
}

.receiver-title {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 14px;
}

.receiver-avatar,
.owner-avatar {
  display: grid;
  width: 58px;
  height: 58px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 999px;
  background: hsl(var(--primary) / 0.12);
  color: hsl(var(--primary));
}

.receiver-summary h1,
.owner-title h1 {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0;
}

.receiver-summary p,
.owner-title p {
  margin-top: 6px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.active-badge {
  border-color: hsl(var(--success) / 0.3);
  background: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
}

.expired-badge {
  border-color: hsl(var(--destructive) / 0.3);
  background: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
}

.receiver-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border-top: 1px solid hsl(var(--border));
  padding: 16px 24px;
}

.receiver-actions > * {
  min-width: 136px;
}

.receiver-state {
  display: inline-flex;
  min-width: 0;
  align-items: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  font-weight: 700;
}

.receiver-files {
  border-top: 1px solid hsl(var(--border));
}

.receiver-files-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: hsl(var(--muted) / 0.2);
  padding: 12px 24px;
}

.receiver-files-head strong {
  font-size: 14px;
  font-weight: 800;
}

.receiver-files-head span {
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.receiver-file-row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 14px;
  align-items: center;
  border-bottom: 1px solid hsl(var(--border));
  padding: 16px 24px;
}

.receiver-file-row strong,
.owner-file-card strong {
  overflow: hidden;
  color: hsl(var(--foreground));
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.receiver-file-row span,
.owner-file-card span {
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.receiver-file-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 4px;
}

.receiver-meta-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0;
  border-top: 1px solid hsl(var(--border));
  background: hsl(var(--muted) / 0.12);
  padding: 18px 24px;
}

.receiver-meta-item {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 8px;
  border-left: 1px solid hsl(var(--border));
  padding: 0 16px;
}

.receiver-meta-item:first-child {
  border-left: 0;
  padding-left: 0;
}

.receiver-meta-item span {
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.receiver-meta-item strong {
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 16px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.receiver-info-card {
  padding: 18px;
}

.receiver-info-card h2 {
  margin-bottom: 14px;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0;
}

.receiver-info-card dl {
  display: grid;
  gap: 12px;
}

.receiver-info-card dl div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-top: 1px solid hsl(var(--border));
  padding-top: 12px;
}

.receiver-info-card dl div:first-child {
  border-top: 0;
  padding-top: 0;
}

.receiver-info-card dt,
.receiver-info-card p {
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.receiver-info-card dd {
  min-width: 0;
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 800;
  text-align: right;
}

.owner-layout {
  display: grid;
  grid-template-columns: 232px minmax(640px, 1fr) 320px;
  gap: 0;
  min-height: calc(100vh - var(--header-height) - 48px);
}

.owner-sidebar {
  border-right: 1px solid hsl(var(--border));
  background: hsl(var(--card));
  padding: 18px 0;
}

.owner-nav {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  font-weight: 700;
  text-align: left;
}

.owner-nav.active {
  border-left: 3px solid hsl(var(--primary));
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.owner-header {
  border-radius: 0;
  box-shadow: none;
}

.owner-title,
.owner-header-actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.owner-header-actions span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.owner-header-actions strong {
  color: hsl(var(--warning));
}

.owner-content {
  min-height: 580px;
  border-top: 0;
  border-radius: 0;
  box-shadow: none;
}

.owner-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid hsl(var(--border));
  padding: 18px 28px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.owner-toolbar > div,
.owner-view-icons {
  display: flex;
  align-items: center;
  gap: 10px;
}

.owner-file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 18px;
  padding: 42px;
}

.owner-file-card {
  display: grid;
  min-height: 230px;
  grid-template-rows: 1fr auto auto auto;
  gap: 10px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--muted) / 0.22);
  padding: 18px;
}

.owner-file-icon {
  display: grid;
  min-height: 100px;
  place-items: center;
  color: hsl(var(--muted-foreground));
}

.owner-manage {
  border-radius: 0;
  box-shadow: none;
}

.owner-manage header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid hsl(var(--border));
  padding: 16px 18px;
}

.owner-manage h2 {
  font-size: 17px;
  font-weight: 800;
}

.manage-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-bottom: 1px solid hsl(var(--border));
  padding: 18px;
}

.manage-section > span,
.manage-row span {
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 700;
}

.manage-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.manage-link {
  overflow: hidden;
  color: hsl(var(--primary));
  font-size: 13px;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.manage-inline-actions {
  display: flex;
  gap: 8px;
}

.owner-qrcode-box {
  display: grid;
  width: 132px;
  height: 132px;
  place-items: center;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: white;
}

.owner-qrcode-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 8px;
}

.manage-section p {
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.empty-file-box,
.share-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: hsl(var(--muted-foreground));
  text-align: center;
}

.empty-file-box {
  min-height: 220px;
  grid-column: 1 / -1;
}

.share-loading {
  min-height: calc(100vh - var(--header-height));
}

@media (max-width: 1200px) {
  .share-shell {
    min-width: 0;
  }

  .receiver-layout {
    grid-template-columns: 1fr;
  }

  .receiver-side {
    margin-top: 24px;
  }

  .receiver-meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    row-gap: 18px;
  }

  .receiver-meta-item:nth-child(odd) {
    border-left: 0;
    padding-left: 0;
  }

  .owner-layout {
    grid-template-columns: 1fr;
  }

  .owner-sidebar {
    display: none;
  }
}
</style>
