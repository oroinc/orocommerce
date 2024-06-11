<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

class SearchTermStub extends SearchTerm
{
    protected ?Category $redirectCategory = null;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function getRedirectCategory(): ?Category
    {
        return $this->redirectCategory;
    }

    public function setRedirectCategory(?Category $redirectCategory): self
    {
        $this->redirectCategory = $redirectCategory;

        return $this;
    }
}
