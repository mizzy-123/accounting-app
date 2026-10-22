import {
    useRef,
    type ChangeEvent,
    type ClipboardEvent,
    type ComponentProps,
    type KeyboardEvent,
} from 'react';
import { Input } from '@/components/ui/input';
import { formatRupiah, parseRupiahInput } from '@/lib/rupiah';
import { cn } from '@/lib/utils';

type MoneyInputProps = Omit<
    ComponentProps<typeof Input>,
    'type' | 'value' | 'onChange' | 'inputMode'
> & {
    value: string;
    onValueChange: (value: string) => void;
};

export function MoneyInput({
    value,
    onValueChange,
    className,
    onKeyDown,
    onPaste,
    ...props
}: MoneyInputProps) {
    const ref = useRef<HTMLInputElement>(null);

    function commit(display: string, digitsBefore: number) {
        const next = parseRupiahInput(display);

        onValueChange(next);

        const formatted = formatRupiah(next);
        const caret = caretFromDigits(
            formatted,
            digitsBefore,
            next.endsWith('.'),
        );

        requestAnimationFrame(() => {
            const node = ref.current;

            if (!node) {
                return;
            }

            if (node.value !== formatted) {
                node.value = formatted;
            }

            node.setSelectionRange(caret, caret);
        });
    }

    function handleChange(event: ChangeEvent<HTMLInputElement>) {
        const input = event.target;
        const cursor = input.selectionStart ?? input.value.length;

        commit(input.value, countDigits(input.value.slice(0, cursor)));
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        onKeyDown?.(event);

        if (event.defaultPrevented || (event.key !== '.' && event.key !== ',')) {
            return;
        }

        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        event.preventDefault();

        const input = event.currentTarget;

        if (input.value.includes(',')) {
            return;
        }

        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        const nextDisplay = `${input.value.slice(0, start)},${input.value.slice(end)}`;

        commit(nextDisplay, countDigits(nextDisplay.slice(0, start + 1)));
    }

    function handlePaste(event: ClipboardEvent<HTMLInputElement>) {
        onPaste?.(event);

        if (event.defaultPrevented) {
            return;
        }

        const pasted = event.clipboardData.getData('text').trim();

        if (!/^\d+\.\d{1,2}$/.test(pasted)) {
            return;
        }

        event.preventDefault();

        const input = event.currentTarget;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        const inserted = pasted.replace('.', ',');
        const nextDisplay = `${input.value.slice(0, start)}${inserted}${input.value.slice(end)}`;

        commit(
            nextDisplay,
            countDigits(nextDisplay.slice(0, start + inserted.length)),
        );
    }

    return (
        <Input
            {...props}
            ref={ref}
            type="text"
            inputMode="decimal"
            autoComplete="off"
            spellCheck={false}
            className={cn('tabular-nums', className)}
            value={formatRupiah(value)}
            onChange={handleChange}
            onKeyDown={handleKeyDown}
            onPaste={handlePaste}
        />
    );
}

function countDigits(value: string): number {
    return value.replace(/\D/g, '').length;
}

function caretFromDigits(
    formatted: string,
    digitsBefore: number,
    decimalPending: boolean,
): number {
    if (digitsBefore <= 0) {
        return 0;
    }

    let seen = 0;

    for (let index = 0; index < formatted.length; index++) {
        if (!/\d/.test(formatted[index])) {
            continue;
        }

        seen++;

        if (seen !== digitsBefore) {
            continue;
        }

        const position = index + 1;

        if (decimalPending && formatted[position] === ',') {
            return position + 1;
        }

        return position;
    }

    return formatted.length;
}
