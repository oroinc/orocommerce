<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;

class RFPFormProvider extends AbstractFormProvider
{
    const RFP_REQUEST_CREATE_ROUTE_NAME = 'oro_rfp_frontend_request_create';
    const RFP_REQUEST_UPDATE_ROUTE_NAME = 'oro_rfp_frontend_request_update';

    /**
     * @param Request $request
     *
     * @return FormAccessor
     */
    public function getRequestForm(Request $request)
    {
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
