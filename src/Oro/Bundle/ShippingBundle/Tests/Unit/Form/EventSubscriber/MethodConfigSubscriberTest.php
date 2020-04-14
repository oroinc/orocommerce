<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

class MethodConfigSubscriberTest extends AbstractConfigSubscriberTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->methodConfigSubscriber;
    }
}
