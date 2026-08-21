<?php

namespace App\Filament\Concerns;

use Livewire\Features\SupportPagination\PaginationUrl;

trait PersistsTablePaginationInUrl
{
    public function updatedPaginators(int | string $page, string $pageName): void
    {
        $this->setPropertyAttribute(
            "paginators.{$pageName}",
            new PaginationUrl(
                as: $pageName,
                history: true,
                keep: false
            )
        );
    }
}
