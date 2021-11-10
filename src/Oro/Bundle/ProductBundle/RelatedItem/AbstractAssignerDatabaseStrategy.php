<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Abstract class for strategies which will save changes of product`s related items to database.
 */
abstract class AbstractAssignerDatabaseStrategy implements AssignerStrategyInterface
{
    /**
     * @var RelatedItemConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper, RelatedItemConfigProviderInterface $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function addRelations(Product $productFrom, array $productsTo)
    {
        if (count($productsTo) === 0) {
            return;
        }

        $productsTo = $this->validateRelations($productFrom, $productsTo);

        if (count($productsTo) === 0) {
            return;
        }

        foreach ($productsTo as $productTo) {
            $this->addRelation($productFrom, $productTo);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelations(Product $productFrom, array $productsTo)
    {
        if (count($productsTo) === 0) {
            return;
        }

        foreach ($productsTo as $productTo) {
            $this->removeRelation($productFrom, $productTo);
        }

        $this->getEntityManager()->flush();
    }

    protected function removeRelation(Product $productFrom, Product $productTo)
    {
        $persistedRelation = $this->getRepository()
            ->findOneBy(['product' => $productFrom, 'relatedItem' => $productTo]);

        if ($persistedRelation === null) {
            return;
        }

        $this->getEntityManager()->remove($persistedRelation);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return bool
     */
    protected function relationAlreadyExists(Product $productFrom, Product $productTo)
    {
        return $this->getRepository()->exists($productFrom, $productTo);
    }

    protected function addRelation(Product $productFrom, Product $productTo)
    {
        $relatedItem = $this->createNewRelation();
        $relatedItem->setProduct($productFrom)
            ->setRelatedItem($productTo);

        $this->getEntityManager()->persist($relatedItem);
    }

    /**
     * @param Product   $productFrom
     * @param Product[] $productsTo
     *
     * @throws \LogicException when functionality is disabled
     * @throws \OverflowException when user tries to add more products that limit allows
     * @throws \InvalidArgumentException When user tries to add related product to itself
     *
     * @return Product[]
     */
    protected function validateRelations(Product $productFrom, array $productsTo)
    {
        if (!$this->configProvider->isEnabled()) {
            throw new \LogicException('Related Items functionality is disabled.');
        }

        $newRelations = [];
        foreach ($productsTo as $productTo) {
            if (!$this->validateRelation($productFrom, $productTo)) {
                continue;
            }
            $newRelations[] = $productTo;
        }

        if (count($newRelations) === 0) {
            return [];
        }

        $numberOfRelations = $this->getRepository()->countRelationsForProduct($productFrom->getId());
        $numberOfRelations += count($newRelations);

        if ($numberOfRelations > $this->configProvider->getLimit()) {
            throw new \OverflowException(
                'It is not possible to add more related items, because of the limit of relations.'
            );
        }

        return $newRelations;
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     *
     * @throws \InvalidArgumentException When user tries to add related product to itself
     *
     * @return bool
     */
    protected function validateRelation(Product $productFrom, Product $productTo)
    {
        if ($productFrom === $productTo) {
            throw new \InvalidArgumentException('It is not possible to create relations from product to itself.');
        }

        if ($this->relationAlreadyExists($productFrom, $productTo)) {
            return false;
        }

        return true;
    }

    /**
     * @return RelatedItemEntityInterface
     */
    abstract protected function createNewRelation();

    /**
     * @return EntityManager
     */
    abstract protected function getEntityManager();

    /**
     * @return AbstractAssignerRepositoryInterface
     */
    abstract protected function getRepository();
}
