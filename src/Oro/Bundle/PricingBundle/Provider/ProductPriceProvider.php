<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class ProductPriceProvider implements ProductPriceProviderInterface
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @param ManagerRegistry $registry
     * @param ShardManager $shardManager
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    public function getPricesAsArrayByScopeCriteriaAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        $currency = null
    ) {
        $result = [];
        foreach ($this->getPricesByScopeCriteriaAndProductIds($scopeCriteria, $productIds, $currency) as $price) {
            $result[$price->getProduct()->getId()][] = [
                'price' => $price->getPrice()->getValue(),
                'currency' => $price->getPrice()->getCurrency(),
                'quantity' => $price->getQuantity(),
                'unit' => $price->getUnit()->getCode(),
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPricesByScopeCriteriaAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        $currency = null
    ) {
        $result = [];
        $priceList = $this->getPriceListByScopeCriteria($scopeCriteria);
        if (!$priceList) {
            return $result;
        }

        $prices = $this->getRepository()->findByPriceListIdAndProductIds(
            $this->shardManager,
            $priceList->getId(),
            $productIds,
            true,
            $currency
        );

        if ($prices) {
            foreach ($prices as $price) {
                $result[$price->getProduct()->getId()][] = [
                    'price' => $price->getPrice()->getValue(),
                    'currency' => $price->getPrice()->getCurrency(),
                    'quantity' => $price->getQuantity(),
                    'unit' => $price->getUnit()->getCode(),
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchedPrices(array $productsPriceCriteria, ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        $priceList = $this->getPriceListByScopeCriteria($scopeCriteria);
        if (!$priceList) {
            return [];
        }

        $productIds = [];
        $productUnitCodes = [];

        foreach ($productsPriceCriteria as $productPriceCriteria) {
            $productIds[] = $productPriceCriteria->getProduct()->getId();
            $productUnitCodes[] = $productPriceCriteria->getProductUnit()->getCode();
        }

        $prices = $this->getRepository()->getPricesBatch(
            $this->shardManager,
            $priceList->getId(),
            $productIds,
            $productUnitCodes,
            []
        );

        $result = [];

        foreach ($productsPriceCriteria as $productPriceCriteria) {
            $id = $productPriceCriteria->getProduct()->getId();
            $code = $productPriceCriteria->getProductUnit()->getCode();
            $quantity = $productPriceCriteria->getQuantity();
            $currency = $productPriceCriteria->getCurrency();
            $precision = $productPriceCriteria->getProductUnit()->getDefaultPrecision();

            $productPrices = array_filter(
                $prices,
                function (array $price) use ($id, $code, $currency) {
                    return (int)$price['id'] === $id && $price['code'] === $code && $price['currency'] === $currency;
                }
            );

            list($price, $matchedQuantity) = $this->matchPriceByQuantity($productPrices, $quantity);
            if ($price !== null) {
                $result[$productPriceCriteria->getIdentifier()] = Price::create(
                    $this->recalculatePricePerUnit($price, $matchedQuantity, $precision),
                    $currency
                );
            } else {
                $result[$productPriceCriteria->getIdentifier()] = null;
            }
        }

        return $result;
    }

    /**
     * @param float $price
     * @param float $quantityPerAmount
     * @param int $precision
     * @return float
     */
    protected function recalculatePricePerUnit($price, $quantityPerAmount, $precision)
    {
        return $precision > 0 ?
            $price / $quantityPerAmount :
            $price;
    }

    /**
     * @param array $prices
     * @param float $expectedQuantity
     * @return array
     */
    protected function matchPriceByQuantity(array $prices, $expectedQuantity)
    {
        $price = null;
        $matchedQuantity = null;
        foreach ($prices as $productPrice) {
            $quantity = (float)$productPrice['quantity'];

            if ($expectedQuantity >= $quantity) {
                $price = (float)$productPrice['value'];
                $matchedQuantity = $quantity;
            }
        }

        return [$price, $matchedQuantity];
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass(CombinedProductPrice::class)
            ->getRepository(CombinedProductPrice::class);
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return null|CombinedPriceList
     */
    protected function getPriceListByScopeCriteria(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return $this->priceListTreeHandler->getPriceList($scopeCriteria->getCustomer(), $scopeCriteria->getWebsite());
    }
}
