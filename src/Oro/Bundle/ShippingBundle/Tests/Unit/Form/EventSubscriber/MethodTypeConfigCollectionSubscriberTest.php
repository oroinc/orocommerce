<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;

class MethodTypeConfigCollectionSubscriberTest extends MethodConfigSubscriberTest
{

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            MethodTypeConfigCollectionSubscriberProxy::getSubscribedEvents()
        );
    }
}
