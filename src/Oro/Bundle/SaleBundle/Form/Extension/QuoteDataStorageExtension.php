<?php

namespace Oro\Bundle\SaleBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The form type extension that pre-fills a quote with requested products taken from the product data storage.
 */
class QuoteDataStorageExtension extends AbstractProductDataStorageExtension
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;
    private QuoteProductPricesProvider $quoteProductPricesProvider;

    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        PropertyAccessorInterface $propertyAccessor,
        ManagerRegistry $doctrine,
        LineItemChecksumGeneratorInterface $lineItemChecksumGenerator,
        LoggerInterface $logger,
        QuoteProductPricesProvider $quoteProductPricesProvider
    ) {
        parent::__construct($requestStack, $storage, $propertyAccessor, $doctrine, $logger);

        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
        $this->quoteProductPricesProvider = $quoteProductPricesProvider;
    }

    #[\Override]
    protected function addItem(Product $product, object $entity, array $itemData): void
    {
        /** @var Quote $entity */

        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($product);

        $this->fillEntityData($quoteProduct, $itemData);
        $this->addKitItemLineItems($quoteProduct, $itemData);

        if (!empty($itemData['requestProductItems'])) {
            $this->addItems($product, $quoteProduct, $itemData['requestProductItems']);
        }

        $entity->addQuoteProduct($quoteProduct);
    }

    /**
     * @param object|Quote $entity
     * @param array $itemsData
     * @return void
     */
    protected function fillItemsData(object $entity, array $itemsData): void
    {
        parent::fillItemsData($entity, $itemsData);

        $prices = $this->quoteProductPricesProvider->getProductLineItemsTierPrices($entity);
        foreach ($entity->getQuoteProducts() as $quoteProduct) {
            if ($quoteProduct->getProduct()?->isKit()) {
                foreach ($quoteProduct->getQuoteProductOffers() as $productOffer) {
                    $productId = $productOffer->getProduct()?->getId();
                    if (!$productId || !isset($prices[$productId])) {
                        $productOffer->setPrice(null);
                        continue;
                    }

                    if (!$productOffer->getPrice()?->getCurrency()) {
                        continue;
                    }

                    $pricesByOffer = $prices[$productId][$productOffer->getChecksum()] ?? [];

                    $priceDto = $this->getTierPriceByScopeCriteria(
                        $pricesByOffer,
                        $productOffer->getQuantity(),
                        $productOffer->getProductUnit()->getCode(),
                        $productOffer->getPrice()->getCurrency()
                    );

                    $productOffer->setPrice($priceDto?->getPrice());
                }
            }
        }
    }

    /**
     * Get tier prices by scope:
     *  1  QTY - 10$
     *  5  QTY - 8$
     *  10 QTY - 5$
     */
    private function getTierPriceByScopeCriteria(
        array $prices,
        float $qty,
        string $unit,
        string $currency
    ): ?ProductPriceDTO {
        $prices = \array_filter(
            $prices,
            function (ProductPriceDTO $priceDto) use ($unit, $currency) {
                return $priceDto->getUnit()->getCode() === $unit
                    && $priceDto->getPrice()->getCurrency() === $currency;
            }
        );

        if (\count($prices) === 0) {
            return null;
        }

        $matchedPrice = null;
        $matchedQuantity = 0;

        foreach ($prices as $price) {
            if ($matchedQuantity <= $qty && $qty >= $price->getQuantity()) {
                $matchedQuantity = $price->getQuantity();
                $matchedPrice = $price;
            }
        }

        return $matchedPrice;
    }

    private function addItems(Product $product, QuoteProduct $quoteProduct, array $itemsData): void
    {
        $defaultUnit = $this->getDefaultProductUnit($product);

        foreach ($itemsData as $subItemData) {
            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductOffer = new QuoteProductOffer();

            $quoteProductOffer->setAllowIncrements(true);

            $this->fillEntityData($quoteProductRequest, $subItemData);
            $this->fillEntityData($quoteProductOffer, $subItemData);

            if (null === $defaultUnit && !$quoteProductRequest->getProductUnit()) {
                continue;
            }

            if (!$quoteProductRequest->getProductUnit()) {
                $quoteProductRequest->setProductUnit($defaultUnit);
                $quoteProductOffer->setProductUnit($defaultUnit);
            }

            $quoteProduct->addQuoteProductRequest($quoteProductRequest);
            $quoteProduct->addQuoteProductOffer($quoteProductOffer);

            foreach ([$quoteProductRequest, $quoteProductOffer] as $quoteLineItem) {
                $checksum = $this->lineItemChecksumGenerator->getChecksum($quoteLineItem);
                if ($checksum !== null) {
                    $quoteLineItem->setChecksum($checksum);
                }
            }
        }
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Quote::class;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [QuoteType::class];
    }

    private function addKitItemLineItems(QuoteProduct $lineItem, array $itemData): void
    {
        $kitItemLineItemsData = $itemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY] ?? [];
        foreach ($kitItemLineItemsData as $kitItemLineItemData) {
            if (!$this->isKitItemLineItemDataValid($kitItemLineItemData)) {
                continue;
            }

            $kitItemLineItem = new QuoteProductKitItemLineItem();
            $this->fillEntityData($kitItemLineItem, $kitItemLineItemData);

            $lineItem->addKitItemLineItem($kitItemLineItem);
        }
    }

    private function isKitItemLineItemDataValid(array $kitItemLineItemData): bool
    {
        return isset(
            $kitItemLineItemData['kitItemId'],
            $kitItemLineItemData['kitItemLabel'],
            $kitItemLineItemData['productId'],
            $kitItemLineItemData['productName'],
            $kitItemLineItemData['productSku'],
            $kitItemLineItemData['productUnitCode']
        );
    }
}
