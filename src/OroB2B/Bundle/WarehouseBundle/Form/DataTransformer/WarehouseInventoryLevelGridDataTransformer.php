<?php

namespace Oro\Bundle\WarehouseBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseInventoryLevelGridDataTransformer implements DataTransformerInterface
{
    const PRECISION_KEY = 'precision';
    const WAREHOUSE_KEY = 'warehouse';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param Product $product
     */
    public function __construct(DoctrineHelper $doctrineHelper, Product $product)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->product = $product;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return new ArrayCollection();
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($value as $combinedId => $changeSetRow) {
            list($warehouseId, $precisionId) = explode('_', $combinedId, 2);
            $warehouse = $this->getWarehouse((int)$warehouseId);
            $precision = $this->getPrecision((int)$precisionId);

            if (!$warehouse || !$precision) {
                unset($value[$combinedId]);
                continue;
            }

            $value[$combinedId] = array_merge(
                $changeSetRow,
                [self::WAREHOUSE_KEY => $warehouse, self::PRECISION_KEY => $precision]
            );
        }

        return $value;
    }

    /**
     * @param int|string $id
     * @return Warehouse|null
     */
    protected function getWarehouse($id)
    {
        return $this->doctrineHelper->getEntityReference('OroWarehouseBundle:Warehouse', $id);
    }

    /**
     * @param int|string $id
     * @return ProductUnitPrecision|null
     */
    protected function getPrecision($id)
    {
        foreach ($this->product->getUnitPrecisions() as $precision) {
            if ($precision->getId() == $id) {
                return $precision;
            }
        }

        return null;
    }
}
