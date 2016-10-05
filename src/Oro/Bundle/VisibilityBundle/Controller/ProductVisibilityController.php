<?php

namespace Oro\Bundle\VisibilityBundle\Controller;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\Type\ScopedDataType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AccountBundle\Form\Handler\WebsiteScopedDataHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;

class ProductVisibilityController extends Controller
{
    /**
     * @Route("/edit/{id}", name="oro_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_product_update")
     *
     * @param Request $request
     * @param Product $product
     * @return array
     */
    public function editAction(Request $request, Product $product)
    {
        $form = $this->createScopedDataForm($product, [
            $this->get('oro_scope.scope_manager')->findDefaultScope()
        ]);

        $handler = new WebsiteScopedDataHandler($form, $request, $this->get('event_dispatcher'));

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $product,
            $form,
            function (Product $product) {
                return [
                    'route' => 'oro_product_visibility_edit',
                    'parameters' => ['id' => $product->getId()],
                ];
            },
            function (Product $product) {
                return [
                    'route' => 'oro_product_view',
                    'parameters' => ['id' => $product->getId()],
                ];
            },
            $this->get('translator')->trans('oro.visibility.event.saved.message'),
            $handler
        );
    }

    /**
     * @Route(
     *      "/edit/{productId}/scope/{id}",
     *      name="oro_product_visibility_scoped",
     *      requirements={"productId"="\d+", "id"="\d+"}
     * )
     * @ParamConverter("product", options={"id" = "productId"})
     * @Template("OroVisibilityBundle:ProductVisibility/widget:scope.html.twig")
     * @AclAncestor("oro_product_update")
     *
     * @param Product $product
     * @param Scope $scope
     * @return array
     */
    public function scopeWidgetAction(Product $product, Scope $scope)
    {
        /** @var Form $form */
        $form = $this->createScopedDataForm($product, [$scope]);

        return [
            'form' => $form->createView()[$scope->getId()],
            'entity' => $product,
            'website' => $scope
        ];
    }

    /**
     * @param Product $product
     * @param array $preloadedScopes
     * @return Form
     */
    protected function createScopedDataForm(Product $product, array $preloadedScopes = [])
    {
        return $this->createForm(
            ScopedDataType::NAME,
            $product,
            [
                'ownership_disabled' => true,
                'preloaded_scopes' => $preloadedScopes,
                'type' => EntityVisibilityType::NAME,
                'options' => [
                    'targetEntityField' => 'product',
                    'allClass' => $this
                        ->getParameter('oro_visibility.entity.product_visibility.class'),
                    'accountGroupClass' => $this
                        ->getParameter('oro_visibility.entity.account_group_product_visibility.class'),
                    'accountClass' => $this
                        ->getParameter('oro_visibility.entity.account_product_visibility.class'),
                ]
            ]
        );
    }
}
