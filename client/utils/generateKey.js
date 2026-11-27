/**
 * Генерирует криптографически стойкий ключ (32 байта / 256 бит) для HS256 / HMAC-SHA256.
 *
 * @param {boolean} asBase64 - Вернуть в формате Base64 вместо Hex.
 * @returns {string}
 */
function generateKey(asBase64 = false){
 // Создаем массив на 32 байта (256 бит)
    const bytes = new Uint8Array(32);
    
    // Заполняем криптографически стойкими случайными числами
    crypto.getRandomValues(bytes);

    if (asBase64) {
        // Преобразуем байты в строку Base64
        const binString = Array.from(bytes, (byte) => String.fromCharCode(byte)).join('');
        return btoa(binString);
    }

    // Преобразуем каждый байт в 2-значный hex
    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}

export {generateKey}
