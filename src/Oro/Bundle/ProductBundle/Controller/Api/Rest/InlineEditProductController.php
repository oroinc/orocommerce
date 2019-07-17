<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("product_inline_edit")
 * @NamePrefix("oro_api_")
 */
class InlineEditProductController extends FOSRestController
{
    /**
     * @Patch("inline-edit/product/{id}/name/patch")
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
     * @Patch("inline-edit/product/{id}/inventory-status/patch")
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
