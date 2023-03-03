<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Product stub with extend attributes
 */
class ExtendProductStub extends Product
{
    private ?LocalizedFallbackValue $titleManyToOne = null;
    private array $titleManyToMany = [];
    private array $titleOneToMany = [];

    
    public function getTitleManyToOne(): ?LocalizedFallbackValue
    {
        return $this->titleManyToOne;
    }

    public function setTitleManyToOne(LocalizedFallbackValue $titleManyToOne): void
    {
        $this->titleManyToOne = $titleManyToOne;
    }

    public function getTitleManyToMany(): array
    {
        return $this->titleManyToMany;
    }

    public function setTitleManyToMany(Collection $titles): void
    {
        $this->titleManyToMany = $titles->toArray();
    }

    public function getTitleOneToMany(): array
    {
        return $this->titleOneToMany;
    }

    public function setTitleOneToMany(Collection $titles): void
    {
        $this->titleOneToMany = $titles->toArray();
    }
}
