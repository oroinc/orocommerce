<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;

class ChangeCategoryPosition extends AbstractVisibilityRegistryAwareAction
{
    /**
     * @var CategoryCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        if (!$this->cacheBuilder instanceof CategoryCaseCacheBuilderInterface) {
            throw new \InvalidArgumentException('Cache builder must impelement CategoryCaseCacheBuilderInterface');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $category = $this->getEntity($context);

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->transactional(
            function () use ($category) {
                $this->cacheBuilder->categoryPositionChanged($category);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity($context)
    {
        $entity = parent::getEntity($context);

        if (!$entity instanceof Category) {
            throw new \LogicException('Action can be applied only to Category entity');
        }

        return $entity;
    }
}
