<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class CategoryStub extends Category
{
    use FallbackTrait;

    public function __construct(?int $id = null)
    {
        parent::__construct();
        
        if ($id !== null) {
            $this->id = $id;
        }
    }

    /**
     * @return CategoryTitle
     */
    public function getDefaultTitle(): ?CategoryTitle
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

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function __clone()
    {
    }
}
