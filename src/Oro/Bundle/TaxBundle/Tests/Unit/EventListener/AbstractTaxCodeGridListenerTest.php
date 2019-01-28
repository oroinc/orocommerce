<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

abstract class AbstractTaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var object */
    protected $listener;

    protected function setUp()
    {
        $this->listener = $this->createListener();
    }

    protected function tearDown()
    {
        unset($this->listener, $this->doctrineHelper);
    }

    /**
     * @return object
     */
    abstract protected function createListener();
}
