<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for product kit items.
 */
class ProductKitItemController extends AbstractController
{
    /**
     * @Route(
     *     "/info/{id}/{state}",
     *     name="oro_product_kit_item_info",
     *     requirements={"id"="\d+", "state"="expanded|collapsed|both"}
     * )
     * @Template
     */
    public function infoAction(ProductKitItem $kitItem, string $state): array
    {
        $this->denyAccessUnlessGranted('VIEW', $kitItem->getProductKit());

        return [
            'entity' => $kitItem,
            'state' => $state,
        ];
    }
}
