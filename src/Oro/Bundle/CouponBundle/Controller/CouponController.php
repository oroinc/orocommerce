<?php

namespace Oro\Bundle\CouponBundle\Controller;

use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Form\Type\BaseCouponType;
use Oro\Bundle\CouponBundle\Form\Type\CouponType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CouponController extends Controller
{
    const COUPONS_GRID = 'coupons-grid';

    /**
     * @Route("/", name="oro_coupon_index")
     * @Template
     * @AclAncestor("oro_coupon_view")
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
     * @Route("/create", name="oro_coupon_create")
     * @Template("OroCouponBundle:Coupon:update.html.twig")
     * @Acl(
     *      id="oro_coupon_create",
     *      type="entity",
     *      class="OroCouponBundle:Coupon",
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
     * @Route("/update/{id}", name="oro_coupon_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_coupon_update",
     *      type="entity",
     *      class="OroCouponBundle:Coupon",
     *      permission="EDIT"
     * )
     * @param Coupon $coupon
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Coupon $coupon, Request $request)
    {
        return $this->update($coupon, $request);
    }

    /**
     * @Route("/view/{id}", name="oro_coupon_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_coupon_view",
     *      type="entity",
     *      class="OroCouponBundle:Coupon",
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
     * @Route("/coupon-mass-edit-widget", name="oro_coupon_mass_edit_widget")
     * @AclAncestor("oro_coupon_edit")
     * @Template("OroCouponBundle:Coupon/widget:mass_update.html.twig")
     *
     * @return array
     */
    public function massUpdateWidgetAction(Request $request)
    {
        $responseData = [
            'form' => $this->createForm(BaseCouponType::class, new Coupon())->createView(),
            'inset' => $request->get('inset', null),
            'values' => $request->get('values', null),
        ];

        if ($request->isMethod('POST')) {
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
        return $responseData;
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
            $this->get('translator')->trans('oro.coupon.form.message.saved'),
            $request
        );
    }
}
