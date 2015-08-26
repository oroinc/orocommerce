<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class AbstractAjaxProductPriceController extends Controller
{
    /** @var EntityManager[] */
    protected $managers = [];

    /**
     * Get products prices by price list and product ids
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByPriceListAction(Request $request)
    {
        return new JsonResponse(
            $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
                $request->get('price_list_id'),
                $request->get('product_ids', []),
                $request->get('currency')
            )
        );
    }

    /**
     * @param array $lineItems
     * @return array
     */
    protected function prepareProductUnitQuantities(array $lineItems)
    {
        $productUnitQuantities = [];

        foreach ($lineItems as $lineItem) {
            $productId = $this->getLineItemData($lineItem, 'product');
            $productUnitCode = $this->getLineItemData($lineItem, 'unit');

            if ($productId && $productUnitCode) {
                /** @var Product $product */
                $product = $this->getEntityReference(
                    $this->getParameter('orob2b_product.product.class'),
                    $productId
                );

                /** @var ProductUnit $unit */
                $unit = $this->getEntityReference(
                    $this->getParameter('orob2b_product.product_unit.class'),
                    $productUnitCode
                );

                $quantity = (float)$this->getLineItemData($lineItem, 'qty');

                $productUnitQuantities[] = new ProductUnitQuantity($product, $unit, $quantity);
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

    /**
     * @param array $lineItem
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getLineItemData(array $lineItem, $key, $default = null)
    {
        $data = $default;
        if (array_key_exists($key, $lineItem)) {
            $data = $lineItem[$key];
        }

        return $data;
    }

    /**
     * @param string $class
     * @param mixed $id
     * @return object
     */
    protected function getEntityReference($class, $id)
    {
        return $this->getManagerForClass($class)->getReference($class, $id);
    }

    /**
     * @param string $class
     * @return EntityManager
     */
    protected function getManagerForClass($class)
    {
        if (!array_key_exists($class, $this->managers)) {
            $this->managers[$class] = $this->getDoctrine()->getManagerForClass($class);
        }

        return $this->managers[$class];
    }
}
