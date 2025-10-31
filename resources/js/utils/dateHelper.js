/**
 * Convert date value to Y-m-d format (Laravel compatible)
 * Handles timezone issues by using local date components
 *
 * @param {Date|string|null} val - Date value to convert
 * @returns {string|null} - Date in Y-m-d format or null
 */
export function toYMD(val) {
  if (!val) return null
  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) return val
  const d = (val instanceof Date) ? val : new Date(val)
  if (isNaN(d)) return null
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/**
 * Format date for display (Vietnamese format)
 *
 * @param {Date|string|null} d - Date value
 * @returns {string} - Formatted date or empty string
 */
export function formatDate(d) {
  if (!d) return ''
  try {
    return new Date(d).toLocaleDateString('vi-VN')
  } catch {
    return d
  }
}
