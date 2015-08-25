<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;

class ProductPriceProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var FrontendPriceListRequestHandler
     */
    protected $requestHandler;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param ManagerRegistry $registry
     * @param FrontendPriceListRequestHandler $requestHandler
     * @param string $className
     */
    public function __construct(ManagerRegistry $registry, FrontendPriceListRequestHandler $requestHandler, $className)
    {
        $this->registry = $registry;
        $this->requestHandler = $requestHandler;
        $this->className = $className;
    }

    /**
     * @param int $priceListId
     * @param array $productIds
     * @param string|null $currency
     * @return array
     */
    public function getPriceByPriceListIdAndProductIds($priceListId, array $productIds, $currency = null)
    {
        $result = [];
        $prices = $this->getRepository()->findByPriceListIdAndProductIds($priceListId, $productIds, true, $currency);

        if ($prices) {
            foreach ($prices as $price) {
                $result[$price->getProduct()->getId()][$price->getUnit()->getCode()][] = [
                    'price' => $price->getPrice()->getValue(),
                    'currency' => $price->getPrice()->getCurrency(),
                    'qty' => $price->getQuantity()
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $productUnitQuantities
     * @param string $currency
     * @param PriceList|null $priceList
     * @return array
     */
    public function matchPrices(array $productUnitQuantities, $currency, PriceList $priceList = null)
    {
        if (!$priceList) {
            $priceList = $this->requestHandler->getPriceList();
        }

        $productIds = [];
        $productUnitCodes = [];

        /** @var ProductUnitQuantity[] $productUnitQuantities */
        foreach ($productUnitQuantities as $productUnitQuantity) {
            $productIds[] = $productUnitQuantity->getProduct()->getId();
            $productUnitCodes[] = $productUnitQuantity->getProductUnit()->getCode();
        }

        $prices = $this->getRepository()->getPricesByPriceListIdAndProductIdsAndUnitCodesAndCurrencies(
            $priceList->getId(),
            $productIds,
            $productUnitCodes,
            []
        );

        $result = [];

        foreach ($productUnitQuantities as $productUnitQuantity) {
            $id = $productUnitQuantity->getProduct()->getId();
            $code = $productUnitQuantity->getProductUnit()->getCode();
            $quantity = $productUnitQuantity->getQuantity();

            $productPrices = array_filter(
                $prices,
                function (array $price) use ($id, $code, $currency) {
                    return $price['id'] === $id && $price['code'] === $code && $price['currency'] === $currency;
                }
            );

            $price = $this->matchPriceByQuantity($productPrices, $quantity);

            if (!$price) {
                $price = $this->matchPriceByQuantity($prices, $quantity);
            }

            $key = sprintf('%s-%s-%s', $id, $code, $quantity);

            $result[$key] = $price ? Price::create($price, $currency) : null;
        }

        return $result;
    }

    /**
     * @param array $prices
     * @param float $expectedQuantity
     * @return float
     */
    protected function matchPriceByQuantity(array $prices, $expectedQuantity)
    {
        $price = 0.0;

        usort(
            $prices,
            function ($a, $b) {
                if ($a['quantity'] === $b['quantity']) {
                    return 0;
                }
                return ($a['quantity'] < $b['quantity']) ? -1 : 1;
            }
        );

        foreach ($prices as $productPrice) {
            $quantity = (float)$productPrice['quantity'];

            if ($expectedQuantity < $quantity) {
                break;
            }

            if ($expectedQuantity >= $quantity) {
                $price = (float)$productPrice['value'];
            } else {
                break;
            }
        }

        return $price;
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->className)->getRepository($this->className);
    }
}
