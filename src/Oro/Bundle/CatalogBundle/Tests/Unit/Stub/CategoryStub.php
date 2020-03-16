<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class CategoryStub extends Category
{
    use FallbackTrait;

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle(): ?LocalizedFallbackValue
    {
        return $this->getDefaultFallbackValue($this->titles);
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultSlugPrototype(): ?LocalizedFallbackValue
    {
        return $this->getDefaultFallbackValue($this->slugPrototypes);
    }
}
