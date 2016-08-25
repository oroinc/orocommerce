<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

class ChangeProductCategory extends AbstractVisibilityRegistryAwareAction
{
    /**
     * @var ProductCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $product = $this->getEntity($context);

        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();
        try {
            $this->cacheBuilder->productCategoryChanged($product);
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity($context)
    {
        $entity = parent::getEntity($context);

        if (!$entity instanceof Product) {
            throw new \LogicException('Resolvable entity must instance of Product');
        }

        return $entity;
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }
}
