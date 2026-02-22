<?php

namespace Oro\Bundle\RFPBundle\Twig;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve products from a request for quote:
 *   - rfp_products
 */
class RequestProductsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('rfp_products', [$this, 'getRequestProducts'])
        ];
    }

    public function getRequestProducts(Request $request): array
    {
        $result = [];
        foreach ($request->getRequestProducts() as $requestProduct) {
            $product = $requestProduct->getProduct();
            $data['name'] = $this->getProductName($product) ;
            $data['sku'] = $requestProduct->getProductSku();
            $data['comment'] = $requestProduct->getComment();

            $items = [];
            foreach ($requestProduct->getRequestProductItems() as $productItem) {
                $items[] = [
                    'quantity' => $productItem->getQuantity(),
                    'price' => $productItem->getPrice(),
                    'unit' => $productItem->getProductUnitCode()
                ];
            }

            $data['sellerName'] = $requestProduct->getProduct()->getOrganization()->getName();
            $data['items'] = $items;
            $data['kitItemLineItems'] = $this->getKitItemLineItemsData($requestProduct);

            $result[] = $data;
        }

        return $result;
    }

    private function getKitItemLineItemsData(RequestProduct $requestProduct): array
    {
        $kitItemLineItemsData = [];
        foreach ($requestProduct->getKitItemLineItems() as $kitItemLineItem) {
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

    private function getProductName(?Product $product): ?string
    {
        return $this->getEntityNameResolver()->getName(
            $product,
            EntityNameProviderInterface::FULL,
            $this->getLocalizationHelper()->getCurrentLocalization()
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            EntityNameResolver::class,
            LocalizationHelper::class
        ];
    }

    private function getEntityNameResolver(): EntityNameResolver
    {
        return $this->container->get(EntityNameResolver::class);
    }

    private function getLocalizationHelper(): LocalizationHelper
    {
        return $this->container->get(LocalizationHelper::class);
    }
}
