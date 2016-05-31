<?php

namespace OroB2B\Bundle\RFPBundle\Mailer;

use OroB2B\Bundle\RFPBundle\Entity\Request;

class RequestRepresentativesNotifier
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        $this->processor = $processor;
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
            foreach ($request->getAccount()->getSalesRepresentatives() as $salesRepresentative) {
                $this->processor->sendRFPNotification($request, $salesRepresentative);
            }
            $this->processor->sendRFPNotification($request, $request->getAccount()->getOwner());
            $this->processor->sendRFPNotification($request, $request->getAccountUser()->getOwner());
        }
    }
}
