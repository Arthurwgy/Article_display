<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReviewLogResource\Pages;

use App\Filament\Resources\ReviewLogResource;
use Filament\Resources\Pages\ListRecords;

class ListReviewLogs extends ListRecords
{
    protected static string $resource = ReviewLogResource::class;
}