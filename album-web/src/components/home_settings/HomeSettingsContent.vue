<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { authStore, pcApi } from '@/lib/api'

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

const isLoading = ref(false)
const isSaving = ref(false)
const profile = ref<any>({})
const form = ref<HomeSettingsForm>({
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

const industryText = computed(() => {
  const map: Record<string, string> = {
    '0': '未设置',
    '1': '微供',
    '2': '网供',
    '3': '摄影',
  }
  return map[form.value.industry_info] || '未设置'
})

const previewLogo = computed(() => form.value.company_logo || profile.value?.avatar || '')
const previewTitle = computed(() => form.value.home_share_title || `${form.value.company_name || profile.value?.nickname || '商户'}的产品主页`)
const previewDesc = computed(() => form.value.home_share_desc || form.value.company_desc || '展示产品、分类与详情图')
const shareCode = computed(() => profile.value?.share_code || profile.value?.home_share_code || '')

const normalizeBool = (value: any, defaultValue = false) => {
  if (value === undefined || value === null || value === '') return defaultValue
  return Number(value) === 1 || value === true
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
}

const loadSettings = async () => {
  isLoading.value = true
  try {
    const data = await pcApi.getCurrentUser()
    profile.value = data || {}
    syncForm(data)
    authStore.setUser(data)
  } catch (error: any) {
    toast.error(error?.message || '主页设置加载失败')
  } finally {
    isLoading.value = false
  }
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
    toast.success('主页设置已保存')
    await loadSettings()
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

onMounted(loadSettings)
</script>

<template>
  <div class="page-body space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-page-title">编辑主页</h1>
        <p class="text-caption mt-2">维护商家主页资料、分享信息与访问权限</p>
      </div>
      <div class="flex items-center gap-3">
        <Button variant="outline" class="gap-2" @click="handlePreview">
          <SafeIcon name="Eye" :size="16" />
          预览主页
        </Button>
        <Button class="gap-2" :disabled="isSaving || isLoading" @click="handleSave">
          <SafeIcon :name="isSaving ? 'Loader2' : 'Save'" :size="16" :class="isSaving ? 'animate-spin' : ''" />
          保存设置
        </Button>
      </div>
    </div>

    <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>主页资料</CardTitle>
            <CardDescription>这些内容会展示在分享出去的商家主页顶部</CardDescription>
          </CardHeader>
          <CardContent class="grid gap-5 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="company_name">商家名称</Label>
              <Input id="company_name" v-model="form.company_name" placeholder="请输入商家名称" />
            </div>
            <div class="space-y-2">
              <Label for="company_logo">Logo 地址</Label>
              <Input id="company_logo" v-model="form.company_logo" placeholder="https://..." />
            </div>
            <div class="space-y-2 md:col-span-2">
              <Label for="company_desc">主页简介</Label>
              <Textarea id="company_desc" v-model="form.company_desc" class="min-h-24" placeholder="请输入主页简介" />
            </div>
            <div class="space-y-2">
              <Label for="contact_mobile">联系电话</Label>
              <Input id="contact_mobile" v-model="form.contact_mobile" placeholder="请输入联系电话" />
            </div>
            <div class="space-y-2">
              <Label for="contact_wechat">微信号</Label>
              <Input id="contact_wechat" v-model="form.contact_wechat" placeholder="请输入微信号" />
            </div>
            <div class="grid grid-cols-3 gap-3 md:col-span-2">
              <div class="space-y-2">
                <Label for="address_province">省份</Label>
                <Input id="address_province" v-model="form.address_province" />
              </div>
              <div class="space-y-2">
                <Label for="address_city">城市</Label>
                <Input id="address_city" v-model="form.address_city" />
              </div>
              <div class="space-y-2">
                <Label for="address_district">区县</Label>
                <Input id="address_district" v-model="form.address_district" />
              </div>
            </div>
            <div class="space-y-2 md:col-span-2">
              <Label for="address_detail">详细地址</Label>
              <Input id="address_detail" v-model="form.address_detail" placeholder="请输入详细地址" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>分享信息</CardTitle>
            <CardDescription>用于 PC 分享链接、小程序码和社交分享卡片</CardDescription>
          </CardHeader>
          <CardContent class="grid gap-5 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="home_share_title">分享标题</Label>
              <Input id="home_share_title" v-model="form.home_share_title" placeholder="默认使用商家名称" />
            </div>
            <div class="space-y-2">
              <Label for="home_share_image">分享封面地址</Label>
              <Input id="home_share_image" v-model="form.home_share_image" placeholder="https://..." />
            </div>
            <div class="space-y-2 md:col-span-2">
              <Label for="home_share_desc">分享描述</Label>
              <Textarea id="home_share_desc" v-model="form.home_share_desc" class="min-h-20" />
            </div>
            <div class="space-y-2">
              <Label for="home_service_name">联系按钮名称</Label>
              <Input id="home_service_name" v-model="form.home_service_name" placeholder="服务" />
            </div>
            <div class="space-y-2">
              <Label for="home_watermark_text">主页水印文字</Label>
              <Input id="home_watermark_text" v-model="form.home_watermark_text" placeholder="默认不显示自定义水印" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>权限设置</CardTitle>
            <CardDescription>控制访客进入主页和保存原图的权限</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-4">
              <div>
                <p class="text-sm font-medium">展示主页</p>
                <p class="text-xs text-muted-foreground">关闭后主页不对外展示</p>
              </div>
              <Switch v-model="form.is_show_home" />
            </div>
            <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-4">
              <div>
                <p class="text-sm font-medium">访客无需填写昵称</p>
                <p class="text-xs text-muted-foreground">开启后访客进入主页不再要求昵称</p>
              </div>
              <Switch v-model="form.visit_no_need_nickname" />
            </div>
            <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-4">
              <div>
                <p class="text-sm font-medium">访客无需授权手机号</p>
                <p class="text-xs text-muted-foreground">开启后访客进入主页不再要求手机号</p>
              </div>
              <Switch v-model="form.visit_no_need_mobile" />
            </div>
            <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-4">
              <div>
                <p class="text-sm font-medium">允许访客保存图片</p>
                <p class="text-xs text-muted-foreground">下载原图仍需要访客拥有会员状态</p>
              </div>
              <Switch v-model="form.visit_allow_save_pic" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>主页预览</CardTitle>
            <CardDescription>保存后将同步到分享主页</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-start gap-3">
              <img
                v-if="previewLogo"
                :src="previewLogo"
                alt=""
                class="h-16 w-16 rounded-lg border border-border object-cover"
              />
              <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg border border-border bg-muted">
                <SafeIcon name="Store" :size="24" class="text-muted-foreground" />
              </div>
              <div class="min-w-0">
                <p class="truncate text-base font-semibold">{{ form.company_name || '商户主页' }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ industryText }}</p>
              </div>
            </div>
            <p class="line-clamp-3 text-sm text-muted-foreground">{{ form.company_desc || '暂无简介' }}</p>
            <div class="rounded-lg border border-border bg-muted/30 p-3">
              <p class="truncate text-sm font-medium">{{ previewTitle }}</p>
              <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ previewDesc }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="rounded-md bg-primary/10 px-3 py-2 text-primary">
                {{ form.is_show_home ? '主页展示中' : '主页已隐藏' }}
              </div>
              <div class="rounded-md bg-muted px-3 py-2 text-muted-foreground">
                {{ form.visit_allow_save_pic ? '允许保存' : '禁止保存' }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
