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
        return [new TwigFunction('rfp_products', [$this, 'getRequestProducts'])];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getRequestProducts(Request $request)
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
                    'unit' => $productItem->getProductUnitCode(),
                ];
            }

            $data['items'] = $items;
            $data['kitItemLineItems'] = $this->getKitItemLineItemsData($requestProduct);

            $result[] = $data;
        }

        return $result;
    }

    protected function getKitItemLineItemsData(RequestProduct $requestProduct): array
    {
        $kitItemLineItemsData = [];
        foreach ($requestProduct->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemData['kitItemLabel'] = $this->getLocalizationHelper()->getLocalizedValue(
                $kitItemLineItem->getKitItem()->getLabels()
            );
            $kitItemLineItemData['unit'] = $kitItemLineItem->getProductUnit();
            $kitItemLineItemData['quantity'] = $kitItemLineItem->getQuantity();
            $kitItemLineItemData['productName'] = $this->getProductName($kitItemLineItem->getProduct());

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
