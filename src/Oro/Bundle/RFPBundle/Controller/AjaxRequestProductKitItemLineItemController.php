<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request as RFQ;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * The controller that implement AJAX entry point for {@see RequestProductKitItemLineItem}.
 */
class AjaxRequestProductKitItemLineItemController extends AbstractController
{
    /**
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    #[Route(path: '/entry-point/{id}', name: 'oro_rfp_request_product_kit_item_line_item_entry_point')]
    #[AclAncestor('oro_rfp_request_update')]
    public function entryPointAction(Request $request, Product $product)
    {
        $kitItemLineItems = '';
        if ($product->isKit() === true) {
            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);

            $form = $this->createForm(
                RequestType::class,
                new RFQ(),
                [
                    'validation_groups' => $this->getValidationGroups($requestProduct),
                ]
            );
            $submittedData = $request->get($form->getName());
            $form->submit($submittedData);

            $requestProductKey = array_key_first($submittedData['requestProducts'] ?? []);
            if ($requestProductKey !== null
                && $form->get('requestProducts')->has($requestProductKey)
            ) {
                $requestProductForm = $form->get('requestProducts')->get($requestProductKey);
                $requestProductFormView = $requestProductForm->createView();

                $kitItemLineItems = $this->renderView(
                    '@OroRFP/Form/kitItemLineItems.html.twig',
                    ['form' => $requestProductFormView['kitItemLineItems']]
                );
            }
        }

        return new JsonResponse($kitItemLineItems);
    }

    protected function getValidationGroups(RequestProduct $requestProduct): GroupSequence|array|string
    {
        return new GroupSequence([Constraint::DEFAULT_GROUP, 'request_product_kit_configuration']);
    }
}
