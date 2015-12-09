<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductNormalizerEvent extends Event
{
    const NORMALIZE = 'orob2b_product.normalizer.normalizer';
    const DENORMALIZE = 'orob2b_product.normalizer.denormalizer';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $plainData = [];

    /**
     * @var array
     */
    protected $context = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Product $product, array $plainData, array $context = [])
    {
        $this->product = $product;
        $this->plainData = $plainData;
        $this->context = $context;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return array
     */
    public function getPlainData()
    {
        return $this->plainData;
    }

    /**
     * @param array $plainData
     */
    public function setPlainData(array $plainData)
    {
        $this->plainData = $plainData;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
