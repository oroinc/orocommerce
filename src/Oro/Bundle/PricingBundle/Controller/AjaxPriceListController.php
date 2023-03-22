<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Price list AJAX controller.
 */
class AjaxPriceListController extends AbstractController
{
    /**
     * Get price list currencies.
     *
     * @Route("/get-pricelist-currency-list/{id}",
     *      name="oro_pricing_price_list_currency_list",
     *      requirements={"id"="\d+"})
     * @AclAncestor("oro_product_update")
     *
     * @param PriceList $priceList
     * @return JsonResponse
     */
    public function getPriceListCurrencyListAction(PriceList $priceList)
    {
        $currencyNames = Currencies::getNames($this->get(LocaleSettings::class)->getLocale());

        $currencies = array_intersect_key($currencyNames, array_fill_keys($priceList->getCurrencies(), null));

        return new JsonResponse($currencies);
    }

    protected function getRepository(): PriceListRepository
    {
        return $this->container
            ->get(ManagerRegistry::class)
            ->getRepository(PriceList::class);
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            LocaleSettings::class,
            ManagerRegistry::class,
            TranslatorInterface::class,
            LoggerInterface::class,
        ]);
    }
}
