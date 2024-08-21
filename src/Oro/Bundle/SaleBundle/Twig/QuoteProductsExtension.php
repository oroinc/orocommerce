<?php

namespace Oro\Bundle\SaleBundle\Twig;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve products from a quote:
 *   - quote_products
 */
class QuoteProductsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getLocalizationHelper(): LocalizationHelper
    {
        return $this->container->get(LocalizationHelper::class);
    }

    private function getEntityNameResolver(): EntityNameResolver
    {
        return $this->container->get(EntityNameResolver::class);
    }

    private function getProductName(?Product $product): ?string
    {
        return $this->getEntityNameResolver()->getName(
            $product,
            EntityNameProviderInterface::FULL,
            $this->getLocalizationHelper()->getCurrentLocalization()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new TwigFunction('quote_products', [$this, 'getQuoteProducts'])];
    }

    /**
     * @param Quote $request
     *
     * @return array
     */
    public function getQuoteProducts(Quote $request)
    {
        $result = [];
        foreach ($request->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            $data['name'] = $this->getProductName($product) ?? $quoteProduct->getFreeFormProduct();
            $data['sku'] = $quoteProduct->getProductSku();
            $data['comment'] = $quoteProduct->getComment();
            $data['commentCustomer'] = $quoteProduct->getCommentCustomer();
            $data['sellerName'] = $quoteProduct->getProduct()?->getOrganization()->getName();

            $items = [];
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $items[] = [
                    'quantity' => $quoteProductOffer->getQuantity(),
                    'price' => $quoteProductOffer->getPrice(),
                    'unit' => $quoteProductOffer->getProductUnitCode()
                ];
            }

            $data['items'] = $items;
            $data['kitItemLineItems'] = $this->getKitItemLineItemsData($quoteProduct);

            $result[] = $data;
        }

        return $result;
    }

    protected function getKitItemLineItemsData(QuoteProduct $quoteProduct): array
    {
        $kitItemLineItemsData = [];
        foreach ($quoteProduct->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemData['kitItemLabel'] = $this->getLocalizationHelper()->getLocalizedValue(
                $kitItemLineItem->getKitItem()->getLabels()
            );
            $kitItemLineItemData['unit'] = $kitItemLineItem->getProductUnit();
            $kitItemLineItemData['quantity'] = $kitItemLineItem->getQuantity();
            $kitItemLineItemData['productName'] = $this->getProductName($kitItemLineItem->getProduct());
            $kitItemLineItemData['productSku'] = $kitItemLineItem->getProductSku();

            $kitItemLineItemsData[] = $kitItemLineItemData;
        }

        return $kitItemLineItemsData;
    }

    public static function getSubscribedServices(): array
    {
        return [
            LocalizationHelper::class,
            EntityNameResolver::class,
        ];
    }
}
