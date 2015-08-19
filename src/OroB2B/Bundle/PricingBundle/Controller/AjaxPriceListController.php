<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PaymentTermRepository;

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
                'message' => $this->getTranslator()->trans(
                    'orob2b.pricing.pricelist.set_default.message',
                    [
                        '{{ priceListName }}' => $priceList->getName()
                    ]
                )
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
     * @Route("/get-pricelist-currency-list/{id}",
     *      name="orob2b_pricing_price_list_currency_list",
     *      requirements={"id"="\d+"})
     * @AclAncestor("orob2b_product_update")
     *
     * @param PriceList $priceList
     * @return JsonResponse
     */
    public function getPriceListCurrencyList(PriceList $priceList)
    {
        $currencyNames = Intl::getCurrencyBundle()->getCurrencyNames($this->get('oro_locale.settings')->getLocale());

        $currencies = array_intersect_key($currencyNames, array_fill_keys($priceList->getCurrencies(), null));

        return new JsonResponse($currencies);
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
