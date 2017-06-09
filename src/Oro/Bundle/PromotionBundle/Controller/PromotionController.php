<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PromotionController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_promotion_view", requirements={"id"="\d+"})
     */
    public function viewAction(Promotion $promotion)
    {
        // View action
    }

    /**
     * @Route("/", name="oro_promotion_index")
     */
    public function indexAction()
    {
        // Index action
    }

    /**
     * @Route("/create", name="oro_promotion_create")
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
