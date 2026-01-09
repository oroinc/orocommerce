<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;

/**
 * Updates customer visibility data in the search index after demo data fixtures are loaded.
 *
 * This listener ensures that the website search index is properly updated with customer-specific visibility
 * information after demo data is loaded, maintaining consistency between visibility settings and search results.
 */
class ChangeVisibilityDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    /** @var CustomerPartialUpdateDriverInterface */
    protected $partialUpdateDriver;

    public function __construct(
        OptionalListenerManager $listenerManager,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        parent::__construct($listenerManager);

        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    #[\Override]
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
        $event->log('updating visibility for all customers');

        /* @var $customers Customer[] */
        $customers = $event->getObjectManager()->getRepository(Customer::class)->findAll();

        foreach ($customers as $customer) {
            $this->partialUpdateDriver->updateCustomerVisibility($customer);
        }
    }
}
