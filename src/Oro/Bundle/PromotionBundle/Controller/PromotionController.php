<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Promotions.
 */
class PromotionController extends AbstractController
{
    /**
     * @param Promotion $promotion
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_promotion_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_promotion_view', type: 'entity', class: Promotion::class, permission: 'VIEW')]
    public function viewAction(Promotion $promotion)
    {
        $definitionParts = $this->container->get(ProductCollectionDefinitionConverter::class)
            ->getDefinitionParts($promotion->getProductsSegment()->getDefinition());

        return [
            'entity' => $promotion,
            'scopeEntities' => $this->container->get(ScopeManager::class)->getScopeEntities('promotion'),
            'segmentId' => $promotion->getProductsSegment()->getId(),
            'segmentDefinition' => $definitionParts[ProductCollectionDefinitionConverter::DEFINITION_KEY],
            'includedProducts' => $definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY],
            'excludedProducts' => $definitionParts[ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY]
        ];
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_promotion_index')]
    #[Template]
    #[AclAncestor('oro_promotion_view')]
    public function indexAction()
    {
        return [
            'entity_class' => Promotion::class,
            'gridName' => 'promotion-grid'
        ];
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_promotion_create')]
    #[Template('@OroPromotion/Promotion/update.html.twig')]
    #[Acl(id: 'oro_promotion_create', type: 'entity', class: Promotion::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        $promotion = new Promotion();

        return $this->update($promotion, $request);
    }

    /**
     *
     * @param Promotion $promotion
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_promotion_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_promotion_update', type: 'entity', class: Promotion::class, permission: 'EDIT')]
    public function updateAction(Promotion $promotion, Request $request)
    {
        return $this->update($promotion, $request);
    }

    /**
     * @param Promotion $promotion
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Promotion $promotion, Request $request)
    {
        $form = $this->createForm(PromotionType::class, $promotion);

        $result = $this->container->get(UpdateHandlerFacade::class)->update(
            $promotion,
            $form,
            $this->container->get(TranslatorInterface::class)->trans('oro.promotion.controller.saved.message'),
            $request
        );

        return $result;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class,
                ProductCollectionDefinitionConverter::class,
                ScopeManager::class,
            ]
        );
    }
}
