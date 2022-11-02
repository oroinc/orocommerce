<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires after frontend product export data normalizing. Stores product data and exported values. New custom values for
 * export could be mixed during this event.
 */
class ProductExportNormalizerEvent extends Event
{
    public const FRONTEND_PRODUCT_EXPORT_NORMALIZE = 'oro_product.frontend_product_export.normalize';

    private array $data;
    private Product $product;
    private array $options;

    public function __construct(Product $product, array $data, array $options)
    {
        $this->data = $data;
        $this->product = $product;
        $this->options = $options;
    }

    /**
     * @return array Normalized product data
     * [
     *      'sample_field_name' => 'sample_field_value',
     *      // ...
     * ]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return array Contains export options
     *  [
     *      'currentCurrency' => 'USD',
     *      'currentLocalizationId' => 2,
     *      'ids' => [1, 2],
     *      // ...
     *  ]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $data Normalized product data
     * [
     *      'sample_field_name' => 'sample_field_value',
     *      // ...
     * ]
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
