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

    public function __construct(Processor $processor, ConfigManager $configManager)
    {
        $this->processor = $processor;
        $this->configManager = $configManager;
    }

    public function notifyRepresentatives(Request $request)
    {
        if ($request->getId()) {
            $recipients = $request->getCustomerUser()->getSalesRepresentatives()->toArray();

            if ($this->shouldNotifySalesRepsOfCustomer($request)) {
                $recipients = array_merge($recipients, $request->getCustomer()->getSalesRepresentatives()->toArray());
            }

            if ($this->shouldNotifyOwnerOfCustomerUser($request)) {
                $recipients[] = $request->getCustomerUser()->getOwner();
            }

            if ($this->shouldNotifyOwnerOfCustomer($request)) {
                $recipients[] = $request->getCustomer()->getOwner();
            }

            foreach (array_unique($recipients, SORT_REGULAR) as $recipient) {
                $this->processor->sendRFPNotification($request, $recipient);
            }
        }
    }

    /**
     * Send confirmation email to guest customer user if request is created
     */
    public function sendConfirmationEmail(Request $request)
    {
        $customerUser = $request->getCustomerUser();
        if ($customerUser !== null && $customerUser->isGuest() && $request->getId()) {
            $this->processor->sendConfirmation($request, $customerUser);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifySalesRepsOfCustomer(Request $request)
    {
        return ($request->getCustomer()->hasSalesRepresentatives()
            && ('always' === $this->configManager->get('oro_rfp.notify_assigned_sales_reps_of_the_customer')
                || !$request->getCustomerUser()->hasSalesRepresentatives()));
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfCustomerUser(Request $request)
    {
        return ('always' === $this->configManager->get('oro_rfp.notify_owner_of_customer_user_record')
            || !$request->getCustomerUser()->hasSalesRepresentatives());
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfCustomer(Request $request)
    {
        return ('always' === $this->configManager->get('oro_rfp.notify_owner_of_customer')
            || !$request->getCustomer()->hasSalesRepresentatives());
    }
}
