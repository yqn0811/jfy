<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { authStore, pcApi } from '@/lib/api'

interface ChinaRegion {
  code: string
  name: string
  province?: string | number
  city?: string | number
  area?: string | number
  children?: ChinaRegion[]
}

interface HomeSettingsForm {
  company_name: string
  company_logo: string
  company_desc: string
  contact_mobile: string
  contact_wechat: string
  address_province: string
  address_city: string
  address_district: string
  address_detail: string
  is_show_home: boolean
  visit_no_need_nickname: boolean
  visit_no_need_mobile: boolean
  visit_allow_save_pic: boolean
  home_service_name: string
  home_watermark_text: string
  home_share_title: string
  home_share_desc: string
  home_share_image: string
  industry_info: string
}

const props = withDefaults(defineProps<{
  open?: boolean
}>(), {
  open: false,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved', profile: any): void
}>()

const emptyForm = (): HomeSettingsForm => ({
  company_name: '',
  company_logo: '',
  company_desc: '',
  contact_mobile: '',
  contact_wechat: '',
  address_province: '',
  address_city: '',
  address_district: '',
  address_detail: '',
  is_show_home: true,
  visit_no_need_nickname: false,
  visit_no_need_mobile: false,
  visit_allow_save_pic: false,
  home_service_name: '服务',
  home_watermark_text: '',
  home_share_title: '',
  home_share_desc: '',
  home_share_image: '',
  industry_info: '0',
})

const isLoading = ref(false)
const isSaving = ref(false)
const isUploadingShareImage = ref(false)
const profile = ref<any>({})
const form = ref<HomeSettingsForm>(emptyForm())
const shareImageInput = ref<HTMLInputElement | null>(null)
const hasLoaded = ref(false)
const regionList = ref<ChinaRegion[]>([])
const isRegionLoading = ref(false)

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
})

const setOpen = (value: boolean) => {
  isOpen.value = value
}

const provinceOptions = computed(() => regionList.value)
const selectedProvince = computed(() => {
  return provinceOptions.value.find(item => item.name === form.value.address_province) || null
})
const cityOptions = computed<ChinaRegion[]>(() => {
  const province = selectedProvince.value
  if (!province) return []
  if (province.children?.some(item => item.children?.length)) return province.children || []
  return [{
    code: province.code,
    name: province.name,
    children: province.children || [],
  }]
})
const selectedCity = computed(() => {
  return cityOptions.value.find(item => item.name === form.value.address_city) || null
})
const districtOptions = computed(() => selectedCity.value?.children || [])

const shareCode = computed(() => profile.value?.share_code || profile.value?.home_share_code || '')

const normalizeBool = (value: any, defaultValue = false) => {
  if (value === undefined || value === null || value === '') return defaultValue
  return Number(value) === 1 || value === true
}

const normalizeRegionSelection = () => {
  const province = provinceOptions.value.find(item => item.name === form.value.address_province)
  if (!province) return

  const cities = cityOptions.value
  if (!cities.length) return

  const city = cities.find(item => item.name === form.value.address_city) || cities[0]
  form.value.address_city = city.name

  const districts = city.children || []
  if (districts.length && !districts.some(item => item.name === form.value.address_district)) {
    form.value.address_district = districts[0].name
  }
}

const loadRegions = async () => {
  if (regionList.value.length || isRegionLoading.value) return
  isRegionLoading.value = true
  try {
    const response = await fetch(`${import.meta.env.BASE_URL}data/china-regions-level.json`)
    regionList.value = await response.json()
    normalizeRegionSelection()
  } catch {
    toast.error('省市区数据加载失败')
  } finally {
    isRegionLoading.value = false
  }
}

const syncForm = (data: any = {}) => {
  form.value = {
    company_name: data.company_name || '',
    company_logo: data.company_logo || data.avatar || '',
    company_desc: data.company_desc || data.user_desc || '',
    contact_mobile: data.contact_mobile || '',
    contact_wechat: data.contact_wechat || '',
    address_province: data.address_province || '',
    address_city: data.address_city || '',
    address_district: data.address_district || '',
    address_detail: data.address_detail || '',
    is_show_home: normalizeBool(data.is_show_home, true),
    visit_no_need_nickname: normalizeBool(data.visit_no_need_nickname),
    visit_no_need_mobile: normalizeBool(data.visit_no_need_mobile),
    visit_allow_save_pic: normalizeBool(data.visit_allow_save_pic),
    home_service_name: data.home_service_name || '服务',
    home_watermark_text: data.home_watermark_text || '',
    home_share_title: data.home_share_title || '',
    home_share_desc: data.home_share_desc || '',
    home_share_image: data.home_share_image || '',
    industry_info: String(data.industry_info ?? 0),
  }
  normalizeRegionSelection()
}

const loadSettings = async () => {
  isLoading.value = true
  try {
    const data = await pcApi.getCurrentUser()
    profile.value = data || {}
    syncForm(data)
    authStore.setUser(data)
    hasLoaded.value = true
  } catch (error: any) {
    toast.error(error?.message || '主页设置加载失败')
  } finally {
    isLoading.value = false
  }
}

