import type { TemplateData } from './TemplateData'

export const templateDataList: TemplateData[] = [
  {
    id: 'template-hr-onboarding',
    name: 'HR 入职资料收集',
    industry: 'hr',
    status: 'official',
    isOfficial: true,
    defaultFieldCount: 3,
    defaultMaterialCount: 3,
    useCount: 128,
    description: '适合新员工入职资料、合同与身份信息收集。',
    createdAt: '2025-11-01T02:00:00Z',
    updatedAt: '2026-07-02T06:00:00Z'
  },
  {
    id: 'template-edu-homework',
    name: '教育作业材料收集',
    industry: 'education',
    status: 'official',
    isOfficial: true,
    defaultFieldCount: 2,
    defaultMaterialCount: 2,
    useCount: 84,
    description: '适合教师收集作业图片、说明文档和家长确认信息。',
    createdAt: '2025-10-10T02:00:00Z',
    updatedAt: '2026-06-28T08:00:00Z'
  },
  {
    id: 'template-fin-expense',
    name: '财务报销凭证归集',
    industry: 'finance',
    status: 'official',
    isOfficial: true,
    defaultFieldCount: 3,
    defaultMaterialCount: 3,
    useCount: 76,
    description: '适合统一收集报销单、发票与付款截图。',
    createdAt: '2025-12-05T02:00:00Z',
    updatedAt: '2026-07-01T08:30:00Z'
  },
  {
    id: 'template-photo-delivery',
    name: '摄影交付任务',
    industry: 'photography',
    status: 'official',
    isOfficial: true,
    defaultFieldCount: 1,
    defaultMaterialCount: 2,
    useCount: 64,
    description: '适合向客户发送成片、清单和预览链接。',
    createdAt: '2026-01-15T02:00:00Z',
    updatedAt: '2026-06-30T04:00:00Z'
  },
  {
    id: 'template-supplier-docs',
    name: '供应商资质收集',
    industry: 'procurement',
    status: 'official',
    isOfficial: true,
    defaultFieldCount: 3,
    defaultMaterialCount: 3,
    useCount: 51,
    description: '适合收集供应商证照、联系人与项目资料。',
    createdAt: '2026-02-20T02:00:00Z',
    updatedAt: '2026-07-03T05:30:00Z'
  },
  {
    id: 'template-custom-001',
    name: '行政物资申请',
    industry: 'procurement',
    status: 'custom',
    isOfficial: false,
    defaultFieldCount: 2,
    defaultMaterialCount: 1,
    useCount: 19,
    description: '团队自定义的物资申请与审批模板。',
    createdAt: '2026-03-12T04:00:00Z',
    updatedAt: '2026-07-06T09:30:00Z'
  }
]

export class TemplateService {
  static getAll(): TemplateData[] {
    return templateDataList
  }

  static getById(id: string): TemplateData | undefined {
    return templateDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'industry' | 'status' | 'isOfficial', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TemplateData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return templateDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.name.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = key === 'isOfficial' ? String(item.isOfficial) : (item as any)[key]
          return Array.isArray(val) ? val.includes(String(itemVal)) : String(itemVal) === String(val)
        })
        return matchKeyword && matchFilter
      })
      .sort((a, b) => {
        if (!sortKey) return 0
        const aVal = (a as any)[sortKey]
        const bVal = (b as any)[sortKey]
        if (aVal === bVal) return 0
        const result = aVal > bVal ? 1 : -1
        return sortDirection === 'asc' ? result : -result
      })
  }

  static loadPersisted(): TemplateData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('templateDataList')
    return raw ? (JSON.parse(raw) as TemplateData[]) : null
  }

  static savePersisted(items: TemplateData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('templateDataList', JSON.stringify(items))
  }
}
