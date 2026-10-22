const MAX_INTEGER_DIGITS = 13;
const MAX_DECIMAL_DIGITS = 2;

/**
 * Nilai yang disimpan di form: digit saja, titik desimal opsional, tanpa pemisah ribuan.
 * Titik di akhir ("1000.") berarti pengguna baru mulai menulis desimal.
 */
export function parseRupiahInput(input: string): string {
    const cleaned = input.replace(/[^\d.,]/g, '');

    if (cleaned === '') {
        return '';
    }

    const commaAt = cleaned.indexOf(',');

    if (commaAt !== -1) {
        return joinAmount(
            cleaned.slice(0, commaAt).replace(/\./g, ''),
            cleaned.slice(commaAt + 1).replace(/[.,]/g, ''),
        );
    }

    if (cleaned.endsWith('.')) {
        return joinAmount(cleaned.slice(0, -1).replace(/\./g, ''), '');
    }

    return joinAmount(cleaned.replace(/\./g, ''), null);
}

/** Tampilkan nilai form sebagai rupiah, misalnya "1000000" menjadi "1.000.000". */
export function formatRupiah(raw: string): string {
    if (!raw) {
        return '';
    }

    const hasDecimal = raw.includes('.');
    const [intPart, decPart = ''] = raw.split('.');
    const grouped = groupThousands(intPart || '0');

    if (!hasDecimal || decPart === '00') {
        return grouped;
    }

    return `${grouped},${decPart}`;
}

function joinAmount(intRaw: string, decRaw: string | null): string {
    let intDigits = intRaw.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    if (intDigits === '') {
        intDigits = decRaw === null ? '' : '0';
    }

    if (intDigits === '') {
        return '';
    }

    intDigits = intDigits.slice(0, MAX_INTEGER_DIGITS);

    if (decRaw === null) {
        return intDigits;
    }

    return `${intDigits}.${decRaw.replace(/\D/g, '').slice(0, MAX_DECIMAL_DIGITS)}`;
}

function groupThousands(intPart: string): string {
    return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
