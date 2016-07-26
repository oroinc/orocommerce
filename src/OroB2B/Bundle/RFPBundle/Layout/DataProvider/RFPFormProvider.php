<?php

namespace OroB2B\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;

class RFPFormProvider extends AbstractFormDataProvider
{
    const RFP_REQUEST_CREATE_ROUTE_NAME = 'orob2b_rfp_frontend_request_create';
    const RFP_REQUEST_UPDATE_ROUTE_NAME = 'orob2b_rfp_frontend_request_update';

    /**
     * @param ContextInterface $context
     *
     * @return FormAccessor
     */
    public function getRequestForm(ContextInterface $context)
    {
        $request = $this->getEntity($context, Request::class, 'entity');
        
        if ($request->getId()) {
            return $this->getFormAccessor(
                RequestType::NAME,
                self::RFP_REQUEST_UPDATE_ROUTE_NAME,
                $request,
                ['id' => $request->getId()]
            );
        }

        return $this->getFormAccessor(
            RequestType::NAME,
            self::RFP_REQUEST_CREATE_ROUTE_NAME,
            $request
        );
    }
}
