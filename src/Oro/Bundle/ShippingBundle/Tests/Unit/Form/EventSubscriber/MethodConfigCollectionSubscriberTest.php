<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

class MethodConfigCollectionSubscriberTest extends AbstractConfigSubscriberTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->methodConfigCollectionSubscriber;
    }
}
