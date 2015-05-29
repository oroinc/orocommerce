<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

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
        try {
            $this->getRepository()->setDefault($priceList);

            $response = [
                'successful' => true,
                'message' => $this->getTranslator()->trans('orob2b.pricing.pricelist.set_default.message')
            ];

        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Set default price list failed: %s: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );

            $response = ['successful' => false];
        }

        return new JsonResponse($response);
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

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
