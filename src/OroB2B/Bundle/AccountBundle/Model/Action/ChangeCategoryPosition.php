<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

class ChangeCategoryPosition extends CategoryCaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $category = $context->getEntity();

        $this->cacheBuilder->categoryPositionChanged($category);
    }
}
