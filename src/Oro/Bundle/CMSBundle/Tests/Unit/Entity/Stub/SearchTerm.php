<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm as BaseSearchTerm;

class SearchTerm extends BaseSearchTerm
{
    protected ?ContentBlock $contentBlock = null;

    public function getContentBlock(): ?ContentBlock
    {
        return $this->contentBlock;
    }

    public function setContentBlock(ContentBlock $contentBlock): self
    {
        $this->contentBlock = $contentBlock;

        return $this;
    }
}
