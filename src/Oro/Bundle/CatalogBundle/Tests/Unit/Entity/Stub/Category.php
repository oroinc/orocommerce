<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;

class Category extends BaseCategory
{
    use LocalizedEntityTrait;

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

    public function addProduct(Product $value)
    {
        if (!$this->products->contains($value)) {
            $this->products->add($value);
        }

        return $this;
    }

    public function removeProduct(Product $value)
    {
        $this->products->removeElement($value);

        return $this;
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
