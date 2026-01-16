<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Holds the product object and import data.
 * Dispatched in:
 * - {@see \Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy::beforeProcessEntity()}
 * - {@see \Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy::afterProcessEntity()}
 * This event allows listeners to modify or validate product data during import operations, providing access to
 * both the product entity and the raw import data for custom processing before or after the main import strategy logic.
 */
class ProductStrategyEvent extends Event
{
    public const PROCESS_BEFORE = 'oro_product.strategy.process_before';
    public const PROCESS_AFTER = 'oro_product.strategy.process_after';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $rawData = [];

    protected bool $productValid = true;

    protected ?ContextInterface $context = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(Product $product, array $rawData)
    {
        $this->product = $product;
        $this->productValid = true;
        $this->rawData = $rawData;
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
    public function getRawData()
    {
        return $this->rawData;
    }

    public function getContext(): ?ContextInterface
    {
        return $this->context;
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function isProductValid(): bool
    {
        return $this->productValid;
    }

    public function markProductInvalid(): void
    {
        $this->productValid = false;
    }
}
