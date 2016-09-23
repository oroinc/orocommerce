<?php

namespace Oro\Bundle\ScopeBundle\Manager;

interface ScopeProviderInterface
{
    /**
     * @param array|object $context
     * @return array
     */
    public function getCriteriaByContext($context);

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope();
}
