<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Provides a {@see Request} entity form for a use on a storefront.
 */
class RFPFormProvider extends AbstractFormProvider
{
    public const RFP_REQUEST_CREATE_ROUTE_NAME = 'oro_rfp_frontend_request_create';
    public const RFP_REQUEST_UPDATE_ROUTE_NAME = 'oro_rfp_frontend_request_update';

    /**
     * @param Request $request
     *
     * @return FormView
     */
    public function getRequestFormView(Request $request)
    {
        $options = $this->getFormOptions($request);

        return $this->getFormView(RequestType::class, $request, $options);
    }

    /**
     * @param Request $request
     *
     * @return FormInterface
     */
    public function getRequestForm(Request $request)
    {
        $options = $this->getFormOptions($request);

        return $this->getForm(RequestType::class, $request, $options);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getFormOptions(Request $request)
    {
        $options = [];

        if ($request->getId()) {
            $options['action'] = $this->generateUrl(
                self::RFP_REQUEST_UPDATE_ROUTE_NAME,
                ['id' => $request->getId()]
            );
            $options['validation_groups'] = new GroupSequence(['Default', 'frontend_request_update']);
        } else {
            $options['action'] = $this->generateUrl(
                self::RFP_REQUEST_CREATE_ROUTE_NAME
            );
            $options['validation_groups'] = new GroupSequence(['Default', 'frontend_request_create']);
        }

        return $options;
    }
}
