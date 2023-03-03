<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product as BaseProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class Product extends BaseProduct
{
    use LocalizedEntityTrait;

    /**
     * @var AbstractEnumValue
     */
    private $inventoryStatus;

    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $color;

    /**
     * @var bool
     */
    private $slimFit;

    /**
     * @var EntityFieldFallbackValue
     */
    private $pageTemplate;

    /**
     * @var AbstractEnumValue[]|ArrayCollection
     */
    private $flags;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var float
     */
    private $categorySortOrder;

    /**
     * @var array
     */
    private $localizedFields = [
        'name' => 'names',
        'description' => 'descriptions',
        'shortDescription' => 'shortDescriptions',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->flags = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \RuntimeException('It\'s not expected to get non-existing property');
    }

    /**
     * {@inheritdoc}
     */
    public function __set(string $name, $value): void
    {
        if (array_key_exists($name, $this->localizedFields)) {
            $this->localizedFieldSet($this->localizedFields, $name, $value);

            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;

            return;
        }

        throw new \RuntimeException('It\'s not expected to set non-existing property');
    }

    /**
     * {@inheritdoc}
     */
    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return (bool)$this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param int $id
     * @return Product
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return AbstractEnumValue
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param AbstractEnumValue $inventoryStatus
     * @return $this
     */
    public function setInventoryStatus(AbstractEnumValue $inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getSlimFit()
    {
        return $this->slimFit;
    }

    /**
     * @param bool $slimFit
     */
    public function setSlimFit($slimFit)
    {
        $this->slimFit = $slimFit;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getPageTemplate()
    {
        return $this->pageTemplate;
    }

    public function setPageTemplate(EntityFieldFallbackValue $pageTemplate)
    {
        $this->pageTemplate = $pageTemplate;
    }

    public function setDirectlyPrimaryUnitPrecision(ProductUnitPrecision $primaryUnitPrecision)
    {
        $this->primaryUnitPrecision = $primaryUnitPrecision;
    }

    /**
     * @return ArrayCollection|AbstractEnumValue[]
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param ArrayCollection|AbstractEnumValue[] $flags
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

    /**
     * @return float|null
     */
    public function getCategorySortOrder()
    {
        return $this->categorySortOrder;
    }

    public function setCategorySortOrder(float $categorySortOrder)
    {
        $this->categorySortOrder = $categorySortOrder;
    }

    public function setParentVariantLinks(Collection $collection)
    {
        $this->parentVariantLinks = $collection;
    }

    public function setVariantLinks(Collection $collection)
    {
        $this->variantLinks = $collection;
    }

    public function cloneLocalizedFallbackValueAssociations(): self
    {
        return $this;
    }
}
