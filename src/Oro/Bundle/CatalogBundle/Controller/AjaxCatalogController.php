<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ajax Catalog Controller
 */
class AjaxCatalogController extends AbstractController
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/category-move', name: 'oro_catalog_category_move', methods: ['PUT'])]
    #[AclAncestor('oro_catalog_category_update')]
    #[CsrfProtection()]
    public function categoryMoveAction(Request $request)
    {
        $nodeId = (int)$request->get('id');
        $parentId = (int)$request->get('parent');
        $position = (int)$request->get('position');

        return new JsonResponse(
            $this->container->get(CategoryTreeHandler::class)->moveNode($nodeId, $parentId, $position)
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CategoryTreeHandler::class,
        ]);
    }
}
