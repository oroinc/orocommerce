<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Form\Type\BaseCouponType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponCodePreviewType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class CouponController extends Controller
{
    const COUPONS_GRID = 'promotion-coupons-grid';

    /**
     * @Route("/", name="oro_promotion_coupon_index")
     * @Template
     * @AclAncestor("oro_promotion_coupon_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Coupon::class,
            'gridName' => self::COUPONS_GRID
        ];
    }

    /**
     * @Route("/create", name="oro_promotion_coupon_create")
     * @Template("OroPromotionBundle:Coupon:update.html.twig")
     * @Acl(
     *      id="oro_promotion_coupon_create",
     *      type="entity",
     *      class="OroPromotionBundle:Coupon",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new Coupon(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_promotion_coupon_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_promotion_coupon_update",
     *      type="entity",
     *      class="OroPromotionBundle:Coupon",
     *      permission="EDIT"
     * )
     * @param \Oro\Bundle\PromotionBundle\Entity\Coupon $coupon
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Coupon $coupon, Request $request)
    {
        return $this->update($coupon, $request);
    }

    /**
     * @Route("/view/{id}", name="oro_promotion_coupon_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_promotion_coupon_view",
     *      type="entity",
     *      class="OroPromotionBundle:Coupon",
     *      permission="VIEW"
     * )
     *
     * @param Coupon $coupon
     * @return array
     */
    public function viewAction(Coupon $coupon)
    {
        return ['entity' => $coupon];
    }

    /**
     * @Route("/coupon-mass-edit-widget", name="oro_promotion_coupon_mass_edit_widget")
     * @AclAncestor("oro_promotion_coupon_edit")
     * @Template("OroPromotionBundle:Coupon/widget:mass_update.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function massUpdateWidgetAction(Request $request)
    {
        $responseData = [
            'inset' => $request->get('inset', null),
            'values' => $request->get('values', null),
        ];

        $form = $this->createForm(BaseCouponType::class, new Coupon());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MassActionDispatcher $massActionDispatcher */
            $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');
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
     * @Route("/coupon-generation-preview", name="oro_promotion_coupon_generation_preview")
     * @AclAncestor("oro_promotion_coupon_view")
     * @param Request $request
     * @return JsonResponse
     */
    public function couponGenerationPreview(Request $request)
    {
        $responseData = [];
        $formData = $request->get('couponGenerationData');
        if ($formData) {
            $form = $this->createForm(CouponCodePreviewType::class, new CouponGenerationOptions());
            $form->submit($formData);
            if ($form->isValid()) {
                $couponGenerationOptions = $form->getData();
                $couponGenerationOptions->setCouponQuantity(1);
                $couponCodeGenerator = $this->get('oro_promotion.coupon_generation.generator');
            }
        }

        return new JsonResponse($responseData);
    }

    /**
     * @param Coupon $coupon
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Coupon $coupon, Request $request)
    {
        $handler = $this->get('oro_form.update_handler');
        return $handler->update(
            $coupon,
            CouponType::class,
            $this->get('translator')->trans('oro.promotion.coupon.form.message.saved'),
            $request
        );
    }
}
