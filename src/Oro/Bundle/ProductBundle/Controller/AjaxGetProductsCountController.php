<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Provider\GridCountProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Implements the following AJAX actions:
 * - get number of rows in submitted grid without filters
 */
class AjaxGetProductsCountController extends AbstractController
{
    /**
     *
     * @param string $gridName
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(
        path: '/get-count/{gridName}',
        name: 'oro_product_datagrid_count_get',
        requirements: ['gridName' => '[\w\:-]+']
    )]
    public function getAction($gridName, Request $request)
    {
        $params = $request->get('params', []);
        $count = $this->container->get(GridCountProvider::class)->getGridCount($gridName, $params);

        return new JsonResponse($count);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                GridCountProvider::class,
            ]
        );
    }
}
