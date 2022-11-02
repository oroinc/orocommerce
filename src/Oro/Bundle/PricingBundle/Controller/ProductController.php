<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for sidebar placeholder shown on product index page.
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/sidebar", name="oro_pricing_price_product_sidebar")
     * @Template
     *
     * @param Request $request
     * @return array
     */
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
        return $this->get(PriceListRequestHandlerInterface::class);
    }

    protected function isPriceListsEnabled(): bool
    {
        return $this->container->get(FeatureChecker::class)->isFeatureEnabled('oro_price_lists');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
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
