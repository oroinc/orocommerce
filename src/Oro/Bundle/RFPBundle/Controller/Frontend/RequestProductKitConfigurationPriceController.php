<?php

namespace Oro\Bundle\RFPBundle\Controller\Frontend;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitConfigurationType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for a storefront request for product kit configuration price.
 */
class RequestProductKitConfigurationPriceController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route(path: '/', name: 'oro_rfp_frontend_request_product_kit_configuration_price', methods: ['POST'])]
    #[CsrfProtection]
    #[AclAncestor('oro_rfp_frontend_request_create')]
    public function __invoke(Request $request): JsonResponse
    {
        $requestProduct = new RequestProduct();
        $requestProductKitConfigurationForm = $this->createForm(
            RequestProductKitConfigurationType::class,
            $requestProduct,
            [
                'validation_groups' => 'request_product_kit_configuration_price',
            ]
        );

        $requestProductKitConfigurationForm->handleRequest($request);

        if ($requestProductKitConfigurationForm->isSubmitted()) {
            if ($requestProductKitConfigurationForm->isValid()) {
                $quantity = (float)$requestProductKitConfigurationForm->get('quantity')->getData() ?: 1.0;
                $productUnit = $requestProductKitConfigurationForm->get('productUnit')->getData();
                $requestProductItem = (new RequestProductItem())
                    ->setRequestProduct($requestProduct)
                    ->setQuantity($quantity)
                    ->setProductUnit($productUnit);

                $lineItemPriceProvider = $this->container->get('oro_pricing.provider.product_line_item_price');
                $productLineItemPrices = $lineItemPriceProvider->getProductLineItemsPrices([$requestProductItem]);
                /** @var ProductLineItemPrice $productLineItemPrice */
                $productLineItemPrice = reset($productLineItemPrices) ?: null;

                return new JsonResponse([
                    'successful' => true,
                    'price' => $productLineItemPrice?->getPrice()->jsonSerialize(),
                ]);
            }

            $formErrors = iterator_to_array($requestProductKitConfigurationForm->getErrors(true, true));
            $data = [
                'successful' => false,
                'messages' => [
                    'error' => array_map(static fn (FormError $formError) => $formError->getMessage(), $formErrors),
                ],
            ];

            return new JsonResponse($data, 422);
        }

        return new JsonResponse(['successful' => false], 400);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            'oro_pricing.provider.product_line_item_price' => ProductLineItemPriceProviderInterface::class,
        ];
    }
}
