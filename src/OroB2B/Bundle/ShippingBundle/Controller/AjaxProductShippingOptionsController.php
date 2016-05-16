<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ShippingBundle\Provider\MeasureUnitProvider;

class AjaxProductShippingOptionsController extends Controller
{
    /**
     * Get available FreightClasses codes
     *
     * @Route("/freight-classes", name="orob2b_shipping_freight_classes")
     * @Method({"GET"})
     * @AclAncestor("orob2b_product_update")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableProductUnitFreightClasses(Request $request)
    {
        /* @var $provider MeasureUnitProvider */
        $provider = $this->get('orob2b_shipping.provider.measure_units.freight');

        return new JsonResponse(
            [
                'units' => $provider->getFormattedUnits((bool)$request->get('short', false)),
            ]
        );
    }
}
