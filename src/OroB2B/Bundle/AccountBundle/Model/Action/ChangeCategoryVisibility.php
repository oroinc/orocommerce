<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class ChangeCategoryVisibility extends AbstractCategoryCaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof ProcessData) {
            throw new \LogicException('This action can be called only from process context');
        }

        $categoryVisibility = $context->getEntity();
        if (!$categoryVisibility instanceof VisibilityInterface) {
            throw new \LogicException('Resolvable entity must implement VisibilityInterface');
        }

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->transactional(
            function () use ($categoryVisibility) {
                $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
            }
        );
    }
}
