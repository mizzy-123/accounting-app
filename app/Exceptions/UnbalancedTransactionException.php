<?php

namespace App\Exceptions;

use Exception;

class UnbalancedTransactionException extends Exception
{
    public static function entries(): self
    {
        return new self('Total debit harus sama dengan total kredit.');
    }

    public static function invalidEntryRow(): self
    {
        return new self('Setiap baris jurnal hanya boleh memiliki debit atau kredit, tidak keduanya.');
    }
}
