<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;

class MethodTypeConfigCollectionSubscriberTest extends AbstractConfigSubscriberTest
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->methodTypeConfigCollectionSubscriber;
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT => 'preSubmit',
                FormEvents::POST_SET_DATA => 'postSetData',
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }
}
