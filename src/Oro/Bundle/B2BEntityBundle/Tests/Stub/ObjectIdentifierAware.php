<?php

namespace Oro\Bundle\B2BEntityBundle\Tests\Stub;

use Oro\Bundle\B2BEntityBundle\Storage\ObjectIdentifierAwareInterface;

class ObjectIdentifierAware implements ObjectIdentifierAwareInterface
{
    /**
     * @var string
     */
    public $testProperty;

    /**
     * @var string
     */
    public $testProperty2;

    /**
     * @param string $testProperty
     * @param string $testProperty2
     */
    public function __construct($testProperty = null, $testProperty2 = null)
    {
        $this->testProperty = $testProperty;
        $this->testProperty2 = $testProperty2;
    }


    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier()
    {
        return get_class($this) . '_' . $this->testProperty . '_' . $this->testProperty2;
    }
}
