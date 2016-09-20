<?php

namespace Oro\Bundle\AccountBundle\Scope;

use Oro\Bundle\ScopeBundle\Manager\ScopeProviderInterface;

class AccountScopeProvider implements ScopeProviderInterface
{
    /**
     * @param string $scopeType
     * @param array|object $context
     * @return array
     */
    public function getCriteria($scopeType, $context)
    {
        return ['account' => $context['account']];
    }
}
