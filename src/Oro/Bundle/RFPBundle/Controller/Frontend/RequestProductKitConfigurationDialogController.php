<?php

namespace Oro\Bundle\RFPBundle\Controller\Frontend;

use Oro\Bundle\FormBundle\Utils\CsrfTokenUtils;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\RFPBundle\Entity\Request as RequestEntity;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitConfigurationType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for a storefront request product kit configuration dialog.
 */
class RequestProductKitConfigurationDialogController extends AbstractController
{
    #[Route(path: '/', name: 'oro_rfp_frontend_request_product_kit_configuration', methods: ['POST'])]
    #[CsrfProtection]
    #[Layout]
    #[AclAncestor('oro_rfp_frontend_request_create')]
    public function __invoke(Request $request): Response|array
    {
        $isWidgetInit = $request->get('_widgetInit') === '1';
        $requestProduct = new RequestProduct();
        $requestProductKitConfigurationForm = $this->createForm(
            RequestProductKitConfigurationType::class,
            $requestProduct,
            [
                'validation_groups' => ['frontend_request_product_kit_configuration'],
            ]
        );

        $widgetContainer = 'dialog';
        $statusCode = 200;

        if ($isWidgetInit) {
            $this->handleWidgetInitRequest($requestProductKitConfigurationForm, $request);

            $formView = $requestProductKitConfigurationForm->createView();
        } else {
            $requestProductKitConfigurationForm->handleRequest($request);

            if ($requestProductKitConfigurationForm->isSubmitted() && $requestProductKitConfigurationForm->isValid()) {
                $requestProductIndex = (string)$requestProductKitConfigurationForm->get('index')->getData();
                $requestEntity = new RequestEntity();
                $requestEntity
                    ->getRequestProducts()
                    ->set($requestProductIndex, $requestProduct);

                $formView = $this
                    ->createForm(RequestType::class, $requestEntity)
                    ->get('requestProducts')
                    ?->get($requestProductIndex)
                    ?->get('kitItemLineItems')
                    ?->createView();

                $widgetContainer = 'result';
            } else {
                $formView = $requestProductKitConfigurationForm->createView();
                $statusCode = 422;
            }
        }

        return [
            'response_status_code' => $statusCode,
            'widget_container' => $widgetContainer,
            'data' => [
                'form' => $formView,
            ],
        ];
    }

    private function handleWidgetInitRequest(
        FormInterface $requestProductKitConfigurationForm,
        Request $request
    ): void {
        $requestFormData = $request->request->all()['oro_rfp_frontend_request'] ?? [];
        $requestProductsData = (array)($requestFormData['requestProducts'] ?? []);
        $requestProductData = reset($requestProductsData) ?: [];
        $requestProductIndex = (int)key($requestProductsData);

        $data = [
            'index' => $requestProductIndex,
            'product' => $requestProductData['product'] ?? '',
            'kitItemLineItems' => $requestProductData['kitItemLineItems'] ?? [],
        ];

        if (CsrfTokenUtils::isCsrfProtectionEnabled($requestProductKitConfigurationForm)) {
            $csrfFieldName = CsrfTokenUtils::getCsrfFieldName($requestProductKitConfigurationForm);
            $csrfToken = CsrfTokenUtils::getCsrfToken($requestProductKitConfigurationForm);
            $data[$csrfFieldName] = (string)$csrfToken;
        }

        $requestProductKitConfigurationForm->submit($data, false);
    }
}