watch(() => props.open, (open) => {
  if (!open) return
  loadRegions()
  if (!hasLoaded.value) loadSettings()
})

const handleProvinceChange = (value: any) => {
  form.value.address_province = String(value || '')
  const firstCity = cityOptions.value[0]
  form.value.address_city = firstCity?.name || ''
  form.value.address_district = firstCity?.children?.[0]?.name || ''
}

const handleCityChange = (value: any) => {
  form.value.address_city = String(value || '')
  form.value.address_district = districtOptions.value[0]?.name || ''
}

const buildPayload = () => ({
  company_name: form.value.company_name.trim(),
  company_logo: form.value.company_logo.trim(),
  company_desc: form.value.company_desc.trim(),
  contact_mobile: form.value.contact_mobile.trim(),
  contact_wechat: form.value.contact_wechat.trim(),
  address_province: form.value.address_province.trim(),
  address_city: form.value.address_city.trim(),
  address_district: form.value.address_district.trim(),
  address_detail: form.value.address_detail.trim(),
  is_show_home: form.value.is_show_home ? 1 : 0,
  visit_no_need_nickname: form.value.visit_no_need_nickname ? 1 : 0,
  visit_no_need_mobile: form.value.visit_no_need_mobile ? 1 : 0,
  visit_allow_save_pic: form.value.visit_allow_save_pic ? 1 : 0,
  home_service_name: form.value.home_service_name.trim() || '服务',
  home_watermark_text: form.value.home_watermark_text.trim(),
  home_share_title: form.value.home_share_title.trim(),
  home_share_desc: form.value.home_share_desc.trim(),
  home_share_image: form.value.home_share_image.trim(),
  industry_info: Number(form.value.industry_info || 0),
})

