<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;

class ChangeCategoryPosition extends AbstractCategoryCaseAction
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
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof ProcessData) {
            throw new \LogicException('This action can be called only from process context');
        }

        $category = $context->getEntity();
        if (!$category instanceof Category) {
            throw new \LogicException('Action can be applied only to Category entity');
        }

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->transactional(
            function () use ($category) {
                $this->cacheBuilder->categoryPositionChanged($category);
            }
        );
    }
}
