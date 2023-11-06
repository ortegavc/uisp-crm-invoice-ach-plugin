<?php

declare(strict_types=1);

namespace App\Service;

class InvoiceStatuses
{
    public const DRAFT = '0';
    public const UNPAID = '1';
    public const PARTIALLY_PAID = '2';
    public const PAID = '3';
    public const VOID = '4';
    public const PROCESSED_PROFORMA = '5';
}
