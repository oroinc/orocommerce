<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Frontend product brand controller.
 */
class BrandController extends AbstractController
{
    const GRID_NAME = 'frontend-brand-search-grid';

    /**
     * View list of brands
     *
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_product_frontend_brand_index')]
    public function indexAction()
    {
        throw new NotFoundHttpException();
    }

    /**
     * View list of products for brand
     *
     *
     * @param Request $request
     * @param Brand $brand
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_product_frontend_brand_view', requirements: ['id' => '\d+'])]
    public function viewAction(Request $request, Brand $brand)
    {
        throw new NotFoundHttpException();
    }
}
