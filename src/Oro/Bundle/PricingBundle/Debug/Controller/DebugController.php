<?php

namespace Oro\Bundle\PricingBundle\Debug\Controller;

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
     * @Route("/index", name="oro_pricing_price_product_debug_index")
     * @Template
     *
     * @return array
     */
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
     * @Route("/sidebar", name="oro_pricing_price_product_debug_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return $this->get(SidebarFormProvider::class)->getIndexPageSidebarFormElements();
    }

    /**
     * @Route("/sidebar-view/{id}", name="oro_pricing_price_product_debug_sidebar_view", requirements={"id"="\d+"})
     * @Template
     *
     * @return array
     */
    public function sidebarViewAction(Product $product)
    {
        return $this->get(SidebarFormProvider::class)->getViewPageSidebarFormElements($product);
    }

    /**
     * @Route("/trace/{id}", name="oro_pricing_price_product_debug_trace", requirements={"id"="\d+"})
     * @Template
     *
     * @return array
     */
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
     * @Route("/get-currency-list", name="oro_pricing_price_product_debug_currency_list")
     *
     * @return JsonResponse
     */
    public function getPriceListCurrencyListAction()
    {
        $currencyNames = Currencies::getNames($this->get(LocaleSettings::class)->getLocale());
        $currencies = array_intersect_key($currencyNames, array_fill_keys($this->getPriceListCurrencies(), null));

        ksort($currencies);

        return new JsonResponse($currencies);
    }

    private function getActivationRules(?CombinedPriceList $priceList): iterable
    {
        if (!$priceList) {
            return [];
        }

        $provider = $this->get(CombinedPriceListActivationRulesProvider::class);
        if (!$provider->hasActivationRules($priceList)) {
            return [];
        }

        return $provider->getActivationRules($priceList);
    }

    private function getPriceListAssignments(): ?array
    {
        return $this->get(PriceListsAssignmentProvider::class)->getPriceListAssignments();
    }

    private function getCurrentPrices(Product $product): array
    {
        return $this->get(ProductPricesProvider::class)->getCurrentPrices($product);
    }

    private function getPriceMergingDetails(array $usedPriceLists, Product $product): array
    {
        return $this->get(PriceMergeInfoProvider::class)->getPriceMergingDetails($usedPriceLists, $product);
    }

    private function getUsedUnitsAndCurrencies(array $priceMergeDetails): array
    {
        return $this->get(PriceMergeInfoProvider::class)->getUsedUnitsAndCurrencies($priceMergeDetails);
    }

    private function getCplUsedPriceLists(?CombinedPriceList $cpl): array
    {
        if (!$cpl) {
            return [];
        }

        return $this->getDoctrine()->getRepository(CombinedPriceListToPriceList::class)->getPriceListRelations($cpl);
    }

    private function getPriceListHandler(): DebugProductPricesPriceListRequestHandler
    {
        return $this->get(DebugProductPricesPriceListRequestHandler::class);
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
        return $this->get(PriceMergeInfoProvider::class)
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

    /**
     * {@inheritdoc}
     */
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
                SidebarFormProvider::class
            ]
        );
    }
}
