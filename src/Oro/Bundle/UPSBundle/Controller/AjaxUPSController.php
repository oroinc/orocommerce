<?php

namespace Oro\Bundle\UPSBundle\Controller;

use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\AddressBundle\Entity\Country;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxUPSController extends Controller
{
    /**
     * @Route("/get-shipping-services-by-country/{code}",
     *      name="oro_ups_country_shipping_services",
     *      requirements={"code"="^[A-Z]{2}$"})
     * @ParamConverter("country", options={"id" = "code"})
     * @Method("GET")
     * @param Country $country
     * @return JsonResponse
     */
    public function getShippingServicesByCountry(Country $country)
    {
        /** @var ShippingServiceRepository $repository */
        $repository = $this->container
            ->get('doctrine')
            ->getRepository('OroUPSBundle:ShippingService');
        $services = $repository->getShippingServicesByCountry($country);
        $result = [];
        foreach ($services as $service) {
            $result[] = ['id' => $service->getId(), 'description' => $service->getDescription()];
        }
        return new JsonResponse($result);
    }
}
