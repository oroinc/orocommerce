<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides actual Price List for given Customer and Website. This parameters are not mandatory and in case
 * when they not passed will try to get Price List from runtime (if logged in as Anonymous user)
 * or from System Configuration.
 */
class FlatPriceListTreeHandler extends AbstractPriceListTreeHandler
{
    /**
     * @var PriceListRepository
     */
    private $priceListRepository;

    /**
     * {@inheritDoc}
     */
    protected function loadPriceListByCustomer(Customer $customer, Website $website)
    {
        $priceList = $this->getPriceListRepository()->getPriceListByCustomer($customer, $website);

        return $this->checkPriceListSchedule($priceList);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadPriceListByCustomerGroup(CustomerGroup $customerGroup, Website $website)
    {
        $priceList = $this->getPriceListRepository()->getPriceListByCustomerGroup($customerGroup, $website);

        return $this->checkPriceListSchedule($priceList);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListByWebsite(Website $website)
    {
        return $this->getDefaultPriceList($website);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListFromConfig()
    {
        return $this->getDefaultPriceList();
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry
                ->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class);
        }

        return $this->priceListRepository;
    }

    private function getDefaultPriceList(?Website $website = null): ?PriceList
    {
        $priceListId = $this->configManager->get('oro_pricing.default_price_list', false, false, $website);
        if (!$priceListId) {
            return null;
        }

        $priceList = $this->getPriceListRepository()->getActivePriceListById($priceListId);

        return $this->checkPriceListSchedule($priceList);
    }

    /**
     * Return price list only if it has no schedule or the current schedule is active.
     */
    private function checkPriceListSchedule(?PriceList $priceList): ?PriceList
    {
        if (!$priceList) {
            return null;
        }

        if ($priceList->isContainSchedule()) {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach ($priceList->getSchedules() as $schedule) {
                if (!$schedule->getActiveAt() || $schedule->getActiveAt() <= $now) {
                    if (!$schedule->getDeactivateAt() || $schedule->getDeactivateAt() > $now) {
                        // Active schedule found, return price list
                        return $priceList;
                    }
                }
            }

            // No active schedule found, return null
            return null;
        }

        // For price lists without schedules return price list
        return $priceList;
    }
}
