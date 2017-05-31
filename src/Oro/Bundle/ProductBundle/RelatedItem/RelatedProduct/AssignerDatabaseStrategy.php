<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;

class AssignerDatabaseStrategy implements AssignerStrategyInterface
{
    use ChangedEntityGeneratorTrait;

    /**
     * @var AbstractRelatedItemConfigProvider
     */
    private $configProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param AbstractRelatedItemConfigProvider $configProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, AbstractRelatedItemConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function addRelations(Product $productFrom, array $productsTo)
    {
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
        foreach ($productsTo as $productTo) {
            $this->removeRelation($productFrom, $productTo);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     */
    private function removeRelation(Product $productFrom, Product $productTo)
    {
        $persistedRelation = $this->getRelatedProductsRepository()
            ->findOneBy(['product' => $productFrom, 'relatedProduct', $productTo]);

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
    private function relationAlreadyExists(Product $productFrom, Product $productTo)
    {
        return $this->getRelatedProductsRepository()->exists($productFrom, $productTo);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     */
    private function addRelation(Product $productFrom, Product $productTo)
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct($productFrom)
            ->setRelatedProduct($productTo);

        $this->getEntityManager()->persist($relatedProduct);
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
    private function validateRelations(Product $productFrom, array $productsTo)
    {
        if (!$this->configProvider->isEnabled()) {
            throw new \LogicException('Related Products functionality is disabled.');
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

        $numberOfRelations = $this->getRelatedProductsRepository()->countRelationsForProduct($productFrom->getId());
        $numberOfRelations += count($newRelations);

        if ($numberOfRelations > $this->configProvider->getLimit()) {
            throw new \OverflowException(
                sprintf(
                    'It is not possible to add more related products to %s, because of the limit of relations.',
                    $productFrom->getName()
                )
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
    private function validateRelation(Product $productFrom, Product $productTo)
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
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(RelatedProduct::class);
    }

    /**
     * @return RelatedProductRepository|EntityRepository
     */
    private function getRelatedProductsRepository()
    {
        return $this->doctrineHelper->getEntityRepository(RelatedProduct::class);
    }
}
