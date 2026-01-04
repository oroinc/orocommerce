<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\BaseCouponType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponGenerationType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Promotion Coupon Controller
 */
class CouponController extends AbstractController
{
    public const COUPONS_GRID = 'promotion-coupons-grid';

    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_promotion_coupon_index')]
    #[Template('@OroPromotion/Coupon/index.html.twig')]
    #[AclAncestor('oro_promotion_coupon_view')]
    public function indexAction()
    {
        return [
            'entity_class' => Coupon::class,
            'gridName' => self::COUPONS_GRID,
        ];
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_promotion_coupon_create')]
    #[Template('@OroPromotion/Coupon/update.html.twig')]
    #[Acl(id: 'oro_promotion_coupon_create', type: 'entity', class: Coupon::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new Coupon(), $request);
    }

    /**
     * @param Coupon $coupon
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_promotion_coupon_update', requirements: ['id' => '\d+'])]
    #[Template('@OroPromotion/Coupon/update.html.twig')]
    #[Acl(id: 'oro_promotion_coupon_update', type: 'entity', class: Coupon::class, permission: 'EDIT')]
    public function updateAction(Coupon $coupon, Request $request)
    {
        return $this->update($coupon, $request);
    }

    /**
     * @param Coupon $coupon
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_promotion_coupon_view', requirements: ['id' => '\d+'])]
    #[Template('@OroPromotion/Coupon/view.html.twig')]
    #[Acl(id: 'oro_promotion_coupon_view', type: 'entity', class: Coupon::class, permission: 'VIEW')]
    public function viewAction(Coupon $coupon)
    {
        return ['entity' => $coupon];
    }

    /**
     * @param Request $request
     * @return array
     */
    #[Route(path: '/coupon-mass-edit-widget', name: 'oro_promotion_coupon_mass_edit_widget')]
    #[Template('@OroPromotion/Coupon/widget/mass_update.html.twig')]
    #[AclAncestor('oro_promotion_coupon_edit')]
    public function massUpdateWidgetAction(Request $request)
    {
        $responseData = [
            'inset' => $request->get('inset', null),
            'values' => $request->get('values', null),
        ];

        $emptyData = new Coupon();
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->container->get(DoctrineHelper::class);
        $gridName = $request->get('gridName');
        $gridParameters = $request->get($gridName);
        if (!empty($gridParameters['promotion_id'])) {
            /** @var Promotion $promotion */
            $promotion = $doctrineHelper->getEntityReference(Promotion::class, $gridParameters['promotion_id']);
            $emptyData->setPromotion($promotion);
        }
        $form = $this->createForm(BaseCouponType::class, $emptyData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $massActionDispatcher = $this->container->get(MassActionDispatcher::class);
            $response = $massActionDispatcher->dispatchByRequest(
                $request->get('gridName'),
                $request->get('actionName'),
                $request
            );
            $responseData['response'] = [
                'successful' => $response->isSuccessful(),
                'message' => $response->getMessage(),
            ];
        }
        $responseData['form'] = $form->createView();

        return $responseData;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/coupon-generation-preview', name: 'oro_promotion_coupon_generation_preview', methods: ['POST'])]
    #[AclAncestor('oro_promotion_coupon_view')]
    #[CsrfProtection()]
    public function couponGenerationPreview(Request $request)
    {
        $options = new CouponGenerationOptions();

        $form = $this->createForm(CouponGenerationType::class, $options, ['csrf_protection' => false]);

        $oroActionOperationData = $request->get('oro_action_operation', []);
        $couponGenerationOptions = $oroActionOperationData['couponGenerationOptions'] ?? [];

        if ($couponGenerationOptions) {
            $form->submit($couponGenerationOptions);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            return new JsonResponse(['error' => (string)$form->getErrors(true, false)]);
        }

        $generator = $this->container->get(CodeGenerator::class);

        return new JsonResponse(['error' => false, 'code' => $generator->generateOne($options)]);
    }

    /**
     * @param Coupon $coupon
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Coupon $coupon, Request $request)
    {
        $handler = $this->container->get(UpdateHandlerFacade::class);

        return $handler->update(
            $coupon,
            CouponType::class,
            $this->container->get(TranslatorInterface::class)->trans('oro.promotion.coupon.form.message.saved'),
            $request
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class,
                DoctrineHelper::class,
                MassActionDispatcher::class,
                CodeGenerator::class,
            ]
        );
    }
}
