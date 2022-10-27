<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for product inline editing.
 */
class InlineEditProductController extends AbstractFOSRestController
{
    /**
     * @AclAncestor("oro_product_update")
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function patchNameAction(Request $request, Product $product)
    {
        $productName = $request->get('productName');
        $createRedirect = $request->get('createRedirect');

        if ($productName === null) {
            return parent::handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $redirectGenerationStrategy =
            $this->get('oro_config.manager')->get('oro_redirect.redirect_generation_strategy');

        switch ($redirectGenerationStrategy) {
            case Configuration::STRATEGY_ASK:
                $createRedirect = ($createRedirect === null) ? true : (bool) $createRedirect;
                break;
            case Configuration::STRATEGY_ALWAYS:
                $createRedirect = true;
                break;
            case Configuration::STRATEGY_NEVER:
                $createRedirect = false;
                break;
        }

        $slug = $this->get('oro_entity_config.slug.generator')->slugify($productName);

        $product->setDefaultName($productName);
        $product->setDefaultSlugPrototype($slug);
        $product->getSlugPrototypesWithRedirect()->setCreateRedirect($createRedirect);

        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Response::HTTP_OK));
    }

    /**
     * @AclAncestor("oro_product_update")
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function patchInventoryStatusAction(Request $request, Product $product)
    {
        $inventoryStatusId = $request->get('inventoryStatusId');

        if ($inventoryStatusId === null) {
            return parent::handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getDoctrine()
            ->getRepository(ExtendHelper::buildEnumValueClassName('prod_inventory_status'))
            ->find($inventoryStatusId);

        if (!$inventoryStatus) {
            return parent::handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $product->setInventoryStatus($inventoryStatus);
        $this->getDoctrine()->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Response::HTTP_OK));
    }
}
