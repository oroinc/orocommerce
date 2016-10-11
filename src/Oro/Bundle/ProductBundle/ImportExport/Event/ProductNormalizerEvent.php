<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductNormalizerEvent extends Event
{
    const NORMALIZE = 'oro_product.normalizer.normalizer';
    const DENORMALIZE = 'oro_product.normalizer.denormalizer';

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
