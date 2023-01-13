<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;

/**
 * Processing combined price list associations on customer group level
 */
class ProcessAssociationCustomerGroupEventListener extends AbstractProcessAssociationEventListener
{
    public function onProcessAssociations(ProcessEvent $event): void
    {
        $associations = $event->getAssociations();
        $byWebsite = $associations['website'] ?? [];
        unset($byWebsite['ids']);
        $cplUpdateData = [];
        foreach ($byWebsite as $key => $data) {
            $websiteId = $this->getWebsiteId($key);
            $ids = $data['customer_group']['ids'] ?? [];
            if (empty($ids)) {
                continue;
            }
            $customerGroups = $this->getEntitiesByIds($ids, CustomerGroup::class);
            $website = $this->getWebsite($websiteId);

            if ($website && $customerGroups) {
                $cplUpdateData[] = [
                    'websiteId' => $websiteId,
                    'customerGroups' => array_keys($customerGroups)
                ];
                $this->processAssignments(
                    $event->getCombinedPriceList(),
                    $website,
                    $event->getVersion(),
                    $customerGroups
                );
            }
        }

        if (!$event->isSkipUpdateNotification() && $cplUpdateData) {
            $this->eventDispatcher->dispatch(
                new CustomerGroupCPLUpdateEvent($cplUpdateData),
                CustomerGroupCPLUpdateEvent::NAME
            );
        }
    }

    public function onGetAssociatedWebsites(GetAssociatedWebsitesEvent $event): void
    {
        if (!$event->getAssociations()) {
            $websites = $this->registry
                ->getRepository(CombinedPriceListToCustomerGroup::class)
                ->getWebsitesByCombinedPriceList($event->getCombinedPriceList());

            foreach ($websites as $website) {
                $event->addWebsiteAssociation($website);
            }
        }
    }
}
