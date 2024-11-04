<?php

namespace Oro\Bundle\PricingBundle\Debug\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceMergeInfoProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\ProductPricesProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\SidebarFormProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for sidebar placeholder shown on product index page.
 */
class DebugController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/index', name: 'oro_pricing_price_product_debug_index')]
    #[Template]
    public function indexAction()
    {
        $widgetRouteParameters = [
            'gridName' => 'pricing-products-trace-grid',
            'renderParams' => [
                'enableFullScreenLayout' => 1,
                'enableViews' => 0
            ],
            'renderParamsTypes' => [
                'enableFullScreenLayout' => 'int',
                'enableViews' => 'int'
            ]
        ];

        return [
            'entity_class' => Product::class,
            'widgetRouteParameters' => $widgetRouteParameters
        ];
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/sidebar', name: 'oro_pricing_price_product_debug_sidebar')]
    #[Template]
    public function sidebarAction()
    {
        return $this->container->get(SidebarFormProvider::class)->getIndexPageSidebarFormElements();
    }

    /**
     *
     * @return array
     */
    #[Route(
        path: '/sidebar-view/{id}',
        name: 'oro_pricing_price_product_debug_sidebar_view',
        requirements: ['id' => '\d+']
    )]
    #[Template]
    public function sidebarViewAction(Product $product)
    {
        return $this->container->get(SidebarFormProvider::class)->getViewPageSidebarFormElements($product);
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/trace/{id}', name: 'oro_pricing_price_product_debug_trace', requirements: ['id' => '\d+'])]
    #[Template]
    public function traceAction(Product $product)
    {
        $cpl = $this->getPriceListHandler()->getPriceList();
        $currentActiveCpl = $this->getPriceListHandler()->getCurrentActivePriceList();

        $fullChainCpl = $this->getPriceListHandler()->getFullChainCpl();
        $usedPriceLists = $this->getCplUsedPriceLists($cpl);

        $fullChainCplId = null;
        $usedPriceListsFullCpl = null;
        if ($fullChainCpl && $fullChainCpl->getId() !== $cpl?->getId()) {
            $fullChainCplId = $fullChainCpl->getId();
            $usedPriceListsFullCpl = $this->getCplUsedPriceLists($fullChainCpl);
        }

        $showDevelopersInfo = $this->getPriceListHandler()->getShowDevelopersInfo();

        $currentPrices = $this->getCurrentPrices($product);
        $priceMergeDetails = $this->getPriceMergingDetails($usedPriceLists, $product);

        $isActualizationRequired = $this->isActualizationRequired(
            $cpl,
            $currentActiveCpl,
            $priceMergeDetails,
            $currentPrices
        );

        $data = [
            'current_active_cpl' => $currentActiveCpl,
            'product' => $product,
            'current_prices' => $this->prepareCurrentPrices($currentPrices),
            'price_merging_details' => $priceMergeDetails,
            'used_units_and_currencies' => $this->getUsedUnitsAndCurrencies($priceMergeDetails),
            'full_cpl_used_price_lists' => $usedPriceListsFullCpl ?: $usedPriceLists,
            'show_developers_info' => $showDevelopersInfo,
            'requires_price_actualization' => $isActualizationRequired,
            'customer' => $this->getPriceListHandler()->getCustomer(),
            'calculation_start_date' => $this->getCalculationStartDate(),
            'view_date' =>
                $this->getPriceListHandler()->getSelectedDate() ?: new \DateTime('now', new \DateTimeZone('UTC'))
        ];

        if ($this->getPriceListHandler()->getShowDetailedAssignmentInfo()) {
            $data['price_list_assignments'] = $this->getPriceListAssignments();
        }

        if ($showDevelopersInfo) {
            $data['cpl_used_price_lists'] = $usedPriceLists;
            $data['cplId'] = $cpl?->getId();
            $data['fullChainCplId'] = $fullChainCplId;
            $data['cpl_activation_rules'] = $this->getActivationRules($fullChainCpl);
        }

        return $data;
    }

    /**
     * Get price list currencies.
     *
     *
     * @return JsonResponse
     */
    #[Route(path: '/get-currency-list', name: 'oro_pricing_price_product_debug_currency_list')]
    public function getPriceListCurrencyListAction()
    {
        $currencyNames = Currencies::getNames($this->container->get(LocaleSettings::class)->getLocale());
        $currencies = array_intersect_key($currencyNames, array_fill_keys($this->getPriceListCurrencies(), null));

        ksort($currencies);

        return new JsonResponse($currencies);
    }

    private function getActivationRules(?CombinedPriceList $priceList): iterable
    {
        if (!$priceList) {
            return [];
        }

        $provider = $this->container->get(CombinedPriceListActivationRulesProvider::class);
        if (!$provider->hasActivationRules($priceList)) {
            return [];
        }

        return $provider->getActivationRules($priceList);
    }

    private function getPriceListAssignments(): ?array
    {
        return $this->container->get(PriceListsAssignmentProvider::class)->getPriceListAssignments();
    }

    private function getCurrentPrices(Product $product): array
    {
        return $this->container->get(ProductPricesProvider::class)->getCurrentPrices($product);
    }

    private function getPriceMergingDetails(array $usedPriceLists, Product $product): array
    {
        return $this->container->get(PriceMergeInfoProvider::class)->getPriceMergingDetails($usedPriceLists, $product);
    }

    private function getUsedUnitsAndCurrencies(array $priceMergeDetails): array
    {
        return $this->container->get(PriceMergeInfoProvider::class)->getUsedUnitsAndCurrencies($priceMergeDetails);
    }

    private function getCplUsedPriceLists(?CombinedPriceList $cpl): array
    {
        if (!$cpl) {
            return [];
        }

        return $this->container->get(ManagerRegistry::class)
            ->getRepository(CombinedPriceListToPriceList::class)
            ->getPriceListRelations($cpl);
    }

    private function getPriceListHandler(): DebugProductPricesPriceListRequestHandler
    {
        return $this->container->get(DebugProductPricesPriceListRequestHandler::class);
    }

    private function getPriceListCurrencies(): array
    {
        $currencies = (array)$this->getPriceListHandler()->getPriceList()?->getCurrencies();
        sort($currencies);

        return $currencies;
    }

    private function isActualizationRequired(
        ?CombinedPriceList $cpl,
        ?CombinedPriceList $currentActiveCpl,
        array $priceMergeDetails,
        array $currentPrices
    ): bool {
        return $this->container->get(PriceMergeInfoProvider::class)
            ->isActualizationRequired($cpl, $currentActiveCpl, $priceMergeDetails, $currentPrices);
    }

    private function prepareCurrentPrices(array $currentPrices): array
    {
        $result = [];
        foreach ($currentPrices as $currency => $pricesByCurrency) {
            foreach ($pricesByCurrency as $price) {
                $result[$currency][$price['unitCode']][] = $price;
            }
        }

        return $result;
    }

    private function getCalculationStartDate(): ?\DateTime
    {
        $activationRule = $this->getPriceListHandler()->getCplActivationRule();
        if ($activationRule && $activationRule->getActivateAt()) {
            $offsetHours = $this->container->get(ConfigManager::class)
                ->get('oro_pricing.offset_of_processing_cpl_prices');
            $startCalculationDate = clone $activationRule->getActivateAt();
            $startCalculationDate->modify('-' . $offsetHours . ' hours');

            return $startCalculationDate;
        }

        return null;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LocaleSettings::class,
                DebugProductPricesPriceListRequestHandler::class,
                PriceListsAssignmentProvider::class,
                ProductPricesProvider::class,
                PriceMergeInfoProvider::class,
                CombinedPriceListActivationRulesProvider::class,
                ConfigManager::class,
                SidebarFormProvider::class,
                ManagerRegistry::class
            ]
        );
    }
}
