import type {
  MissingCheckResultData,
  ReSubmissionNoticeData,
  ReviewLogData,
  SubmissionData,
  SubmissionFileData,
  SubmissionReceiptData
} from './SubmissionData'

export interface SubmissionFileVO {
  id: string
  fileName: string
  fileSizeMb: number
  fileType: string
  previewUrl?: string
  status: SubmissionFileData['status']
  uploadedAt: string
}

export interface ReviewLogVO {
  id: string
  reviewerName: string
  action: ReviewLogData['action']
  result: ReviewLogData['result']
  remark: string
  createdAt: string
}

export interface SubmissionDetailVO {
  id: string
  collectionTaskId: string
  submitterName: string
  submitterPhone: string
  submitterDepartment: string
  status: SubmissionData['status']
  reviewState: SubmissionData['reviewState']
  hasMissing: boolean
  fileCount: number
  submittedAt: string
  updatedAt: string
  files: SubmissionFileVO[]
  reviewLogs: ReviewLogVO[]
  missingCheck: MissingCheckResultData | undefined
  resubmissionNotice: ReSubmissionNoticeData | undefined
}

export interface SubmissionReceiptVO {
  id: string
  submissionId: string
  receiptNumber: string
  submittedAt: string
  materialSummary: string
}

export const submissionDataList: SubmissionData[] = [
  {
    id: 'submission-001',
    collectionTaskId: 'task-001',
    submitterName: '林子悦',
    submitterPhone: '13800001234',
    submitterDepartment: '市场部',
    status: 'pending_review',
    reviewState: 'waiting',
    hasMissing: false,
    fileCount: 3,
    submittedAt: '2026-07-08T08:10:00Z',
    updatedAt: '2026-07-08T08:10:00Z'
  },
  {
    id: 'submission-002',
    collectionTaskId: 'task-001',
    submitterName: '周启明',
    submitterPhone: '13900004567',
    submitterDepartment: '产品部',
    status: 'need_resubmission',
    reviewState: 'rejected',
    hasMissing: true,
    fileCount: 2,
    submittedAt: '2026-07-07T10:30:00Z',
    updatedAt: '2026-07-08T06:30:00Z'
  },
  {
    id: 'submission-003',
    collectionTaskId: 'task-003',
    submitterName: '王沐阳',
    submitterPhone: '13700007890',
    submitterDepartment: '三年级二班',
    status: 'submitted',
    reviewState: 'waiting',
    hasMissing: true,
    fileCount: 1,
    submittedAt: '2026-07-08T07:40:00Z',
    updatedAt: '2026-07-08T07:40:00Z'
  },
  {
    id: 'submission-004',
    collectionTaskId: 'task-004',
    submitterName: '李欣然',
    submitterPhone: '13600001111',
    submitterDepartment: '研发部',
    status: 'approved',
    reviewState: 'approved',
    hasMissing: false,
    fileCount: 4,
    submittedAt: '2026-07-06T09:00:00Z',
    updatedAt: '2026-07-07T09:30:00Z'
  }
]

export const submissionFileDataList: SubmissionFileData[] = [
  {
    id: 'sfile-001',
    submissionId: 'submission-001',
    fileName: '身份证正面.jpg',
    fileSizeMb: 2.3,
    fileType: 'jpg',
    previewUrl: 'https://example.com/preview/id-front',
    status: 'uploaded',
    uploadedAt: '2026-07-08T08:05:00Z'
  },
  {
    id: 'sfile-002',
    submissionId: 'submission-001',
    fileName: '身份证反面.jpg',
    fileSizeMb: 2.1,
    fileType: 'jpg',
    previewUrl: 'https://example.com/preview/id-back',
    status: 'uploaded',
    uploadedAt: '2026-07-08T08:06:00Z'
  },
  {
    id: 'sfile-003',
    submissionId: 'submission-001',
    fileName: '合同签署页.pdf',
    fileSizeMb: 4.8,
    fileType: 'pdf',
    previewUrl: 'https://example.com/preview/contract',
    status: 'uploaded',
    uploadedAt: '2026-07-08T08:08:00Z'
  },
  {
    id: 'sfile-004',
    submissionId: 'submission-002',
    fileName: '身份证正面.jpg',
    fileSizeMb: 2.5,
    fileType: 'jpg',
    previewUrl: 'https://example.com/preview/id-front-2',
    status: 'uploaded',
    uploadedAt: '2026-07-07T10:20:00Z'
  },
  {
    id: 'sfile-005',
    submissionId: 'submission-002',
    fileName: '合同签署页.pdf',
    fileSizeMb: 4.2,
    fileType: 'pdf',
    previewUrl: 'https://example.com/preview/contract-2',
    status: 'uploaded',
    uploadedAt: '2026-07-07T10:25:00Z'
  },
  {
    id: 'sfile-006',
    submissionId: 'submission-003',
    fileName: '作业照片1.jpg',
    fileSizeMb: 3.1,
    fileType: 'jpg',
    previewUrl: 'https://example.com/preview/homework-1',
    status: 'uploaded',
    uploadedAt: '2026-07-08T07:38:00Z'
  },
  {
    id: 'sfile-007',
    submissionId: 'submission-004',
    fileName: '报销单.pdf',
    fileSizeMb: 1.4,
    fileType: 'pdf',
    previewUrl: 'https://example.com/preview/expense',
    status: 'uploaded',
    uploadedAt: '2026-07-06T08:52:00Z'
  }
]

