/**
 * Formats Turkish phone number with mask: 0XXX XXX XX XX
 * @param value - Raw phone input value
 * @returns Formatted phone number string
 */
export function formatPhoneNumber(value: string): string {
  // Remove all non-digit characters
  const digits = value.replace(/\D/g, '');
  
  // Limit to 11 digits (0 + 10 digits)
  const limitedDigits = digits.slice(0, 11);
  
  // Apply mask: 0XXX XXX XX XX
  if (limitedDigits.length === 0) return '';
  if (limitedDigits.length <= 1) return limitedDigits;
  if (limitedDigits.length <= 4) return `${limitedDigits.slice(0, 1)} ${limitedDigits.slice(1)}`;
  if (limitedDigits.length <= 7) return `${limitedDigits.slice(0, 1)} ${limitedDigits.slice(1, 4)} ${limitedDigits.slice(4)}`;
  if (limitedDigits.length <= 9) return `${limitedDigits.slice(0, 1)} ${limitedDigits.slice(1, 4)} ${limitedDigits.slice(4, 7)} ${limitedDigits.slice(7)}`;
  return `${limitedDigits.slice(0, 1)} ${limitedDigits.slice(1, 4)} ${limitedDigits.slice(4, 7)} ${limitedDigits.slice(7, 9)} ${limitedDigits.slice(9)}`;
}

/**
 * Removes formatting from phone number (removes spaces)
 * @param value - Formatted phone number
 * @returns Raw phone number without formatting
 */
export function unformatPhoneNumber(value: string): string {
  return value.replace(/\D/g, '');
}

/**
 * Validates and formats TC Kimlik number
 * Only allows numeric digits, maximum 11 digits
 * @param value - Raw TC Kimlik input value
 * @returns Formatted TC Kimlik number (only digits, max 11)
 */
export function formatTCKimlik(value: string): string {
  // Remove all non-digit characters
  const digits = value.replace(/\D/g, '');
  
  // Limit to 11 digits
  return digits.slice(0, 11);
}

/**
 * Formats number in Turkish format: 4.000 TL (dot as thousand separator, no decimals)
 * @param value - Number to format
 * @returns Formatted string like "4.000 TL"
 */
export function formatTurkishCurrency(value: number | string): string {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '0 TL';
  
  // Round to nearest integer
  const rounded = Math.round(num);
  
  // Format with dot as thousand separator
  return rounded.toLocaleString('tr-TR') + ' TL';
}

/**
 * Formats number with Turkish locale (dot as thousand separator)
 * @param value - Number to format
 * @param decimals - Number of decimal places (default: 0)
 * @returns Formatted string
 */
export function formatTurkishNumber(value: number | string, decimals: number = 0): string {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '0';
  
  return num.toLocaleString('tr-TR', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

/**
 * Validates email format
 * @param value - Email string to validate
 * @returns true if valid email format, false otherwise
 */
export function isValidEmail(value: string): boolean {
  if (!value || value.trim() === '') return false;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(value.trim());
}

/**
 * Validates Turkish phone number format
 * Accepts: 0XXXXXXXXXX (11 digits starting with 0)
 * @param value - Phone number string to validate (can be formatted or unformatted)
 * @returns true if valid phone format, false otherwise
 */
export function isValidPhoneNumber(value: string): boolean {
  if (!value || value.trim() === '') return false;
  // Remove all non-digit characters
  const digits = value.replace(/\D/g, '');
  // Turkish phone numbers should be 11 digits starting with 0
  return /^0\d{10}$/.test(digits);
}
