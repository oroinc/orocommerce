<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

class MethodTypeConfigCollectionSubscriberTest extends AbstractConfigSubscriberTest
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->methodTypeConfigCollectionSubscriber;
    }
}
