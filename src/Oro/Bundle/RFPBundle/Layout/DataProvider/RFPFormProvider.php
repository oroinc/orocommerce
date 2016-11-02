<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;

class RFPFormProvider extends AbstractFormProvider
{
    const RFP_REQUEST_CREATE_ROUTE_NAME = 'oro_rfp_frontend_request_create';
    const RFP_REQUEST_UPDATE_ROUTE_NAME = 'oro_rfp_frontend_request_update';

    /**
     * @param Request $request
     * @param array   $options
     *
     * @return FormInterface
     */
    public function getRequestForm(Request $request, array $options = [])
    {
        if ($request->getId()) {
            $options['action'] = $this->generateUrl(
                self::RFP_REQUEST_UPDATE_ROUTE_NAME,
                ['id' => $request->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::RFP_REQUEST_CREATE_ROUTE_NAME
            );
        }

        return $this->getForm(RequestType::NAME, $request, $options);
    }
}
