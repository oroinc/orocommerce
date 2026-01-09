<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;

class MethodConfigCollectionSubscriberTest extends AbstractConfigSubscriberTest
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->methodConfigCollectionSubscriber;
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit',
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }
}
