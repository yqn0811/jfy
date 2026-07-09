import type {
  MissingCheckResultData,
  PublicSubmissionTaskData,
  PublicSubmissionTaskVO,
  ReSubmissionNoticeData,
  SubmissionDraftData,
  SubmissionFileData
} from './PublicSubmissionData'

export interface SubmissionReceiptVO {
  id: string
  submissionId: string
  receiptNumber: string
  submittedAt: string
  materialSummary: string
}

export const publicSubmissionTaskDataList: PublicSubmissionTaskData[] = [
  {
    id: 'public-task-001',
    taskId: 'task-001',
    taskName: 'HR 入职资料收集 - 7 月批次',
    organizationName: '织序传输助手演示团队',
    description: '请在截止时间前完成材料上传，缺少项会在提交后提示。',
    dueAt: '2026-07-09T12:00:00Z',
    accessCodeRequired: false,
    submitterFieldIds: ['field-001', 'field-002', 'field-003'],
    materialItemIds: ['mat-001', 'mat-002', 'mat-003'],
    status: 'active'
  },
  {
    id: 'public-task-002',
    taskId: 'task-003',
    taskName: '暑期作业材料收集',
    organizationName: '星河教育资料组',
    description: '提交作业照片与说明文档，支持补交。',
    dueAt: '2026-07-08T22:00:00Z',
    accessCodeRequired: true,
    accessCode: 'EDU-2026',
    submitterFieldIds: ['field-004', 'field-005'],
    materialItemIds: ['mat-004', 'mat-005'],
    status: 'active'
  },
  {
    id: 'public-task-003',
    taskId: 'task-005',
    taskName: '合同签署页补交通知',
    organizationName: '织序传输助手演示团队',
    description: '请根据退回原因重新补交材料。',
    dueAt: '2026-07-06T12:00:00Z',
    accessCodeRequired: true,
    accessCode: 'RETRY-05',
    submitterFieldIds: ['field-001'],
    materialItemIds: ['mat-001', 'mat-003'],
    status: 'expired'
  }
]

export const submissionDraftDataList: SubmissionDraftData[] = [
  {
    id: 'pdraft-001',
    taskId: 'task-001',
    submitterName: '林子悦',
    submitterPhone: '13800001234',
    submitterDepartment: '市场部',
    savedAt: '2026-07-08T08:00:00Z',
    stepIndex: 2
  }
]

export const publicSubmissionFileDataList: SubmissionFileData[] = [
  {
    id: 'pfile-001',
    submissionId: 'public-submission-001',
    fileName: '身份证正面.jpg',
    fileSizeMb: 2.3,
    fileType: 'jpg',
    previewUrl: 'https://example.com/preview/id-front',
    status: 'uploaded',
    uploadedAt: '2026-07-08T08:05:00Z'
  }
]

export const publicMissingCheckResultDataList: MissingCheckResultData[] = [
  {
    id: 'pmcheck-001',
    submissionId: 'public-submission-001',
    missingNames: [],
    summary: '材料齐全',
    checkedAt: '2026-07-08T08:12:00Z',
    state: 'passing'
  }
]

export const publicReSubmissionNoticeDataList: ReSubmissionNoticeData[] = [
  {
    id: 'presub-001',
    submissionId: 'public-submission-002',
    reason: '请补交合同签署页。',
    sentAt: '2026-07-08T06:22:00Z',
    sentBy: '管理员'
  }
]

export class PublicSubmissionService {
  static getTaskVOById(taskId: string): PublicSubmissionTaskVO | undefined {
    const item = publicSubmissionTaskDataList.find((task) => task.taskId === taskId)
    if (!item) return undefined
    return {
      id: item.id,
      taskId: item.taskId,
      taskName: item.taskName,
      organizationName: item.organizationName,
      description: item.description,
      dueAt: item.dueAt,
      accessCodeRequired: item.accessCodeRequired,
      submitterFields: item.submitterFieldIds,
      materials: item.materialItemIds,
      status: item.status
    }
  }

  static getDraftByTaskId(taskId: string): SubmissionDraftData | undefined {
    return submissionDraftDataList.find((item) => item.taskId === taskId)
  }

  static loadPersisted(): SubmissionDraftData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('submissionDraftDataList')
    return raw ? (JSON.parse(raw) as SubmissionDraftData[]) : null
  }

  static savePersisted(items: SubmissionDraftData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('submissionDraftDataList', JSON.stringify(items))
  }

  static submit(taskId: string): SubmissionReceiptVO | undefined {
    const task = publicSubmissionTaskDataList.find((item) => item.taskId === taskId)
    if (!task) return undefined
    return {
      id: `receipt-${taskId}`,
      submissionId: `submission-${taskId}`,
      receiptNumber: 'RX-20260708-1001',
      submittedAt: '2026-07-08T09:30:00Z',
      materialSummary: '已提交所需材料'
    }
  }
}
