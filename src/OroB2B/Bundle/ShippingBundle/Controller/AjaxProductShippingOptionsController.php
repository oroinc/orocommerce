<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

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
        /* @var $provider AbstractMeasureUnitProvider */
        $provider = $this->get('orob2b_shipping.provider.measure_units.freight');

        $codes = $provider->formatUnitsCodes($provider->getUnitsCodes(), (bool)$request->get('short', false));

        return new JsonResponse(
            [
                'units' => array_combine($codes, $codes),
            ]
        );
    }
}
