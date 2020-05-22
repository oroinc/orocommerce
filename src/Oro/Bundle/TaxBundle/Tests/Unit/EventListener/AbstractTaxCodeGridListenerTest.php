<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

abstract class AbstractTaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var object */
    protected $listener;

    protected function setUp(): void
    {
        $this->listener = $this->createListener();
    }

    protected function tearDown(): void
    {
        unset($this->listener, $this->doctrineHelper);
    }

    /**
     * @return object
     */
    abstract protected function createListener();
}
