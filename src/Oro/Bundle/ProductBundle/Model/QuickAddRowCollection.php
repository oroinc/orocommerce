<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\QuickAddRowCollectionValidateEvent;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Collection of QuickAddRow models.
 */
class QuickAddRowCollection extends ArrayCollection
{
    use QuickAddFieldTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected array $errors = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(PHP_EOL, $this->map(function (QuickAddRow $row) {
            return sprintf('%s, %s', $row->getSku(), $row->getQuantity());
        })->toArray());
    }

    /**
     * @return QuickAddRowCollection|QuickAddRow[]
     */
    public function getValidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isValid();
        });
    }

    /**
     * @return QuickAddRowCollection|QuickAddRow[]
     */
    public function getInvalidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return !$row->isValid();
        });
    }

    /**
     * @return bool
     */
    public function hasValidRows()
    {
        return count($this->getValidRows()) > 0;
    }

    /**
     * @return array
     */
    public function getSkus()
    {
        $skus = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($sku = $row->getSku()) {
                $skus[] = $sku;
            }
        }

        return $skus;
    }

    /**
     * @param Product[] $products
     * @return QuickAddRowCollection
     */
    public function mapProducts(array $products)
    {
        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            $sku = mb_strtoupper($row->getSku());

            if (array_key_exists($sku, $products)) {
                $row->setProduct($products[$sku]);
            }
        }

        return $this;
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        $products = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($product = $row->getProduct()) {
                $products[mb_strtoupper($product->getSku())] = $product;
            }
        }

        return $products;
    }

    public function validateEventDispatcher()
    {
        if ($this->eventDispatcher instanceof EventDispatcherInterface) {
            $event = new QuickAddRowCollectionValidateEvent($this);
            $this->eventDispatcher->dispatch($event, $event::NAME);
        }
    }

    /**
     * Prepares data for QuickAddType
     *
     * @return array
     */
    public function getFormData()
    {
        $data = [QuickAddType::PRODUCTS_FIELD_NAME => []];

        foreach ($this->getValidRows() as $row) {
            $productRow = new ProductRow();
            $productRow->productSku = $row->getSku();
            $productRow->productQuantity = $row->getQuantity();
            $productRow->productUnit = $row->getUnit();

            $data[QuickAddType::PRODUCTS_FIELD_NAME][] = $productRow;
        }

        return $data;
    }

    protected function createFrom(array $elements)
    {
        $quickAddRowCollection = parent::createFrom($elements);
        $quickAddRowCollection->errors = $this->errors;

        return $quickAddRowCollection;
    }

    public function addError(string $message, array $parameters = []): self
    {
        $this->errors[] = [
            'message' => $message,
            'parameters' => $parameters,
        ];

        return $this;
    }

    /**
     * @return array<array{message: string, parameters: array}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function isValid(): bool
    {
        return !$this->hasErrors() && $this->forAll(static fn ($key, QuickAddRow $element) => !$element->hasErrors());
    }
}
