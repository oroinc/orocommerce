<?php

namespace OroB2B\Bundle\RFPBundle\Mailer;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\RFPBundle\Entity\Request;

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
            foreach ($request->getAccountUser()->getSalesRepresentatives() as $salesRepresentative) {
                $this->processor->sendRFPNotification($request, $salesRepresentative);
            }

            if ($this->shouldNotifySalesRepsOfAccount($request)) {
                foreach ($request->getAccount()->getSalesRepresentatives() as $salesRepresentative) {
                    $this->processor->sendRFPNotification($request, $salesRepresentative);
                }
            }

            if ($this->shouldNotifyOwnerOfAccountUser($request)) {
                $this->processor->sendRFPNotification($request, $request->getAccountUser()->getOwner());
            }

            if ($this->shouldNotifyOwnerOfAccount($request)) {
                $this->processor->sendRFPNotification($request, $request->getAccount()->getOwner());
            }
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifySalesRepsOfAccount(Request $request)
    {
        return ($request->getAccount()->hasSalesRepresentatives()
            && ('always' == $this->configManager->get('oro_b2b_rfp.notify_assigned_sales_reps_of_the_account')
                || !$request->getAccountUser()->hasSalesRepresentatives())
        );
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfAccountUser(Request $request)
    {
        return ($request->getAccountUser()->getOwner()
            && ('always' == $this->configManager->get('oro_b2b_rfp.notify_owner_of_account_user_record')
                || !$request->getAccountUser()->hasSalesRepresentatives())
        );
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldNotifyOwnerOfAccount(Request $request)
    {
        return ($request->getAccount()->getOwner()
            && ('always' == $this->configManager->get('oro_b2b_rfp.notify_owner_of_account')
                || !$request->getAccount()->hasSalesRepresentatives())
        );
    }
}
