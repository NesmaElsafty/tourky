<?php

namespace App\Helpers;

class PaginationHelper
{
    public static function paginate($query)
    {
        $total = $query->total();
        $perPage = $query->perPage();
        $currentPage = $query->currentPage();
        $lastPage = $query->lastPage();
        $from = $query->firstItem();
        $to = $query->lastItem();
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }
} 