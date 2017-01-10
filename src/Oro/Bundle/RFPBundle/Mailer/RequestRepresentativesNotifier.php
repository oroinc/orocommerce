<?php

namespace Oro\Bundle\RFPBundle\Mailer;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RFPBundle\Entity\Request;

class RequestRepresentativesNotifier
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param Processor $processor
     * @param ConfigManager $configManager
     */
    public function __construct(Processor $processor, ConfigManager $configManager)
    {
        $this->processor = $processor;
        $this->configManager = $configManager;
    }

    /**
     * @param Request $request
     */
    public function notifyRepresentatives(Request $request)
    {
        if ($request->getId()) {
            foreach ($request->getCustomerUser()->getSalesRepresentatives() as $salesRepresentative) {
                $this->processor->sendRFPNotification($request, $salesRepresentative);
            }

            if ($this->shouldNotifySalesRepsOfCustomer($request)) {
                foreach ($request->getCustomer()->getSalesRepresentatives() as $salesRepresentative) {
                    $this->processor->sendRFPNotification($request, $salesRepresentative);
                }
            }

            if ($this->shouldNotifyOwnerOfCustomerUser($request)) {
                $this->processor->sendRFPNotification($request, $request->getCustomerUser()->getOwner());
            }

            if ($this->shouldNotifyOwnerOfCustomer($request)) {
                $this->processor->sendRFPNotification($request, $request->getCustomer()->getOwner());
            }
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifySalesRepsOfCustomer(Request $request)
    {
        return ($request->getCustomer()->hasSalesRepresentatives()
            && ('always' == $this->configManager->get('oro_rfp.notify_assigned_sales_reps_of_the_customer')
                || !$request->getCustomerUser()->hasSalesRepresentatives()));
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfCustomerUser(Request $request)
    {
        return ('always' == $this->configManager->get('oro_rfp.notify_owner_of_customer_user_record')
            || !$request->getCustomerUser()->hasSalesRepresentatives());
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfCustomer(Request $request)
    {
        return ('always' == $this->configManager->get('oro_rfp.notify_owner_of_customer')
            || !$request->getCustomer()->hasSalesRepresentatives());
    }
}
