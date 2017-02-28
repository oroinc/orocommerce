<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

class MethodTypeConfigCollectionSubscriberTest extends AbstractConfigSubscriberTest
{
    public function setUp()
    {
        parent::setUp();
        $this->subscriber = $this->methodTypeConfigCollectionSubscriber;
    }
}
