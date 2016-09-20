<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class ScopeManager
{
    /**
     * @var ScopeProviderInterface[]
     */
    protected $providers = [];

    /**
     * @param string $scopeTarget
     * @param array|object $context
     * @return Scope
     */
    public function findScope($scopeTarget, $context)
    {
        // @todo
    }

    /**
     * @param string $scopeTarget
     * @param array|object $context
     * @return Scope
     */
    public function findOrCreate($scopeTarget, $context)
    {
        // @todo
    }


    /**
     * Returns array of possible scopes by context ordered by priority
     *
     * @param string $scopeTarget
     * @param array|object $context
     *
     * @return Scope[]
     */
    public function findRelatedScopes($scopeTarget, $context)
    {
        // @todo
    }

    public function addProvider(ScopeProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }
}
