<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

class ProductNormalizerEvent extends Event
{
    public const NORMALIZE = 'oro_product.normalizer.normalizer';
    public const DENORMALIZE = 'oro_product.normalizer.denormalizer';

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
