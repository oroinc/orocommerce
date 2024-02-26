<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for sidebar placeholder shown on product index page and product prices widget.
 */
class ProductController extends AbstractController
{
    /**
     * @param Request $request
     * @return array
     */
    #[Route(path: '/sidebar', name: 'oro_pricing_price_product_sidebar')]
    #[Template]
    public function sidebarAction(Request $request)
    {
        $sidebarData = [];

        $currenciesForm = $this->createCurrenciesForm($request);
        if ($currenciesForm) {
            $sidebarData['currencies'] = $currenciesForm->createView();
        }

        if ($this->isPriceListsEnabled()) {
            $sidebarData['priceList'] = $this->createPriceListForm()->createView();
            $sidebarData['showTierPrices'] = $this->createShowTierPricesForm()->createView();
        }

        return $sidebarData;
    }

    #[Route(
        path: '/widget/prices_update/{unit}/{precision}',
        name: 'oro_pricing_widget_prices_update',
        requirements: ['unit' => '\w+', 'precision' => '\d+']
    )]
    #[Template('@OroPricing/Product/widget/prices_update.html.twig')]
    #[AclAncestor('oro_product_update')]
    public function widgetPricesUpdateAction(ProductUnit $unit, int $precision): array
    {
        $product = new Product();

        $productUnit = new ProductUnitPrecision();
        $productUnit->setProduct($product)
            ->setUnit($unit)
            ->setPrecision($precision);

        $product->setPrimaryUnitPrecision($productUnit);

        $form = $this->createForm(ProductType::class, $product);

        return ['form' => $form->createView()];
    }

    /**
     * @return Form
     */
    protected function createPriceListForm()
    {
        $priceList = $this->getPriceListHandler()->getPriceList();

        return $this->createForm(
            PriceListSelectType::class,
            $priceList,
            [
                'create_enabled' => false,
                'placeholder' => false,
                'empty_data' => $priceList,
                'configs' => ['allowClear' => false],
                'label' => 'oro.pricing.pricelist.entity_label',
            ]
        );
    }

    /**
     * @param Request $request
     * @return Form|null
     */
    protected function createCurrenciesForm(Request $request)
    {
        if ($this->isPriceListsEnabled()) {
            $priceList = $this->getPriceListHandler()->getPriceList();
            $availableCurrencies = $priceList->getCurrencies();
            $selectedCurrencies = $this->getPriceListHandler()->getPriceListSelectedCurrencies($priceList);
            $showForm = true;
        } else {
            $availableCurrencies = $this->container->get(UserCurrencyManager::class)->getAvailableCurrencies();
            $selectedCurrencies = $request->get(PriceListRequestHandlerInterface::PRICE_LIST_CURRENCY_KEY);
            $showForm = count($availableCurrencies) > 1;
        }

        if (!$showForm) {
            return null;
        }

        return $this->createForm(
            CurrencySelectionType::class,
            null,
            [
                'label' => 'oro.pricing.pricelist.currencies.label',
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

    /**
     * @return Form
     */
    protected function createShowTierPricesForm()
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.show_tier_prices.label',
                'required' => false,
                'data' => $this->getPriceListHandler()->getShowTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandlerInterface
     */
    protected function getPriceListHandler()
    {
        return $this->container->get(PriceListRequestHandlerInterface::class);
    }

    protected function isPriceListsEnabled(): bool
    {
        return $this->container->get(FeatureChecker::class)->isFeatureEnabled('oro_price_lists');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                FeatureChecker::class,
                PriceListRequestHandlerInterface::class,
                UserCurrencyManager::class,
            ]
        );
    }
}
