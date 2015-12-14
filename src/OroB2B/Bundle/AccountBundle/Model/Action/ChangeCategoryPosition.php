<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

class ChangeCategoryPosition extends AbstractCategoryCaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $category = $context->getEntity();

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->getConnection()->beginTransaction();

        try {
            $this->cacheBuilder->categoryPositionChanged($category);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
    }
}
