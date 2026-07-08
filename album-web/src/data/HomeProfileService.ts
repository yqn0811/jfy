import { HomeProfileData } from './HomeProfileData'

export const homeProfileDataList: HomeProfileData[] = [
  {
    id: 'home_001',
    companyName: '云织家纺',
    logoUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/404cfc00-741d-4356-a857-5af6f1fe0cb8.png',
    industryTag: '家纺面料',
    intro: '专注家纺面料与成品展示，提供稳定的产品资料维护与分享能力。',
    productCount: 18,
    contactServiceName: '服务',
    contactPhone: '021-5678-9001',
    wechatId: 'yunzhi-home',
    region: '浙江省 / 杭州市 / 余杭区',
    address: '文一西路 998 号云纺大厦 8F',
    isPublic: true,
    shareTitle: '云织家纺产品主页',
    shareDescription: '展示家纺产品、分类与详情图。',
    shareCoverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/3bef1daf-0218-4591-9a2c-07dc6ad5721f.png',
    ownerUserId: 'user_001',
    createdAt: '2026-01-10 09:00:00',
    updatedAt: '2026-07-01 10:20:00'
  }
]

export class HomeProfileService {
  static getAll(): HomeProfileData[] {
    return homeProfileDataList
  }

  static getById(id: string): HomeProfileData | undefined {
    return homeProfileDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): HomeProfileData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = homeProfileDataList.filter(item => {
      const matchKeyword = !keyword || [item.companyName, item.intro, item.industryTag].some(val => val.toLowerCase().includes(keyword))
      const matchFilter = Object.entries(filter).every(([key, val]) => {
        if (val === undefined) return true
        const itemVal = (item as any)[key]
        return Array.isArray(val) ? val.includes(itemVal) : itemVal === val
      })
      return matchKeyword && matchFilter
    })
    const sortKey = params.sortKey
    if (!sortKey) return list
    return [...list].sort((a, b) => {
      const av = (a as any)[sortKey]
      const bv = (b as any)[sortKey]
      if (av === bv) return 0
      const direction = params.sortDirection === 'desc' ? -1 : 1
      return av > bv ? direction : -direction
    })
  }

  static loadPersisted(): HomeProfileData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('homeProfileDataList')
    return raw ? JSON.parse(raw) as HomeProfileData[] : null
  }

  static savePersisted(items: HomeProfileData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('homeProfileDataList', JSON.stringify(items))
  }
}