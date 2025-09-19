<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryProductsType;
use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contains actions for the products management of the category products.
 */
class CategoryProductsController extends AbstractController
{
    #[Route(
        path: '/update/{id}',
        name: 'oro_catalog_category_products_update',
        requirements: ['id' => '\d+'],
        methods: ['PUT']
    )]
    #[AclAncestor('oro_catalog_category_update')]
    public function productsUpdateAction(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryProductsType::class);
        $handler = new CategoryHandler(
            $this->container->get(ManagerRegistry::class)->getManagerForClass(Category::class),
            $this->container->get(EventDispatcherInterface::class)
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

    #[Route(
        path: '/manage-sort-order/{id}/widget',
        name: 'oro_catalog_category_products_manage_sort_order_widget',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[Template('@OroCatalog/CategoryProducts/manageSortOrderWidget.html.twig')]
    #[AclAncestor('oro_catalog_category_view')]
    public function manageSortOrderWidgetAction(Category $category, Request $request): array
    {
        return $this->container
            ->get(GridController::class)
            ->widgetAction($request, 'category-products-widget-grid');
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                GridController::class,
                ManagerRegistry::class,
                EventDispatcherInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
