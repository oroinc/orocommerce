<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Category extends BaseCategory
{
    use LocalizedEntityTrait;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var ArrayCollection
     */
    private $products;

    /**
     * @var File
     */
    private $smallImage;

    /**
     * @var array
     */
    protected $localizedFields = [
        'title' => 'titles',
        'shortDescription' => 'shortDescriptions',
        'longDescription' => 'longDescriptions',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->products = new ArrayCollection();
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
    public function __get($name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        } else {
            $this->getPropertyAccessor()->getValue($this, $name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldSet($this->localizedFields, $name, $value);
        } else {
            $reflection = new \ReflectionProperty(self::class, $name);
            $reflection->setAccessible(true);
            $reflection->setValue($this, $value);
        }

        return null;
    }

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param Product $value
     */
    public function addProduct(Product $value)
    {
        if (!$this->products->contains($value)) {
            $this->products->add($value);
        }
    }

    /**
     * @param Product $value
     */
    public function removeProduct(Product $value)
    {
        $this->products->removeElement($value);
    }

    /**
     * @return ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param ArrayCollection $products
     *
     * @return Category
     */
    public function setProducts(ArrayCollection $products)
    {
        $this->products = $products;

        return $this;
    }

    /**
     * @return File
     */
    public function getSmallImage()
    {
        return $this->smallImage;
    }

    /**
     * @param File $smallImage
     *
     * @return Category
     */
    public function setSmallImage(File $smallImage)
    {
        $this->smallImage = $smallImage;

        return $this;
    }
}
