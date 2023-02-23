<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryProductsType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains actions for the products management of the category products.
 */
class CategoryProductsController extends AbstractController
{
    /**
     * @Route(
     *     "/update/{id}",
     *     name="oro_catalog_category_products_update",
     *     requirements={"id"="\d+"},
     *     methods={"PUT"}
     * )
     * @AclAncestor("oro_catalog_category_update")
     */
    public function productsUpdateAction(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryProductsType::class);
        $handler = new CategoryHandler(
            $this->get(ManagerRegistry::class)->getManagerForClass(Category::class),
            $this->get(EventDispatcherInterface::class)
        );

        if ($handler->process($category, $form, $request)) {
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
     *     "/manage-sort-order/widget",
     *     name="oro_catalog_category_products_manage_sort_order_widget",
     *     methods={"GET"}
     * )
     * @AclAncestor("oro_catalog_category_update")
     * @Template
     */
    public function manageSortOrderWidgetAction(Request $request): array
    {
        return [
            'params' => $request->get('params', []),
            'renderParams' => $this->getRenderParams($request),
            'multiselect' => (bool)$request->get('multiselect', false),
        ];
    }

    private function getRenderParams(Request $request): array
    {
        $renderParams = $request->get('renderParams', []);
        $renderParamsTypes = $request->get('renderParamsTypes', []);

        foreach ($renderParamsTypes as $param => $type) {
            if (array_key_exists($param, $renderParams)) {
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $renderParams[$param] = (bool)$renderParams[$param];
                        break;
                    case 'int':
                    case 'integer':
                        $renderParams[$param] = (int)$renderParams[$param];
                        break;
                }
            }
        }

        return $renderParams;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ManagerRegistry::class,
                EventDispatcherInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
