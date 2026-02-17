<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for product inline editing.
 */
class InlineEditProductController extends AbstractFOSRestController
{
    /**
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    #[AclAncestor('oro_product_update')]
    public function patchNameAction(Request $request, Product $product)
    {
        $productName = $request->get('productName');
        $createRedirect = $request->get('createRedirect');

        if ($productName === null) {
            return parent::handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $redirectGenerationStrategy = $this->container->get(ConfigManager::class)
            ->get('oro_redirect.redirect_generation_strategy');

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

        $productName = $this->container->get(HtmlTagHelper::class)->stripTags($productName);
        $slug = $this->container->get(SlugGenerator::class)->slugify($productName);

        $product->setDefaultName($productName);
        $product->setDefaultSlugPrototype($slug);
        $product->getSlugPrototypesWithRedirect()->setCreateRedirect($createRedirect);

        $this->container->get(ManagerRegistry::class)->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view(['productName' => $productName], Response::HTTP_OK));
    }

    /**
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    #[AclAncestor('oro_product_update')]
    public function patchInventoryStatusAction(Request $request, Product $product)
    {
        $inventoryStatusId = $request->get('inventory_status');

        if ($inventoryStatusId === null) {
            return parent::handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        /** @var EnumOptionInterface $inventoryStatus */
        $inventoryStatus = $this->container->get(ManagerRegistry::class)
            ->getRepository(EnumOption::class)
            ->find(
                ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    ExtendHelper::getEnumInternalId($inventoryStatusId)
                )
            );

        if (!$inventoryStatus) {
            return parent::handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $product->setInventoryStatus($inventoryStatus);
        $this->container->get(ManagerRegistry::class)->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Response::HTTP_OK));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            HtmlTagHelper::class,
            SlugGenerator::class,
            ConfigManager::class,
            ManagerRegistry::class
        ]);
    }
}
