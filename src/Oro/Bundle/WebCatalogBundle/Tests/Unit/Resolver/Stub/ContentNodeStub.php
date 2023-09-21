<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeStub extends ContentNode
{
    use FallbackTrait;

    /** @var ArrayCollection */
    protected $titles;

    public function getTitle(Localization $localization = null): LocalizedFallbackValue
    {
        return $this->getFallbackValue($this->titles, $localization);
    }

    public function setTitles(ArrayCollection $titles): void
    {
        $this->titles = $titles;
    }
}
