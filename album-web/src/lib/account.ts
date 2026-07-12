export const isVipMember = (user: any = {}) => {
  const gradeLevel = Number(user?.grade_level ?? user?.gradeLevel ?? user?.vip_grade ?? user?.vipGrade ?? 0)
  const rawEndTime = user?.end_time ?? user?.endTime ?? user?.vip_end_time ?? user?.vipEndTime ?? user?.expire_time ?? user?.expireTime ?? 0
  let endTime = Number(rawEndTime || 0)
  if (!endTime && typeof rawEndTime === 'string' && rawEndTime.trim()) {
    const parsed = Date.parse(rawEndTime)
    endTime = Number.isNaN(parsed) ? 0 : Math.floor(parsed / 1000)
  }
  return Number.isFinite(gradeLevel) && gradeLevel > 0 && (!endTime || endTime > Math.floor(Date.now() / 1000))
}

export const formatBytes = (bytes: number, fractionDigits = 2) => {
  const value = Number(bytes || 0)
  if (!Number.isFinite(value) || value <= 0) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1)
  const amount = value / Math.pow(1024, index)
  const digits = index === 0 ? 0 : fractionDigits
  return `${amount.toFixed(digits)} ${units[index]}`
}

export const parseSpaceToBytes = (value: any) => {
  if (typeof value === 'number') return value
  const raw = String(value || '').trim()
  if (!raw) return 0
  const match = raw.match(/^([\d.]+)\s*(B|KB|MB|GB|G|TB|T|M)?$/i)
  if (!match) return Number(raw) || 0
  const amount = Number(match[1])
  if (!Number.isFinite(amount)) return 0
  const unit = (match[2] || 'B').toUpperCase()
  if (unit === 'T' || unit === 'TB') return amount * 1024 * 1024 * 1024 * 1024
  if (unit === 'G' || unit === 'GB') return amount * 1024 * 1024 * 1024
  if (unit === 'M' || unit === 'MB') return amount * 1024 * 1024
  if (unit === 'KB') return amount * 1024
  return amount
}
