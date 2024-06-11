<?php

declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Stub;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

class SearchTermStub extends SearchTerm
{
    public function __construct(?int $id)
    {
        parent::__construct();

        $this->id = $id;
    }
}
