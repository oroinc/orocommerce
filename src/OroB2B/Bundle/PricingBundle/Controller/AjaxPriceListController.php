<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class AjaxPriceListController extends Controller
{
    /**
     * @Route("/default/{id}", name="orob2b_pricing_price_list_default", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_pricing_price_list_update")
     *
     * @param PriceList $priceList
     * @return JsonResponse
     */
    public function defaultAction(PriceList $priceList)
    {
        $successful = true;

        try {
            $this->getRepository()->setDefault($priceList);
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Set default price list failed: %s: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );

            $successful = false;
        }

        return new JsonResponse(['successful' => $successful]);
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->container
            ->get('doctrine')
            ->getRepository($this->container->getParameter('orob2b_pricing.entity.price_list.class'));
    }
}
