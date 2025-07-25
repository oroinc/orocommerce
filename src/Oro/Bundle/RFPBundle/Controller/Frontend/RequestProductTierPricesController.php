<?php

namespace Oro\Bundle\RFPBundle\Controller\Frontend;

use Oro\Bundle\RFPBundle\Entity\Request as RequestEntity;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\RequestProductLineItemTierPricesProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for a storefront request for product line items tier prices.
 */
class RequestProductTierPricesController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route(path: '/', name: 'oro_rfp_frontend_request_tier_prices', methods: ['POST'])]
    #[CsrfProtection]
    #[AclAncestor('oro_rfp_frontend_request_create')]
    public function __invoke(Request $request): JsonResponse
    {
        $requestEntity = new RequestEntity();
        $requestForm = $this->createForm(
            RequestType::class,
            null,
            [
                'validation_groups' => 'request_tier_prices',
                // CSRF protection is still enabled - on the method itself via @CsrfProtection() annotation.
                'csrf_protection' => false,
            ]
        );

        $requestForm->handleRequest($request);

        if ($requestForm->isSubmitted()) {
            if ($requestForm->isValid()) {
                $requestProductLineItemTierPricesProvider = $this->container
                    ->get('oro_rfp.provider.request_product_line_item_tier_prices');

                $this->reKeyCollections($requestEntity, $requestForm);

                $tierPrices = $requestProductLineItemTierPricesProvider->getTierPrices($requestEntity);

                return new JsonResponse(
                    [
                        'successful' => true,
                        'tierPrices' => $tierPrices,
                    ],
                    200
                );
            }

            $formErrors = iterator_to_array($requestForm->getErrors(true, true));
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
            'oro_rfp.provider.request_product_line_item_tier_prices' => RequestProductLineItemTierPricesProvider::class,
        ];
    }

    /**
     * Re-keys requestProducts and requestProductItems collections according to their keys in submitted data.
     */
    private function reKeyCollections(RequestEntity $requestEntity, FormInterface $requestForm): void
    {
        $requestProducts = $requestEntity->getRequestProducts();
        /** @var RequestProduct $requestProduct */
        $requestProductsForm = $requestForm->get('requestProducts');
        foreach ($requestProductsForm->getData() as $requestProductKey => $requestProduct) {
            $requestProducts->removeElement($requestProduct);
            $requestProducts->set($requestProductKey, $requestProduct);

            $requestProductItemsForm = $requestProductsForm
                ->get($requestProductKey)
                ->get('requestProductItems');

            $requestProductItems = $requestProduct->getRequestProductItems();
            foreach ($requestProductItemsForm->getData() as $requestProductItemKey => $requestProductItem) {
                $requestProductItems->removeElement($requestProductItem);
                $requestProductItems->set($requestProductItemKey, $requestProductItem);
            }
        }
    }
}
