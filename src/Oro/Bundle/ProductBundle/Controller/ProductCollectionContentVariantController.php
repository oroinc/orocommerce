<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Form\Handler\ProductCollectionSegmentProductsFormHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentProductsType;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains actions for the products management of the product collection content variant.
 */
class ProductCollectionContentVariantController extends AbstractController
{
    /**
     * @Route(
     *     "/update/{id}/products",
     *     name="oro_product_collection_content_variant_products_update",
     *     requirements={"id"="\d+"},
     *     methods={"PUT"}
     * )
     * @CsrfProtection()
     */
    public function productsUpdateAction(ContentVariant $contentVariant, Request $request): Response
    {
        $contentNode = $contentVariant->getNode();
        if (!$this->isGranted('oro_web_catalog_update', $contentNode->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        if ($contentVariant->getType() !== ProductCollectionContentVariantType::TYPE) {
            $translator = $this->container->get(TranslatorInterface::class);
            return new JsonResponse(
                [
                    'success' => false,
                    'messages' => [
                        'error' => [$translator->trans(
                            'oro.product.product_collection.invalid_content_variant_type',
                            [],
                            'validators'
                        )]
                    ]
                ],
                400
            );
        }

        /** @var Segment|null $segment */
        $segment = $contentVariant->getProductCollectionSegment();
        if ($segment === null) {
            throw $this->createNotFoundException('Cannot find the product collection segment');
        }

        $formHandler = $this->container->get(ProductCollectionSegmentProductsFormHandler::class);
        $form = $this->createForm(ProductCollectionSegmentProductsType::class, null, ['segment' => $segment]);
        if ($formHandler->process($segment, $form, $request)) {
            $statusCode = 200;
            $responseData['success'] = true;
        } else {
            $statusCode = 400;
            $responseData['success'] = false;
            $formErrorIterator = $form->getErrors(true);
            if ($formErrorIterator) {
                foreach ($formErrorIterator as $formError) {
                    $responseData['messages']['error'][] = $formError->getMessage();
                }
            }
        }

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * @Route(
     *     "/manage-sort-order/{id}/widget/{gridName}",
     *     name="oro_product_collection_content_variant_manage_sort_order_widget",
     *     requirements={"id"="\d+"},
     *     methods={"GET"}
     * )
     * @Template
     */
    public function manageSortOrderWidgetAction(
        ContentVariant $contentVariant,
        string $gridName,
        Request $request
    ): array {
        $contentNode = $contentVariant->getNode();
        $this->denyAccessUnlessGranted('VIEW', $contentNode->getWebCatalog());
        return $this->container
            ->get(GridController::class)
            ->widgetAction($request, $gridName);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                GridController::class,
                TranslatorInterface::class,
                ProductCollectionSegmentProductsFormHandler::class
            ]
        );
    }
}
