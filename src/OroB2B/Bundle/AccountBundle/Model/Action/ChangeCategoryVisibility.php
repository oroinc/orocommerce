<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

class ChangeCategoryVisibility extends CategoryCaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $categoryVisibility = $context->getEntity();

        $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
    }
}
