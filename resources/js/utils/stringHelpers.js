/**
 * Trim all string values in an object recursively
 * This is useful for form data before submitting to backend
 *
 * Usage:
 * import { trimStringValues } from '@/utils/stringHelpers'
 *
 * const formData = { name: '  John  ', email: ' john@example.com ' }
 * const trimmedData = trimStringValues(formData)
 * // Result: { name: 'John', email: 'john@example.com' }
 *
 * Note: This function is particularly useful when you remove v-model.trim
 * to fix Vietnamese input issues (IME). Instead of trimming on input,
 * trim before validation/submission.
 *
 * @param {Object|Array|any} data - The data to trim
 * @returns {Object|Array|any} - The trimmed data
 */
export function trimStringValues(data) {
    if (data === null || data === undefined) {
        return data;
    }

    // Handle arrays
    if (Array.isArray(data)) {
        return data.map(item => trimStringValues(item));
    }

    // Handle objects
    if (typeof data === 'object' && !(data instanceof Date)) {
        const trimmed = {};
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                trimmed[key] = trimStringValues(data[key]);
            }
        }
        return trimmed;
    }

    // Handle strings
    if (typeof data === 'string') {
        return data.trim();
    }

    // Return other types as-is
    return data;
}
