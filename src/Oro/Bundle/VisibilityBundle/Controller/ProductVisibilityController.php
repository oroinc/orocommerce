<?php

namespace Oro\Bundle\VisibilityBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\Type\ScopedDataType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\Handler\VisibilityFormDataHandler;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityRootScopesProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides edit actions to update visibility for product and scope
 */
class ProductVisibilityController extends AbstractController
{
    /**
     * @Route("/edit/{id}", name="oro_product_visibility_edit", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_product_update")
     */
    public function editAction(Request $request, Product $product): array|RedirectResponse
    {
        $scopes = $this->get(VisibilityRootScopesProvider::class)->getScopes($product);
        if (0 === count($scopes)) {
            $preloadedScopes = [];
        } else {
            $preloadedScopes = [reset($scopes)];
        }

        return $this->get(UpdateHandlerFacade::class)->update(
            $product,
            $this->createScopedDataForm($product, $preloadedScopes),
            $this->get(TranslatorInterface::class)->trans('oro.visibility.event.saved.message'),
            $request,
            new VisibilityFormDataHandler($this->get(EventDispatcherInterface::class))
        );
    }

    /**
     * @Route(
     *      "/edit/{productId}/scope/{id}",
     *      name="oro_product_visibility_scoped",
     *      requirements={"productId"="\d+", "id"="\d+"}
     * )
     * @ParamConverter("product", options={"id" = "productId"})
     * @Template("@OroVisibility/ProductVisibility/widget/scope.html.twig")
     * @AclAncestor("oro_product_update")
     */
    public function scopeWidgetAction(Product $product, Scope $scope): array
    {
        /** @var Form $form */
        $form = $this->createScopedDataForm($product, [$scope]);

        return [
            'form' => $form->createView()[$scope->getId()],
            'entity' => $product,
            'scope' => $scope,
        ];
    }

    protected function createScopedDataForm(Product $product, array $preloadedScopes = []): FormInterface
    {
        return $this->createForm(
            ScopedDataType::class,
            $product,
            [
                'ownership_disabled' => true,
                'dynamic_fields_disabled' => true,
                ScopedDataType::PRELOADED_SCOPES_OPTION => $preloadedScopes,
                ScopedDataType::SCOPES_OPTION => $this->get(VisibilityRootScopesProvider::class)->getScopes($product),
                ScopedDataType::TYPE_OPTION => EntityVisibilityType::class,
                ScopedDataType::OPTIONS_OPTION => [
                    'dynamic_fields_disabled' => true,
                    EntityVisibilityType::ALL_CLASS => ProductVisibility::class,
                    EntityVisibilityType::ACCOUNT_GROUP_CLASS => CustomerGroupProductVisibility::class,
                    EntityVisibilityType::ACCOUNT_CLASS => CustomerProductVisibility::class,
                ]
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                VisibilityRootScopesProvider::class,
                EventDispatcherInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