const handleSave = async () => {
  if (!form.value.company_name.trim()) {
    toast.error('请填写商家名称')
    return
  }
  isSaving.value = true
  try {
    await pcApi.updatePcSettings(buildPayload())
    const data = await pcApi.getCurrentUser()
    profile.value = data || {}
    syncForm(data)
    authStore.setUser(data)
    emit('saved', data)
    toast.success('主页设置已保存')
    setOpen(false)
  } catch (error: any) {
    toast.error(error?.message || '保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

const handlePreview = () => {
  if (!shareCode.value) {
    toast.error('主页分享码暂未生成')
    return
  }
  window.location.href = `./share-home?code=${encodeURIComponent(shareCode.value)}`
}

const handleChooseShareImage = () => {
  shareImageInput.value?.click()
}

const handleShareImageSelected = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    toast.error('请选择图片文件')
    target.value = ''
    return
  }
  isUploadingShareImage.value = true
  try {
    const data = await pcApi.uploadCommonImage(file)
    const url = data?.url || data?.file_url || data?.imgurl || data?.path || ''
    if (!url) throw new Error('上传响应缺少图片地址')
    form.value.home_share_image = url
    toast.success('分享封面已上传')
  } catch (error: any) {
    toast.error(error?.message || '上传失败，请稍后重试')
  } finally {
    isUploadingShareImage.value = false
    target.value = ''
  }
}
</script>

<template>
  <Dialog :open="props.open" @update:open="setOpen">
    <DialogScrollContent class="max-h-[88vh] max-w-[1120px] overflow-hidden p-0">
      <div class="flex max-h-[88vh] min-h-[620px] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <div class="flex items-center justify-between gap-4 pr-8">
            <div>
              <DialogTitle>编辑主页</DialogTitle>
              <p class="mt-1 text-sm text-muted-foreground">维护主页资料、联系方式和分享封面</p>
            </div>
            <Button variant="outline" size="sm" class="gap-2" @click="handlePreview">
              <SafeIcon name="Eye" :size="15" />
              预览主页
            </Button>
          </div>
        </DialogHeader>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
          <div v-if="isLoading" class="flex h-80 items-center justify-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="22" class="mr-2 animate-spin" />
            加载中...
          </div>

          <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
            <div class="space-y-5">
              <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-1.5 md:col-span-2">
                  <Label for="home_company_name">商家名称</Label>
                  <Input id="home_company_name" v-model="form.company_name" class="h-9" placeholder="请输入商家名称" />
                </div>
                <div class="space-y-1.5">
                  <Label for="home_service_name">按钮名称</Label>
                  <Input id="home_service_name" v-model="form.home_service_name" class="h-9" placeholder="服务" />
                </div>
                <div class="space-y-1.5">
                  <Label for="contact_mobile">联系电话</Label>
                  <Input id="contact_mobile" v-model="form.contact_mobile" class="h-9" placeholder="联系电话" />
                </div>
                <div class="space-y-1.5">
                  <Label for="contact_wechat">微信号</Label>
                  <Input id="contact_wechat" v-model="form.contact_wechat" class="h-9" placeholder="微信号" />
                </div>
                <div class="space-y-1.5">
                  <Label for="industry_info">行业</Label>
                  <Select v-model="form.industry_info">
                    <SelectTrigger id="industry_info" class="h-9">
                      <SelectValue placeholder="请选择行业" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">未设置</SelectItem>
                      <SelectItem value="1">微供</SelectItem>
                      <SelectItem value="2">网供</SelectItem>
                      <SelectItem value="3">摄影</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-1.5">
                  <Label>省份</Label>
                  <Select :model-value="form.address_province" @update:model-value="handleProvinceChange">
                    <SelectTrigger class="h-9">
                      <SelectValue :placeholder="isRegionLoading ? '加载中' : '省份'" />
                    </SelectTrigger>
                    <SelectContent class="max-h-72">
                      <SelectItem v-for="region in provinceOptions" :key="region.code" :value="region.name">
                        {{ region.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-1.5">
                  <Label>城市</Label>
                  <Select :model-value="form.address_city" :disabled="cityOptions.length === 0" @update:model-value="handleCityChange">
                    <SelectTrigger class="h-9">
                      <SelectValue placeholder="城市" />
                    </SelectTrigger>
                    <SelectContent class="max-h-72">
                      <SelectItem v-for="region in cityOptions" :key="region.code" :value="region.name">
                        {{ region.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-1.5">
                  <Label>区县</Label>
                  <Select v-model="form.address_district" :disabled="districtOptions.length === 0">
                    <SelectTrigger class="h-9">
                      <SelectValue placeholder="区县" />
                    </SelectTrigger>
                    <SelectContent class="max-h-72">
                      <SelectItem v-for="region in districtOptions" :key="region.code" :value="region.name">
                        {{ region.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-1.5 md:col-span-3">
                  <Label for="address_detail">详细地址</Label>
                  <Input id="address_detail" v-model="form.address_detail" class="h-9" placeholder="请输入详细地址" />
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-1.5">
                  <Label for="home_share_title">分享标题</Label>
                  <Input id="home_share_title" v-model="form.home_share_title" class="h-9" placeholder="默认使用商家名称" />
                </div>
                <div class="space-y-1.5">
                  <Label for="home_watermark_text">主页水印</Label>
                  <Input id="home_watermark_text" v-model="form.home_watermark_text" class="h-9" placeholder="默认不显示自定义水印" />
                </div>
                <div class="space-y-1.5 md:col-span-2">
                  <Label for="home_share_desc">分享描述</Label>
                  <Textarea id="home_share_desc" v-model="form.home_share_desc" class="min-h-[68px] resize-none" placeholder="分享卡片描述" />
                </div>
                <div class="space-y-1.5 md:col-span-2">
                  <Label for="company_desc">主页简介</Label>
                  <Textarea id="company_desc" v-model="form.company_desc" class="min-h-[76px] resize-none" placeholder="主页顶部简介" />
                </div>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                  <span class="text-sm">展示主页</span>
                  <Switch v-model="form.is_show_home" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                  <span class="text-sm">允许访客保存图片</span>
                  <Switch v-model="form.visit_allow_save_pic" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                  <span class="text-sm">访客无需昵称</span>
                  <Switch v-model="form.visit_no_need_nickname" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                  <span class="text-sm">访客无需手机号</span>
                  <Switch v-model="form.visit_no_need_mobile" />
                </div>
              </div>
            </div>

            <aside class="space-y-4">
              <div class="space-y-2">
                <Label>分享封面图</Label>
                <input
                  ref="shareImageInput"
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handleShareImageSelected"
                />
                <button
                  type="button"
                  class="group relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-lg border border-dashed border-border bg-muted/30 transition-colors hover:border-primary"
                  @click="handleChooseShareImage"
                >
                  <img
                    v-if="form.home_share_image"
                    :src="form.home_share_image"
                    alt=""
                    class="h-full w-full object-cover"
                  />
                  <div
                    class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/0 text-muted-foreground transition-colors group-hover:bg-black/35 group-hover:text-white"
                    :class="!form.home_share_image && 'bg-transparent'"
                  >
                    <SafeIcon :name="isUploadingShareImage ? 'Loader2' : 'UploadCloud'" :size="24" :class="isUploadingShareImage ? 'animate-spin' : ''" />
                    <span class="text-sm font-medium">{{ form.home_share_image ? '更换封面' : '上传封面' }}</span>
                  </div>
                </button>
              </div>

              <div class="space-y-2">
                <Label for="company_logo">Logo 地址</Label>
                <Input id="company_logo" v-model="form.company_logo" class="h-9" placeholder="https://..." />
              </div>

              <div class="rounded-lg border border-border bg-muted/20 p-3">
                <p class="truncate text-sm font-semibold">{{ form.company_name || '商户主页' }}</p>
                <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ form.company_desc || '暂无简介' }}</p>
                <p class="mt-3 truncate text-xs text-muted-foreground">
                  {{ [form.address_province, form.address_city, form.address_district].filter(Boolean).join(' / ') || '未设置地址' }}
                </p>
              </div>
            </aside>
          </div>
        </div>

        <DialogFooter class="border-t border-border px-6 py-4">
          <Button variant="outline" @click="setOpen(false)">取消</Button>
          <Button :disabled="isSaving || isLoading" @click="handleSave">
            <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
            保存设置
          </Button>
        </DialogFooter>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>
