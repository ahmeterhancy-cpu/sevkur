<?php

namespace App\Services;

use RuntimeException;

class StockUnavailableException extends RuntimeException
{
    public function __construct(public readonly string $subject = '')
    {
        parent::__construct('Stok yetersiz: '.$subject);
    }
}
