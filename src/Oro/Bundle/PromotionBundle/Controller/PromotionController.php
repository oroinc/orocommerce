<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PromotionController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_promotion_view", requirements={"id"="\d+"})
     * @Template()
     * @Acl(
     *      id="oro_promotion_view",
     *      type="entity",
     *      class="OroPromotionBundle:Promotion",
     *      permission="VIEW"
     * )
     */
    public function viewAction(Promotion $promotion)
    {
        // View action
    }

    /**
     * @Route("/", name="oro_promotion_index")
     * @Template()
     * @AclAncestor("oro_promotion_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Promotion::class,
            'gridName' => 'promotion-grid'
        ];
    }

    /**
     * @Route("/create", name="oro_promotion_create")
     * @Acl(
     *      id="oro_promotion_create",
     *      type="entity",
     *      class="OroPromotionBundle:Promotion",
     *      permission="CREATE"
     * )
     */
    public function createAction(Request $request)
    {
        // Create action
    }

    /**
     * @Route("/update/{id}", name="oro_promotion_update", requirements={"id"="\d+"})
     */
    public function updateAction(Promotion $promotion)
    {
        // Update action
    }
}
