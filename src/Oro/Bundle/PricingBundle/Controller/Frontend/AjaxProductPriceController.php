<?php

namespace Oro\Bundle\PricingBundle\Controller\Frontend;

use Oro\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_frontend_price_by_customer")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_frontend_matching_price")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get('oro_pricing.provider.matching_price')->getMatchingPrices(
            $lineItems,
            $this->get('oro_pricing.model.product_price_scope_criteria_request_handler')->getPriceScopeCriteria()
        );

        return new JsonResponse($matchedPrices);
    }

    /**
     * @todo BB-14587/BB-15426 Do we really need this route? >
     * @todo < It's mentioned in ororfp/js/app/views/line-item-view unitLoaderRouteName which is never used
     *
     * @Route("/get-product-units-by-currency", name="oro_pricing_frontend_units_by_pricelist")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductUnitsByCurrencyAction(Request $request)
    {
        /** @var ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler */
        $scopeCriteriaRequestHandler = $this->container
            ->get('oro_pricing.model.product_price_scope_criteria_request_handler');
        $scopeCriteria = $scopeCriteriaRequestHandler->getPriceScopeCriteria();

        /** @var ProductPriceProviderInterface $priceProvider */
        $priceProvider = $this->container->get('oro_pricing.provider.product_price');

        /** @var UnitLabelFormatterInterface $unitFormatter */
        $unitFormatter = $this->container->get('oro_product.formatter.product_unit_label');

        $productId = $request->get('id');
        $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');
        $product = $doctrineHelper->getEntityReference(Product::class, $productId);
        $currency = $request->get('currency');
        $prices = $priceProvider->getPricesByScopeCriteriaAndProducts($scopeCriteria, [$product], $currency);

        $units = [];
        if (!empty($prices[$productId])) {
            $units = array_map(function (array $price) use ($doctrineHelper) {
                return $doctrineHelper->getEntityReference(ProductUnit::class, $price['unit']);
            }, $prices[$productId]);
        }

        return new JsonResponse(['units' => $unitFormatter->formatChoices($units)]);
    }

    /**
     * @Route("/set-current-currency", name="oro_pricing_frontend_set_current_currency")
     * @Method({"POST"})
     *
     * {@inheritdoc}
     */
    public function setCurrentCurrencyAction(Request $request)
    {
        $currency = $request->get('currency');
        $result = false;
        $userCurrencyManager = $this->get('oro_pricing.user_currency_manager');
        if (in_array($currency, $userCurrencyManager->getAvailableCurrencies(), true)) {
            $userCurrencyManager->saveSelectedCurrency($currency);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }
}
