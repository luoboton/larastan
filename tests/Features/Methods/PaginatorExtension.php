<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;

class PaginatorExtension
{
    public function testPaginateProxiesToCollection(): array
    {
        return User::paginate()->all();
    }

    public function testSimplePaginateProxiesToCollection(): array
    {
        return User::simplePaginate()->all();
    }
}
