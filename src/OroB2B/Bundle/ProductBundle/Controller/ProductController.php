<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use OroB2B\Bundle\ProductBundle\Form\Handler\ProductCreateStepOneHandler;

class ProductController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_product_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_view",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="VIEW"
     * )
     *
     * @param Product $product
     * @return array
     */
    public function viewAction(Product $product)
    {
        return [
            'entity' => $product
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_product_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_product_view")
     *
     * @param Product $product
     * @return array
     */
    public function infoAction(Product $product)
    {
        return [
            'product' => $product,
            'imageTypes' => $this->get('oro_layout.provider.image_type')->getImageTypes()
        ];
    }

    /**
     * @Route("/", name="orob2b_product_index")
     * @Template
     * @AclAncestor("orob2b_product_view")
     *
     * @return array
     */
    public function indexAction()
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
        $event = $this->get('event_dispatcher')->dispatch(
            ProductGridWidgetRenderEvent::NAME,
            new ProductGridWidgetRenderEvent($widgetRouteParameters)
        );

        return [
            'entity_class' => $this->container->getParameter('orob2b_product.entity.product.class'),
            'widgetRouteParameters' => $event->getWidgetRouteParameters()
        ];
    }

    /**
     * Create product form
     *
     * @Route("/create", name="orob2b_product_create")
     * @Template("OroB2BProductBundle:Product:createStepOne.html.twig")
     * @Acl(
     *      id="orob2b_product_create",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->createStepOne($request);
    }

    /**
     * Create product form step two
     *
     * @Route("/create/step-two", name="orob2b_product_create_step_two")
     * @Template("OroB2BProductBundle:Product:createStepTwo.html.twig")
     *
     * @AclAncestor("orob2b_product_create")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createStepTwoAction(Request $request)
    {
        return $this->createStepTwo($request, new Product());
    }

    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_product_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_update",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="EDIT"
     * )
     * @param Product $product
     * @return array|RedirectResponse
     */
    public function updateAction(Product $product)
    {
        return $this->update($product);
    }

    /**
     * @param Product $product
     * @return array|RedirectResponse
     */
    protected function update(Product $product)
    {
        return $this->get('orob2b_product.service.product_update_handler')->handleUpdate(
            $product,
            $this->createForm(ProductType::NAME, $product),
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_update',
                    'parameters' => ['id' => $product->getId()]
                ];
            },
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_view',
                    'parameters' => ['id' => $product->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.product.controller.product.saved.message')
        );
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function createStepOne(Request $request)
    {
        $form = $this->createForm(ProductStepOneType::NAME);
        $handler = new ProductCreateStepOneHandler($form, $request);

        if ($handler->process()) {
            return $this->forward('OroB2BProductBundle:Product:createStepTwo');
        }

        return ['form' => $form->createView()];
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return array|RedirectResponse
     */
    protected function createStepTwo($request, Product $product)
    {
        if ($request->get('input_action') == 'orob2b_product_create') {
            $form = $this->createForm(ProductStepOneType::NAME);
            $form->handleRequest($request);
            $formData = $form->all();

            if (!empty($formData)) {

                if ($formData['category']->getData()) {
                    $provider = $this->get('orob2b_product.provider.default_product_unit_provider');
                    $provider->setCategoryId($formData['category']->getData()->getId());
                }

                $form = $this->createForm(ProductType::NAME, $product);

                foreach ($formData as $key => $item) {
                    $data = $item->getData();
                    $form->get($key)->setData($data);
                }
            }

            return [
                'form' => $form->createView(),
                'entity' => $product
            ];
        }

        return $this->get('orob2b_product.service.product_update_handler')->handleUpdate(
            $product,
            $this->createForm(ProductType::NAME, $product),
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_update',
                    'parameters' => ['id' => $product->getId()]
                ];
            },
            function (Product $product) {
                return [
                    'route' => 'orob2b_product_view',
                    'parameters' => ['id' => $product->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.product.controller.product.saved.message')
        );
    }
}
