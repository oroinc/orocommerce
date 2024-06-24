<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

class SearchTermStub extends SearchTerm
{
    protected ?ContentNode $redirectContentNode = null;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function getRedirectContentNode(): ?ContentNode
    {
        return $this->redirectContentNode;
    }

    public function setRedirectContentNode(?ContentNode $redirectContentNode): self
    {
        $this->redirectContentNode = $redirectContentNode;

        return $this;
    }
}
