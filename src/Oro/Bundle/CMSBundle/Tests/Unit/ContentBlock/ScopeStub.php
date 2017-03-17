<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlock;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class ScopeStub extends Scope
{
    private $field1;

    private $field2;

    public function __construct($field1, $field2)
    {
        $this->field1 = $field1;
        $this->field2 = $field2;
    }

    public function getField1()
    {
        return $this->field1;
    }

    public function getField2()
    {
        return $this->field2;
    }
}
