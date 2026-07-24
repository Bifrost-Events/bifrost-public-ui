<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Response;

/**
 * Legacy «Mine deltakere» — erstattet av admin-core representerte personer.
 */
final class ParticipantController
{
    public function index(): array
    {
        return Response::redirect('/min-side/personer');
    }

    public function store(): array
    {
        return Response::redirect('/min-side/personer');
    }

    public function update(int $id): array
    {
        return Response::redirect('/min-side/personer');
    }
}
