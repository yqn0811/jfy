
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'

interface WatermarkSettings {
  text: string
  fontSize: number
  opacity: number
  angle: number
}

const DEFAULT_WATERMARK: WatermarkSettings = {
  text: '© 家纺云 版权所有',
  fontSize: 24,
  opacity: 0.3,
  angle: -45,
}

const watermarkText = ref(DEFAULT_WATERMARK.text)
const fontSize = ref(DEFAULT_WATERMARK.fontSize)
const opacity = ref(DEFAULT_WATERMARK.opacity)
const angle = ref(DEFAULT_WATERMARK.angle)
const isClient = ref(true)
const isSaving = ref(false)
const isLoading = ref(false)

const previewImageUrl = 'https://api.jfyuntu.com/image/img_default.png'

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    loadSettings()
    isClient.value = true
  })
})

const loadSettings = () => {
  isLoading.value = true
  pcApi.getCurrentUser()
    .then((profile) => {
      watermarkText.value = profile?.home_watermark_text || DEFAULT_WATERMARK.text
    })
    .catch((e) => {
      console.error('Failed to load watermark settings:', e)
    })
    .finally(() => {
      isLoading.value = false
    })
}

const saveSettings = async () => {
  isSaving.value = true
  try {
    await pcApi.updatePcSettings({ home_watermark_text: watermarkText.value })
    toast.success('设置已保存')
  } catch (e) {
    console.error('Failed to save watermark settings:', e)
    toast.error('保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

const resetSettings = () => {
  watermarkText.value = DEFAULT_WATERMARK.text
  fontSize.value = DEFAULT_WATERMARK.fontSize
  opacity.value = DEFAULT_WATERMARK.opacity
  angle.value = DEFAULT_WATERMARK.angle
  toast('已重置为默认值')
}

const previewStyle = computed(() => ({
  position: 'relative' as const,
  overflow: 'hidden' as const,
  borderRadius: '0.5rem',
}))

const watermarkOverlayStyle = computed(() => ({
  position: 'absolute' as const,
  top: '50%',
  left: '50%',
  transform: `translate(-50%, -50%) rotate(${angle.value}deg)`,
  fontSize: `${fontSize.value}px`,
  color: `rgba(0, 0, 0, ${opacity.value})`,
  fontWeight: 'bold',
  whiteSpace: 'nowrap' as const,
  pointerEvents: 'none' as const,
  zIndex: 10,
}))
</script>

<template>
  <div class="page-body space-y-6">
    <!-- 页面标题 -->
    <div class="mb-8">
      <h1 class="text-page-title mb-2">水印设置</h1>
      <p class="text-caption">为您的产品图片添加版权保护水印，访客在预览和下载时都能看到水印效果</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 左侧：设置表单 -->
      <div class="lg:col-span-1 space-y-6">
        <!-- 水印文案设置 -->
        <Card class="surface-base">
          <CardHeader class="pb-4">
            <CardTitle class="text-section-title">水印文案</CardTitle>
            <CardDescription>输入要显示在图片上的文字</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="watermark-text" class="text-label">文案内容</Label>
              <Textarea
                id="watermark-text"
                v-model="watermarkText"
                placeholder="输入水印文案，如：© 公司名 版权所有"
                class="min-h-[80px] resize-none"
                maxlength="100"
              />
              <p class="text-[11px] text-muted-foreground">
                {{ watermarkText.length }} / 100 字符
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- 水印样式设置 -->
        <Card class="surface-base">
          <CardHeader class="pb-4">
            <CardTitle class="text-section-title">样式设置</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- 字体大小 -->
            <div class="space-y-2">
              <Label for="font-size" class="text-label">字体大小</Label>
              <div class="flex items-center gap-3">
                <Input
                  id="font-size"
                  v-model.number="fontSize"
                  type="number"
                  min="12"
                  max="72"
                  class="flex-1 h-9"
                />
                <span class="text-sm text-muted-foreground w-12">{{ fontSize }}px</span>
              </div>
            </div>

            <!-- 透明度 -->
            <div class="space-y-2">
              <Label for="opacity" class="text-label">透明度</Label>
              <div class="flex items-center gap-3">
                <input
                  id="opacity"
                  v-model.number="opacity"
                  type="range"
                  min="0.1"
                  max="1"
                  step="0.1"
                  class="flex-1 h-2 bg-muted rounded-lg appearance-none cursor-pointer accent-primary"
                />
                <span class="text-sm text-muted-foreground w-12">{{ Math.round(opacity * 100) }}%</span>
              </div>
            </div>

            <!-- 旋转角度 -->
            <div class="space-y-2">
              <Label for="angle" class="text-label">旋转角度</Label>
              <div class="flex items-center gap-3">
                <Input
                  id="angle"
                  v-model.number="angle"
                  type="number"
                  min="-90"
                  max="90"
                  class="flex-1 h-9"
                />
                <span class="text-sm text-muted-foreground w-12">{{ angle }}°</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- 操作按钮 -->
        <div class="space-y-2">
          <Button
            class="w-full h-10 bg-primary text-primary-foreground hover:bg-primary-hover"
            :disabled="isSaving"
            @click="saveSettings"
          >
            <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
            <SafeIcon v-else name="Save" :size="16" class="mr-2" />
            {{ isSaving ? '保存中...' : '保存设置' }}
          </Button>
          <Button
            variant="outline"
            class="w-full h-10"
            @click="resetSettings"
          >
            <SafeIcon name="RotateCcw" :size="16" class="mr-2" />
            恢复默认
          </Button>
        </div>

        <!-- 提示信息 -->
        <Alert class="bg-accent/10 border-accent/30">
          <SafeIcon name="Info" :size="16" class="text-accent" />
          <AlertDescription class="text-xs text-accent ml-2">
            水印将应用于所有产品图片的预览和下载版本
          </AlertDescription>
        </Alert>
      </div>

      <!-- 右侧：预览区 -->
      <div class="lg:col-span-2">
        <Card class="surface-base h-full">
          <CardHeader class="pb-4">
            <CardTitle class="text-section-title">预览效果</CardTitle>
            <CardDescription>实时预览水印在图片上的显示效果</CardDescription>
          </CardHeader>
          <CardContent>
            <div
              v-if="isClient"
              :style="previewStyle"
              class="w-full bg-muted rounded-lg overflow-hidden border border-border"
            >
              <!-- 预览图片 -->
              <img
                :src="previewImageUrl"
                alt="水印预览"
                class="w-full h-auto block"
              />
              <!-- 水印覆盖层 -->
              <div :style="watermarkOverlayStyle">
                {{ watermarkText || '水印预览' }}
              </div>
            </div>
            <div v-else class="w-full h-64 bg-muted rounded-lg animate-pulse" />
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- 应用说明 -->
    <Card class="surface-base bg-secondary/20 border-secondary/30">
      <CardHeader class="pb-3">
        <CardTitle class="text-base flex items-center gap-2">
          <SafeIcon name="HelpCircle" :size="18" class="text-primary" />
          应用说明
        </CardTitle>
      </CardHeader>
      <CardContent class="text-sm text-muted-foreground space-y-2">
        <p>✓ 水印将自动应用于所有产品的花色图和详情图</p>
        <p>✓ 访客在图片查看器中可以看到水印效果</p>
        <p>✓ 下载的图片也会包含水印，保护您的版权</p>
        <p>✓ 修改水印设置后，新上传的图片将使用最新配置</p>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
/* 范围滑块自定义样式 */
input[type='range']::-webkit-slider-thumb {
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: hsl(var(--primary));
  cursor: pointer;
  border: 2px solid hsl(var(--background));
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type='range']::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: hsl(var(--primary));
  cursor: pointer;
  border: 2px solid hsl(var(--background));
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type='range']::-webkit-slider-runnable-track {
  background: hsl(var(--muted));
  height: 6px;
  border-radius: 3px;
}

input[type='range']::-moz-range-track {
  background: hsl(var(--muted));
  height: 6px;
  border-radius: 3px;
}
</style>
