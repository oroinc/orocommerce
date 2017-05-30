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
    public function addRelation(Product $productFrom, Product $productTo)
    {
        $this->validateRelation($productFrom, $productTo);

        if ($this->relationAlreadyExists($productFrom, $productTo)) {
            return;
        }

        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct($productFrom)
            ->setRelatedProduct($productTo);

        $this->getEntityManager()->persist($relatedProduct);
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelation(Product $productFrom, Product $productTo)
    {
        $persistedRelation = $this->findPersistedRelation($productFrom, $productTo);

        if ($persistedRelation !== null) {
            $this->getEntityManager()->detach($persistedRelation);

            return;
        }

        $persistedRelation = $this->getRelatedProductsRepository()
            ->findOneBy(['product' => $productFrom, 'relatedProduct', $productTo]);

        if ($persistedRelation !== null) {
            $this->getEntityManager()->remove($persistedRelation);

            return;
        }
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return bool
     */
    private function relationAlreadyExists(Product $productFrom, Product $productTo)
    {
        return $this->findPersistedRelation($productFrom, $productTo) !== null
            || $this->relationExistsInDatabase($productFrom, $productTo);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return RelatedProduct|null
     */
    private function findPersistedRelation(Product $productFrom, Product $productTo)
    {
        $uow = $this->getEntityManager()->getUnitOfWork();

        foreach ($this->getCreatedEntities($uow) as $persistingRelation) {
            if (!$persistingRelation instanceof RelatedProduct) {
                continue;
            }

            if ($persistingRelation->getProduct() === $productFrom
                && $persistingRelation->getRelatedProduct() === $productTo
            ) {
                return $persistingRelation;
            }
        }

        return null;
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return bool
     */
    private function relationExistsInDatabase(Product $productFrom, Product $productTo)
    {
        return $this->getRelatedProductsRepository()->exists($productFrom, $productTo);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @throws \LogicException when functionality is disabled
     * @throws \InvalidArgumentException when user tries add related product to itself
     * @throws \OverflowException when user tries to add more products that limit allows
     */
    private function validateRelation(Product $productFrom, Product $productTo)
    {
        if (!$this->configProvider->isEnabled()) {
            throw new \LogicException('Related Products functionality is disabled.');
        }

        if ($productFrom === $productTo) {
            throw new \InvalidArgumentException('It is not possible to create relations from product to itself.');
        }

        $numberOfRelations = $this->getNumberOfScheduledRelationsForProduct($productFrom);
        $numberOfRelations += $this->getRelatedProductsRepository()->countRelationsForProduct($productFrom->getId());

        if ($numberOfRelations >= $this->configProvider->getLimit()) {
            throw new \OverflowException(
                sprintf(
                    'It is not possible to add more related products to %s, because of the limit of relations.',
                    $productFrom->getName()
                )
            );
        }
    }

    /**
     * @param Product $productFrom
     * @return int
     */
    private function getNumberOfScheduledRelationsForProduct(Product $productFrom)
    {
        $numberOfRelations = 0;
        $uow = $this->getEntityManager()->getUnitOfWork();

        foreach ($this->getCreatedEntities($uow) as $persistingRelation) {
            if (!$persistingRelation instanceof RelatedProduct) {
                continue;
            }

            if ($persistingRelation->getProduct() === $productFrom) {
                $numberOfRelations++;
            }
        }

        return $numberOfRelations;
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
