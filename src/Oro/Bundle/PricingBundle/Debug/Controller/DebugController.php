<?php

namespace Oro\Bundle\PricingBundle\Debug\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\MultiWebsiteBundle\Form\Type\WebsiteSelectType;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceMergeInfoProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\ProductPricesProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
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
        $sidebarData = [];

        $currenciesForm = $this->createCurrenciesForm();
        if ($currenciesForm) {
            $sidebarData['currencies'] = $currenciesForm->createView();
        }
        $sidebarData['showTierPrices'] = $this->createShowTierPricesForm()->createView();

        $sidebarData['websites'] = $this->createWebsiteForm()->createView();
        $sidebarData['customers'] = $this->createCustomersForm()->createView();

        return $sidebarData;
    }

    /**
     * @Route("/sidebar-view/{id}", name="oro_pricing_price_product_debug_sidebar_view", requirements={"id"="\d+"})
     * @Template
     *
     * @return array
     */
    public function sidebarViewAction(Product $product)
    {
        $sidebarData = [
            'product' => $product
        ];

        $sidebarData['websites'] = $this->createWebsiteForm()->createView();
        $sidebarData['customers'] = $this->createCustomersForm()->createView();
        $sidebarData['date'] = $this->createDateForm()->createView();
        $sidebarData['showDetailedAssignmentInfo'] = $this->createShowDetailedAssignmentInfoForm()->createView();
        $sidebarData['showDevelopersInfo'] = $this->createShowDevelopersInfoForm()->createView();

        return $sidebarData;
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

        $fullChainCpl = $this->getPriceListHandler()->getFullChainCpl();
        $usedPriceLists = $this->getCplUsedPriceLists($cpl);

        $fullChainCplId = null;
        $usedPriceListsFullCpl = null;
        if ($fullChainCpl && $fullChainCpl->getId() !== $cpl?->getId()) {
            $fullChainCplId = $fullChainCpl->getId();
            $usedPriceListsFullCpl = $this->getCplUsedPriceLists($fullChainCpl);
        }

        $showDevelopersInfo = $this->getPriceListHandler()->getShowDevelopersInfo();

        $data = [
            'product' => $product,
            'current_prices' => $this->getCurrentPrices($product),
            'price_merging_details' => $this->getPriceMergingDetails($usedPriceLists, $product),
            'full_cpl_used_price_lists' => $usedPriceListsFullCpl ?: $usedPriceLists,
            'show_developers_info' => $showDevelopersInfo
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

    private function getCplUsedPriceLists(?CombinedPriceList $cpl): array
    {
        if (!$cpl) {
            return [];
        }

        return $this->getDoctrine()->getRepository(CombinedPriceListToPriceList::class)->getPriceListRelations($cpl);
    }

    protected function createCurrenciesForm(): ?FormInterface
    {
        $availableCurrencies = $this->getPriceListCurrencies();
        $selectedCurrencies = $this->getPriceListHandler()
            ->getPriceListSelectedCurrencies($this->getPriceListHandler()->getPriceList());

        if (count($availableCurrencies) <= 1) {
            return null;
        }

        return $this->createForm(
            CurrencySelectionType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.currencies.label',
                'expanded' => true,
                'compact' => true,
                'multiple' => true,
                'csrf_protection' => false,
                'required' => false,
                'currencies_list' => $availableCurrencies,
                'data' => $selectedCurrencies,
            ]
        );
    }

    protected function createShowTierPricesForm(): FormInterface
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_tier_prices.label',
                'required' => false,
                'data' => $this->getPriceListHandler()->getShowTierPrices(),
            ]
        );
    }

    protected function createShowDetailedAssignmentInfoForm(): FormInterface
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_detailed_assignment_info.label',
                'required' => false,
                'data' => $this->getPriceListHandler()->getShowDetailedAssignmentInfo(),
            ]
        );
    }

    protected function createShowDevelopersInfoForm(): FormInterface
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_developers_info.label',
                'required' => false,
                'data' => $this->getPriceListHandler()->getShowDevelopersInfo(),
            ]
        );
    }

    protected function createWebsiteForm(): FormInterface
    {
        $website = $this->getPriceListHandler()->getWebsite();

        return $this->createForm(
            WebsiteSelectType::class,
            $website,
            [
                'label' => 'oro.website.entity_label',
                'required' => false,
                'empty_data' => $website,
                'create_enabled' => false
            ]
        );
    }

    protected function createCustomersForm(): FormInterface
    {
        $customer = $this->getPriceListHandler()->getCustomer();

        return $this->createForm(
            CustomerSelectType::class,
            $customer,
            [
                'label' => 'oro.customer.entity_label',
                'required' => false,
                'empty_data' => $customer,
                'create_enabled' => false
            ]
        );
    }

    protected function createDateForm(): FormInterface
    {
        $date = $this->getPriceListHandler()->getSelectedDate();

        return $this->createForm(
            OroDateTimeType::class,
            $date,
            [
                'label' => 'oro.pricing.productprice.debug.show_for_date.label',
                'required' => false,
                'empty_data' => $date
            ]
        );
    }

    /**
     * @return DebugProductPricesPriceListRequestHandler
     */
    protected function getPriceListHandler()
    {
        return $this->get(DebugProductPricesPriceListRequestHandler::class);
    }

    private function getPriceListCurrencies(): array
    {
        $currencies = (array)$this->getPriceListHandler()->getPriceList()?->getCurrencies();
        sort($currencies);

        return $currencies;
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
                CombinedPriceListActivationRulesProvider::class
            ]
        );
    }
}
