export const QUICK_SEND_USER_MAX_FILE_SIZE_MB = 500
export const QUICK_SEND_ANONYMOUS_MAX_FILE_SIZE_MB = 200
export const MAX_FILES_PER_BATCH = 50

export const BLOCKED_FILE_EXTENSIONS = [
  'php',
  'phtml',
  'phar',
  'php3',
  'php4',
  'php5',
  'asp',
  'aspx',
  'jsp',
  'jspx',
  'exe',
  'dll',
  'bat',
  'cmd',
  'com',
  'msi',
  'sh',
  'bash',
  'zsh',
  'ps1',
  'vbs',
  'js',
  'mjs',
  'jar',
  'war',
  'ear',
  'htaccess',
  'htpasswd',
  'html',
  'htm',
  'svg',
  'swf',
  'scr',
  'lnk',
  'reg',
  'apk',
  'ipa',
  'dmg',
  'pkg',
  'deb',
  'rpm',
]

const BLOCKED_EXTENSION_SET = new Set(BLOCKED_FILE_EXTENSIONS)
const DOUBLE_EXTENSION_PATTERN = /\.(php[0-9]?|phtml|phar|asp|aspx|jsp|jspx)(\.|$)/i
const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'])

export interface FileBatchValidationOptions {
  maxSizeMb: number
  maxFiles?: number
  existingCount?: number
  imageOnly?: boolean
  allowedExtensions?: string[]
  materialName?: string
}

export interface FileBatchValidationResult {
  ok: boolean
  message?: string
}

export const getFileExtension = (fileName: string) => {
  const cleanName = fileName.trim().toLowerCase()
  const dotIndex = cleanName.lastIndexOf('.')
  return dotIndex >= 0 ? cleanName.slice(dotIndex + 1) : ''
}

export const isBlockedFileName = (fileName: string) => {
  const cleanName = fileName.trim().toLowerCase()
  const extension = getFileExtension(cleanName)
  return BLOCKED_EXTENSION_SET.has(extension) || DOUBLE_EXTENSION_PATTERN.test(cleanName)
}

export const normalizeAllowedExtensions = (extensions: string[] = []) => {
  return extensions
    .map((item) => item.trim().toLowerCase().replace(/^\./, ''))
    .filter((item) => item && !item.includes('/'))
}

export const validateFileBatch = (
  files: FileList | File[],
  options: FileBatchValidationOptions,
): FileBatchValidationResult => {
  const fileArray = Array.from(files)
  const maxFiles = options.maxFiles ?? MAX_FILES_PER_BATCH
  const existingCount = options.existingCount ?? 0
  const allowedExtensions = normalizeAllowedExtensions(options.allowedExtensions)
  const materialPrefix = options.materialName ? `${options.materialName}: ` : ''

  if (fileArray.length === 0) {
    return { ok: false, message: '请选择上传文件' }
  }

  if (existingCount + fileArray.length > maxFiles) {
    return { ok: false, message: `单次最多上传 ${maxFiles} 个文件，请分批处理` }
  }

  for (const file of fileArray) {
    const extension = getFileExtension(file.name)

    if (isBlockedFileName(file.name)) {
      return { ok: false, message: `${materialPrefix}暂不支持上传该类型文件: "${file.name}"` }
    }

    if (options.imageOnly && !(file.type.startsWith('image/') || IMAGE_EXTENSIONS.has(extension))) {
      return { ok: false, message: `图片发送只支持图片文件: "${file.name}"` }
    }

    if (allowedExtensions.length > 0 && extension && !allowedExtensions.includes(extension)) {
      return { ok: false, message: `${materialPrefix}文件格式不符合要求: "${file.name}"` }
    }

    if (options.maxSizeMb > 0 && file.size > options.maxSizeMb * 1024 * 1024) {
      return { ok: false, message: `${materialPrefix}文件 "${file.name}" 超过最大限制 ${options.maxSizeMb}MB` }
    }
  }

  return { ok: true }
}
