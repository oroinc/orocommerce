<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;

class AbstractAjaxProductPriceController extends Controller
{
    /**
     * Get products prices by price list and product ids
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByPriceListAction(Request $request)
    {
        $params = $request->query->all();

        $priceListId = isset($params['price_list_id']) ? $params['price_list_id'] : null;
        $productIds = isset($params['product_ids']) ? $params['product_ids'] : [];
        $currency = isset($params['currency']) ? $params['currency'] : null;

        $currencies = $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
            $priceListId,
            $productIds,
            $currency
        );

        return new JsonResponse($currencies);
    }

    /**
     * @param $lineItems
     * @return array
     */
    protected function prepareProductUnitQuantities($lineItems)
    {
        $productUnitQuantities = [];

        foreach ($lineItems as $lineItem) {
            $quantity = null;
            if (isset($lineItem['qty'])) {
                $quantity = $lineItem['qty'];
            }

            $productId = null;
            if (isset($lineItem['product'])) {
                $productId = $lineItem['product'];
            }

            $productUnitCode = null;
            if (isset($lineItem['unit'])) {
                $productUnitCode = $lineItem['unit'];
            }

            if ($productId && $productUnitCode) {
                $em = $this->getDoctrine()->getManagerForClass('OroB2BProductBundle:Product');
                $product = $em->getReference('OroB2BProductBundle:Product', $productId);

                $em = $this->getDoctrine()->getManagerForClass('OroB2BProductBundle:ProductUnit');
                $unitCode = $em->getReference('OroB2BProductBundle:ProductUnit', $productUnitCode);

                $productUnitQuantities[] = new ProductUnitQuantity($product, $unitCode, (float)$quantity);
            }
        }

        return $productUnitQuantities;
    }

    /**
     * @param Price[] $matchedPrice
     * @return array
     */
    protected function formatMatchedPrices(array $matchedPrice)
    {
        $result = [];
        foreach ($matchedPrice as $key => $price) {
            if ($price) {
                $result[$key]['value'] = $price->getValue();
                $result[$key]['currency'] = $price->getCurrency();
            }
        }

        return $result;
    }
}
