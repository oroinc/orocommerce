<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms SortOrder field array data into CollectionSortOrder entities
 */
class CollectionSortOrderTransformer implements DataTransformerInterface
{
    public function __construct(
        protected DoctrineHelper $doctrineHelper,
        protected ?Segment $segment = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value != null) {
            foreach ($value as $id => $item) {
                $value[$id]['data'] = ['categorySortOrder' => $item['data']->getSortOrder()];
            }
        }
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
        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        if ($value instanceof ArrayCollection) {
            $value = $value->toArray();
        }
        foreach ($value as $productId => $changeSetRow) {
            // Managing rows added then removed from collection
            if (!array_key_exists('categorySortOrder', $changeSetRow['data'])) {
                unset($value[$productId]);
                continue;
            }
            // Managing non-existent products
            $product = $this->getEntity(Product::class, (int)$productId);
            if (!$product) {
                unset($value[$productId]);
                continue;
            }

            $newValue = $changeSetRow['data']['categorySortOrder'];

            $collection = $this->buildCollection($productId, $product);
            $collection->setSortOrder(is_null($newValue) ? null : (float)$newValue);

            $value[$productId]['data'] = $collection;
        }

        return $value;
    }

    /**
     * @param int $productId
     * @param Product $product
     * @return CollectionSortOrder
     */
    private function buildCollection(int $productId, Product $product): CollectionSortOrder
    {
        $collection = null;
        if (!is_null($this->segment)) {
            $collection = $this->doctrineHelper
                ->getEntityRepository(CollectionSortOrder::class)
                ->findOneBy([
                    'product' => $productId,
                    'segment' => $this->segment->getId()
                ]);
        }
        if (is_null($collection)) {
            $collection = new CollectionSortOrder();
            $collection->setProduct($product);
            if (!is_null($this->segment)) {
                $collection->setSegment($this->segment);
            }
        }
        return $collection;
    }

    /**
     * @param string $entityClass
     * @param int $id
     * @return object
     */
    protected function getEntity(string $entityClass, int $id)
    {
        return $this->doctrineHelper->getEntityManager($entityClass)->find($entityClass, $id);
    }
}
