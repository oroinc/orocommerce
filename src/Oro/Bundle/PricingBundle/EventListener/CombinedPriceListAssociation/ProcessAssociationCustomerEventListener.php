<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;

/**
 * Processing combined price list associations on customer level
 */
class ProcessAssociationCustomerEventListener extends AbstractProcessAssociationEventListener
{
    public function onProcessAssociations(ProcessEvent $event): void
    {
        $associations = $event->getAssociations();
        $byWebsite = $associations['website'] ?? [];
        unset($byWebsite['ids']);
        $cplUpdateData = [];
        foreach ($byWebsite as $key => $data) {
            $websiteId = $this->getWebsiteId($key);
            $ids = $data['customer']['ids'] ?? [];
            if (empty($ids)) {
                continue;
            }
            $customers = $this->getEntitiesByIds($ids, Customer::class);
            $website = $this->getWebsite($websiteId);

            if ($website && $customers) {
                $cplUpdateData[] = [
                    'websiteId' => $websiteId,
                    'customers' => array_keys($customers)
                ];
                $this->processAssignments($event->getCombinedPriceList(), $website, $event->getVersion(), $customers);
            }
        }

        if (!$event->isSkipUpdateNotification() && $cplUpdateData) {
            $this->eventDispatcher->dispatch(new CustomerCPLUpdateEvent($cplUpdateData), CustomerCPLUpdateEvent::NAME);
        }
    }

    public function onGetAssociatedWebsites(GetAssociatedWebsitesEvent $event): void
    {
        if (!$event->getAssociations()) {
            $websites = $this->registry
                ->getRepository(CombinedPriceListToCustomer::class)
                ->getWebsitesByCombinedPriceList($event->getCombinedPriceList());

            foreach ($websites as $website) {
                $event->addWebsiteAssociation($website);
            }
        }
    }
}
