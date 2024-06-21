<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Stub;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

class SearchTermStub extends SearchTerm
{
    protected ?Page $redirectCmsPage = null;

    protected ?ContentBlock $contentBlock = null;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function getRedirectCmsPage(): ?Page
    {
        return $this->redirectCmsPage;
    }

    public function setRedirectCmsPage(?Page $redirectCmsPage): self
    {
        $this->redirectCmsPage = $redirectCmsPage;

        return $this;
    }

    public function getContentBlock(): ?ContentBlock
    {
        return $this->contentBlock;
    }

    public function setContentBlock(?ContentBlock $contentBlock): self
    {
        $this->contentBlock = $contentBlock;

        return $this;
    }
}
