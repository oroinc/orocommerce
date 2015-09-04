<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;

class QuickAddController extends Controller
{
    /**
     * Process quick add
     *
     * @Route("/", name="orob2b_product_frontend_quick_add")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:add.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function addAction(Request $request)
    {
        $form = $this->createForm(QuickAddType::NAME);

        $response = null;
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);

            // TODO: Add handler call here BB-1058
        }

        if ($response) {
            return $response;
        } else {
            return [
                'form' => $form->createView()
            ];
        }
    }
}
