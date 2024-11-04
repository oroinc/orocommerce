<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;
use Oro\Bundle\ProductBundle\Form\Handler\ProductCreateStepOneHandler;
use Oro\Bundle\ProductBundle\Form\Handler\ProductUpdateHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Provider\PageTemplateProvider;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\UpsellProductConfigProvider;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for the Product entity.
 */
class ProductController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_product_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_product_view', type: 'entity', class: Product::class, permission: 'VIEW')]
    public function viewAction(Product $product): array
    {
        $pageTemplate = $this->container->get(PageTemplateProvider::class)
            ->getPageTemplate($product, 'oro_product_frontend_product_view');

        return [
            'entity' => $product,
            'imageTypes' => $this->container->get(ImageTypeProvider::class)->getImageTypes(),
            'pageTemplate' => $pageTemplate,
            'upsellProductsEnabled' => $this->container->get(UpsellProductConfigProvider::class)
                ->isEnabled(),
            'relatedProductsEnabled' => $this->container->get(RelatedProductsConfigProvider::class)
                ->isEnabled(),
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_product_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_product_view')]
    public function infoAction(Product $product): array
    {
        return [
            'product' => $product,
            'imageTypes' => $this->container->get(ImageTypeProvider::class)->getImageTypes()
        ];
    }

    #[Route(path: '/info/{id}/kit-items', name: 'oro_product_info_kit_items', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_product_view')]
    public function kitItemsInfoAction(Product $product): array
    {
        return [
            'entity' => $product,
        ];
    }

    #[Route(path: '/', name: 'oro_product_index')]
    #[Template]
    #[AclAncestor('oro_product_view')]
    public function indexAction(): array
    {
        $widgetRouteParameters = [
            'gridName' => 'products-grid',
            'renderParams' => [
                'enableFullScreenLayout' => 1,
                'enableViews' => 0
            ],
            'renderParamsTypes' => [
                'enableFullScreenLayout' => 'int',
                'enableViews' => 'int'
            ]
        ];

        /** @var ProductGridWidgetRenderEvent $event */
        $event = $this->container->get(EventDispatcherInterface::class)->dispatch(
            new ProductGridWidgetRenderEvent($widgetRouteParameters),
            ProductGridWidgetRenderEvent::NAME
        );

        return [
            'entity_class' => Product::class,
            'widgetRouteParameters' => $event->getWidgetRouteParameters()
        ];
    }

    /**
     * Create product form
     */
    #[Route(path: '/create', name: 'oro_product_create')]
    #[Template('@OroProduct/Product/createStepOne.html.twig')]
    #[Acl(id: 'oro_product_create', type: 'entity', class: Product::class, permission: 'CREATE')]
    public function createAction(Request $request): array|Response
    {
        return $this->createStepOne($request);
    }

    /**
     * Create product form step two
     */
    #[Route(path: '/create/step-two', name: 'oro_product_create_step_two')]
    #[Template('@OroProduct/Product/createStepTwo.html.twig')]
    #[AclAncestor('oro_product_create')]
    public function createStepTwoAction(Request $request): array|RedirectResponse
    {
        return $this->createStepTwo($request, new Product());
    }

    /**
     * Edit product form
     */
    #[Route(path: '/update/{id}', name: 'oro_product_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_product_update', type: 'entity', class: Product::class, permission: 'EDIT')]
    public function updateAction(Product $product): array|RedirectResponse
    {
        return $this->update($product);
    }

    /**
     * Quick edit product form
     */
    #[Route(
        path: '/related-items-update/{id}',
        name: 'oro_product_related_items_update',
        requirements: ['id' => '\d+']
    )]
    #[Template]
    #[AclAncestor('oro_product_update')]
    public function updateRelatedItemsAction(Product $product): array|RedirectResponse
    {
        if (!$this->relatedItemsIsGranted()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->container->get(RelatedItemConfigHelper::class)->isAnyEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->update($product);
    }

    private function update(Product $product): array|RedirectResponse
    {
        /** @var ProductUpdateHandler $handler */
        $handler = $this->container->get(ProductUpdateHandler::class);

        return $handler->update(
            $product,
            $this->createForm(ProductType::class, $product),
            $this->container->get(TranslatorInterface::class)->trans('oro.product.controller.product.saved.message')
        );
    }

    private function createStepOne(Request $request): array|Response
    {
        $product = new Product();
        $form = $this->createForm(ProductStepOneType::class, $product);
        $handler = new ProductCreateStepOneHandler($form, $request);
        $queryParams = $request->query->all();

        if ($handler->process()) {
            return $this->forward(
                __CLASS__ . '::createStepTwoAction',
                [],
                $queryParams
            );
        }

        return [
            'form' => $form->createView(),
            'entity' => $product,
            'isWidgetContext' => (bool)$request->get('_wid', false)
        ];
    }

    private function createStepTwo(Request $request, Product $product): array|RedirectResponse
    {
        if ($request->get('input_action') === 'oro_product_create') {
            $form = $this->createForm(ProductStepOneType::class, $product);
            $queryParams = $request->query->all();
            $form->handleRequest($request);
            $formData = $form->all();

            if (!empty($formData)) {
                $form = $this->createForm(ProductType::class, $product);
                foreach ($formData as $key => $item) {
                    $data = $item->getData();
                    $form->get($key)->setData($data);
                }
            }

            return [
                'form' => $form->createView(),
                'entity' => $product,
                'isWidgetContext' => (bool)$request->get('_wid', false),
                'queryParams' => $queryParams
            ];
        }

        $form = $this->createForm(ProductStepOneType::class, $product, ['validation_groups' => false]);
        $form->submit($request->request->all(ProductType::NAME));

        return $this->update($product);
    }

    #[Route(path: '/get-changed-urls/{id}', name: 'oro_product_get_changed_slugs', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_product_update')]
    public function getChangedSlugsAction(Product $product): JsonResponse
    {
        return new JsonResponse($this->container->get(ChangedSlugsHelper::class)
            ->getChangedSlugsData($product, ProductType::class));
    }

    #[Route(
        path: '/get-changed-default-url/{id}',
        name: 'oro_product_get_changed_default_slug',
        requirements: ['id' => '\d+']
    )]
    #[AclAncestor('oro_product_update')]
    public function getChangedDefaultSlugAction(Request $request, Product $product): JsonResponse
    {
        $newName = $request->get('productName');

        $configManager = $this->container->get(ConfigManager::class);
        $showRedirectConfirmation =
            $configManager->get('oro_redirect.redirect_generation_strategy') === Configuration::STRATEGY_ASK;

        $slugsData = [];
        if ($newName !== null) {
            $newName = $this->container->get('oro_ui.html_tag_helper')->stripTags($newName);
            $newSlug = $this->container->get(SlugGenerator::class)->slugify($newName);
            $slugsData = $this->container->get(ChangedSlugsHelper::class)
                ->getChangedDefaultSlugData($product, $newSlug);
        }

        return new JsonResponse([
            'showRedirectConfirmation' => $showRedirectConfirmation && !empty($slugsData),
            'slugsData' => $slugsData,
        ]);
    }

    #[Route(
        path: '/get-possible-products-for-related-products/{id}',
        name: 'oro_product_possible_products_for_related_products',
        requirements: ['id' => '\d+']
    )]
    #[Template('@OroProduct/Product/selectRelatedProducts.html.twig')]
    public function getPossibleProductsForRelatedProductsAction(Product $product): array
    {
        return ['product' => $product];
    }

    #[Route(
        path: '/get-possible-products-for-upsell-products/{id}',
        name: 'oro_product_possible_products_for_upsell_products',
        requirements: ['id' => '\d+']
    )]
    #[Template('@OroProduct/Product/selectUpsellProducts.html.twig')]
    public function getPossibleProductsForUpsellProductsAction(Product $product): array
    {
        return ['product' => $product];
    }

    #[Route(path: '/add-products-widget/{gridName}', name: 'oro_add_products_widget')]
    #[Template]
    #[AclAncestor('oro_product_view')]
    public function addProductsWidgetAction(Request $request, string $gridName): array
    {
        $hiddenProducts = $request->get('hiddenProducts');

        return [
            'parameters' => $hiddenProducts ? ['hiddenProducts' => $hiddenProducts] : [],
            'gridName' => $gridName,
        ];
    }

    /**
     * Checks if at least one "Related Items" functionality is available for the user
     */
    private function relatedItemsIsGranted(): bool
    {
        return $this->isGranted('oro_related_products_edit')
            || $this->isGranted('oro_upsell_products_edit');
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                PageTemplateProvider::class,
                ImageTypeProvider::class,
                UpsellProductConfigProvider::class,
                RelatedProductsConfigProvider::class,
                RelatedItemConfigHelper::class,
                ProductUpdateHandler::class,
                TranslatorInterface::class,
                EventDispatcherInterface::class,
                ChangedSlugsHelper::class,
                ConfigManager::class,
                SlugGenerator::class,
                'oro_ui.html_tag_helper' => HtmlTagHelper::class,
            ]
        );
    }
}
