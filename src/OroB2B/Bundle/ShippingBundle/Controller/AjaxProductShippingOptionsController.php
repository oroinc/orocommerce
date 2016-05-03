<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

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
        // ToDo: use service instead
        $isShort = (bool)$request->get('short', false);
        $className = $this->getParameter('orob2b_shipping.entity.freight_class.class');
        $repository = $this
            ->getDoctrine()
            ->getManagerForClass($className)
            ->getRepository($className);

        /** @var UnitLabelFormatter $formatter */
        $formatter = $this->get('orob2b_shipping.formatter.freight_class_label');
        $codes = array_map(
            function (MeasureUnitInterface $entity) {
                return $entity->getCode();
            },
            $repository->findAll()
        );
        $codes = array_combine($codes, $codes);
        $codes = array_map(
            function ($code) use ($formatter, $isShort) {
                return $formatter->format($code, $isShort);
            },
            $codes
        );

        ksort($codes);

        return new JsonResponse(
            [
                'units' => $codes,
            ]
        );
    }
}