export const reviewLogDataList: ReviewLogData[] = [
  {
    id: 'rlog-001',
    submissionId: 'submission-001',
    reviewerId: 'user-001',
    reviewerName: '管理员',
    action: 'comment',
    result: 'pending',
    remark: '材料已收到，等待复核。',
    createdAt: '2026-07-08T08:20:00Z'
  },
  {
    id: 'rlog-002',
    submissionId: 'submission-002',
    reviewerId: 'user-001',
    reviewerName: '管理员',
    action: 'reject',
    result: 'resubmission_needed',
    remark: '缺少身份证反面，请补交。',
    createdAt: '2026-07-08T06:20:00Z'
  },
  {
    id: 'rlog-003',
    submissionId: 'submission-004',
    reviewerId: 'user-001',
    reviewerName: '管理员',
    action: 'approve',
    result: 'approved',
    remark: '检查通过，准予归档。',
    createdAt: '2026-07-07T09:30:00Z'
  }
]

export const missingCheckResultDataList: MissingCheckResultData[] = [
  {
    id: 'mcheck-001',
    submissionId: 'submission-001',
    missingNames: [],
    summary: '材料齐全',
    checkedAt: '2026-07-08T08:12:00Z',
    state: 'passing'
  },
  {
    id: 'mcheck-002',
    submissionId: 'submission-002',
    missingNames: ['身份证反面'],
    summary: '缺少 1 项必传材料',
    checkedAt: '2026-07-08T06:18:00Z',
    state: 'missing'
  },
  {
    id: 'mcheck-003',
    submissionId: 'submission-003',
    missingNames: ['说明文档'],
    summary: '当前提交内容不完整',
    checkedAt: '2026-07-08T07:42:00Z',
    state: 'warning'
  }
]

export const reSubmissionNoticeDataList: ReSubmissionNoticeData[] = [
  {
    id: 'resub-001',
    submissionId: 'submission-002',
    reason: '缺少身份证反面，请补交后再提交。',
    sentAt: '2026-07-08T06:22:00Z',
    sentBy: '管理员'
  }
]

export const submissionReceiptDataList: SubmissionReceiptData[] = [
  {
    id: 'receipt-001',
    submissionId: 'submission-001',
    receiptNumber: 'RX-20260708-0001',
    submittedAt: '2026-07-08T08:10:00Z',
    materialSummary: '身份证正反面、合同签署页'
  },
  {
    id: 'receipt-002',
    submissionId: 'submission-003',
    receiptNumber: 'RX-20260708-0003',
    submittedAt: '2026-07-08T07:40:00Z',
    materialSummary: '作业照片'
  }
]

export class SubmissionService {
  static getAll(): SubmissionData[] {
    return submissionDataList
  }

  static getById(id: string): SubmissionData | undefined {
    return submissionDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'collectionTaskId' | 'reviewState' | 'hasMissing' | 'submitterDepartment', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): SubmissionData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return submissionDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.submitterName.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = key === 'hasMissing' ? String(item.hasMissing) : (item as any)[key]
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

  static getDetailVOById(id: string): SubmissionDetailVO | undefined {
    const item = this.getById(id)
    if (!item) return undefined
    return {
      id: item.id,
      collectionTaskId: item.collectionTaskId,
      submitterName: item.submitterName,
      submitterPhone: item.submitterPhone,
      submitterDepartment: item.submitterDepartment,
      status: item.status,
      reviewState: item.reviewState,
      hasMissing: item.hasMissing,
      fileCount: item.fileCount,
      submittedAt: item.submittedAt,
      updatedAt: item.updatedAt,
      files: submissionFileDataList
        .filter((file) => file.submissionId === item.id)
        .map((file) => ({
          id: file.id,
          fileName: file.fileName,
          fileSizeMb: file.fileSizeMb,
          fileType: file.fileType,
          previewUrl: file.previewUrl,
          status: file.status,
          uploadedAt: file.uploadedAt
        })),
      reviewLogs: reviewLogDataList
        .filter((log) => log.submissionId === item.id)
        .map((log) => ({
          id: log.id,
          reviewerName: log.reviewerName,
          action: log.action,
          result: log.result,
          remark: log.remark,
          createdAt: log.createdAt
        })),
      missingCheck: missingCheckResultDataList.find((m) => m.submissionId === item.id),
      resubmissionNotice: reSubmissionNoticeDataList.find((r) => r.submissionId === item.id)
    }
  }

  static getSubmissionFileVOListBySubmissionId(submissionId: string): SubmissionFileVO[] {
    return submissionFileDataList
      .filter((file) => file.submissionId === submissionId)
      .map((file) => ({
        id: file.id,
        fileName: file.fileName,
        fileSizeMb: file.fileSizeMb,
        fileType: file.fileType,
        previewUrl: file.previewUrl,
        status: file.status,
        uploadedAt: file.uploadedAt
      }))
  }

  static getReceiptVOById(id: string): SubmissionReceiptVO | undefined {
    return submissionReceiptDataList.find((item) => item.id === id)
  }

  static loadPersisted(): SubmissionData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('submissionDataList')
    return raw ? (JSON.parse(raw) as SubmissionData[]) : null
  }

  static savePersisted(items: SubmissionData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('submissionDataList', JSON.stringify(items))
  }
}
