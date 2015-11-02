<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuickAddController extends Controller
{
    /**
     * @Route("/", name="orob2b_product_frontend_quick_add")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:add.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function addAction(Request $request)
    {
        $result = $this->get('orob2b_product.form_handler.quick_add')->process($request);

        /** @var FormInterface $form */
        $form = $result['form'];
        /** @var Response|null $response */
        $response = $result['response'];

        $copyPasteForm = $this->createForm(QuickAddCopyPasteType::NAME);

        return $response ?: ['form' => $form->createView(), 'copyPasteForm' => $copyPasteForm->createView()];
    }

    /**
     * @Route("/import/", name="orob2b_product_frontend_import")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:import.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function importAction(Request $request)
    {
        $result = $this->get('orob2b_product.form_handler.quick_add_import_from_file')->process($request);

        /** @var FormInterface $form */
        $form = $result['form'];

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/import/summary/", name="orob2b_product_frontend_import_summary")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:importShow.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function importShowAction(Request $request)
    {
        return [
            'isWidgetContext' => false
        ];
    }
}
