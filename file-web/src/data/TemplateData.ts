export interface TemplateData {
  id: string
  name: string
  industry: 'education' | 'hr' | 'finance' | 'photography' | 'procurement'
  status: 'official' | 'custom' | 'archived'
  isOfficial: boolean
  defaultFieldCount: number
  defaultMaterialCount: number
  useCount: number
  description: string
  createdAt: string
  updatedAt: string
}
