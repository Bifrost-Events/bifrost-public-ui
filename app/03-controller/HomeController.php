<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\PublicView;

final class HomeController
{
    public function __invoke(): array
    {
        return PublicView::renderHome();
    }
}
