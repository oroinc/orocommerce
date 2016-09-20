<?php

namespace Oro\Bundle\ScopeBundle\Manager;

interface ScopeProviderInterface
{
    /**
     * @param string $scopeType
     * @param array|object $context
     * @return array
     */
    public function getCriteria($scopeType, $context);
}
