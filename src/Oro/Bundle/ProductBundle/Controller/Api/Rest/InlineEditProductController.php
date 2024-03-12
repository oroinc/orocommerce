<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
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

        $redirectGenerationStrategy =
            $this->container->get('oro_config.manager')->get('oro_redirect.redirect_generation_strategy');

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

        $productName = $this->container->get('oro_ui.html_tag_helper')->stripTags($productName);
        $slug = $this->container->get('oro_entity_config.slug.generator')->slugify($productName);

        $product->setDefaultName($productName);
        $product->setDefaultSlugPrototype($slug);
        $product->getSlugPrototypesWithRedirect()->setCreateRedirect($createRedirect);

        $this->container->get('doctrine')->getManagerForClass(Product::class)->flush();

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
        $inventoryStatusId = $request->get('inventoryStatusId');

        if ($inventoryStatusId === null) {
            return parent::handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->container->get('doctrine')
            ->getRepository(ExtendHelper::buildEnumValueClassName('prod_inventory_status'))
            ->find($inventoryStatusId);

        if (!$inventoryStatus) {
            return parent::handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $product->setInventoryStatus($inventoryStatus);
        $this->container->get('doctrine')->getManagerForClass(Product::class)->flush();

        return parent::handleView($this->view([], Response::HTTP_OK));
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_ui.html_tag_helper' => HtmlTagHelper::class,
                'oro_entity_config.slug.generator' => SlugGenerator::class,
                'oro_config.manager' => ConfigManager::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
