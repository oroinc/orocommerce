<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class StubScope extends Scope
{
    /**
     * @var mixed
     */
    protected $scopeField;

    /**
     * @return mixed
     */
    public function getScopeField()
    {
        return $this->scopeField;
    }

    /**
     * @param mixed $scopeField
     */
    public function setScopeField($scopeField)
    {
        $this->scopeField = $scopeField;
    }
}
