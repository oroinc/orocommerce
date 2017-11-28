<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;

class ChangeVisibilityDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_visibility.entity.entity_listener.customer_listener',
    ];

    /** @var OptionalListenerManager */
    protected $listenerManager;

    /** @var CustomerPartialUpdateDriverInterface */
    protected $partialUpdateDriver;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param CustomerPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->listenerManager = $listenerManager;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->disableListeners(self::LISTENERS);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->enableListeners(self::LISTENERS);

            $this->processCustomers($event);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    protected function processCustomers(MigrationDataFixturesEvent $event)
    {
        $event->log('updating visibility for all customers');

        /* @var $customers Customer[] */
        $customers = $event->getObjectManager()->getRepository(Customer::class)->findAll();

        foreach ($customers as $customer) {
            $this->partialUpdateDriver->updateCustomerVisibility($customer);
        }
    }
}
