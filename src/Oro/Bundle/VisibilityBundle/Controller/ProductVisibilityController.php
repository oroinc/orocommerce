<?php

namespace Oro\Bundle\VisibilityBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\Type\ScopedDataType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\Handler\VisibilityFormDataHandler;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides edit actions to update visibility for product and scope
 */
class ProductVisibilityController extends AbstractController
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
        $scopes = $this->get('oro_visibility.root_scopes_provider')->getScopes($product);
        if (0 === count($scopes)) {
            $preloadedScopes = [];
        } else {
            $preloadedScopes = [reset($scopes)];
        }
        $form = $this->createScopedDataForm($product, $preloadedScopes);

        $handler = new VisibilityFormDataHandler($form, $request, $this->get('event_dispatcher'));

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
            'scope' => $scope,
        ];
    }

    /**
     * @param Product $product
     * @param array $preloadedScopes
     * @return FormInterface
     */
    protected function createScopedDataForm(Product $product, array $preloadedScopes = [])
    {
        return $this->createForm(
            ScopedDataType::class,
            $product,
            [
                'ownership_disabled' => true,
                'dynamic_fields_disabled' => true,
                ScopedDataType::PRELOADED_SCOPES_OPTION => $preloadedScopes,
                ScopedDataType::SCOPES_OPTION => $this->get('oro_visibility.root_scopes_provider')->getScopes($product),
                ScopedDataType::TYPE_OPTION => EntityVisibilityType::class,
                ScopedDataType::OPTIONS_OPTION => [
                    EntityVisibilityType::ALL_CLASS => ProductVisibility::class,
                    EntityVisibilityType::ACCOUNT_GROUP_CLASS => CustomerGroupProductVisibility::class,
                    EntityVisibilityType::ACCOUNT_CLASS => CustomerProductVisibility::class,
                ]
            ]
        );
    }
}
