<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for product kit items.
 */
class ProductKitItemController extends AbstractController
{
    #[Route(
        path: '/info/{id}/{state}',
        name: 'oro_product_kit_item_info',
        requirements: ['id' => '\d+', 'state' => 'expanded|collapsed|both']
    )]
    #[Template('@OroProduct/ProductKitItem/info.html.twig')]
    public function infoAction(ProductKitItem $kitItem, string $state): array
    {
        $this->denyAccessUnlessGranted('VIEW', $kitItem->getProductKit());

        return [
            'entity' => $kitItem,
            'state' => $state,
        ];
    }
}
