<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddOrderType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\QuickAddCopyPaste;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;

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
     * @Route("/import/", name="orob2b_product_frontend_quick_add_import")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:import.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function importAction(Request $request)
    {
        $form = $this->createForm(QuickAddImportFromFileType::NAME)->handleRequest($request);

        /** @var QuickAddCopyPaste $copyPasteFormData */
        $formData = $form->getData();


        if (!empty($formData[QuickAddType::PRODUCTS_FIELD_NAME])) {
            $resultForm = $this->createForm(QuickAddOrderType::NAME);
            $resultForm->setData($formData[QuickAddType::PRODUCTS_FIELD_NAME]);

            return $this->render(
                'OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig',
                [
                    'result' => $formData[QuickAddType::PRODUCTS_FIELD_NAME],
                    'form' => $resultForm->createView()
                ]
            );
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/validation/result/", name="orob2b_product_frontend_quick_add_validation_result")
     * Template("OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function validationResultAction(Request $request)
    {
        $result = $this->get('orob2b_product.form_handler.quick_add_import')->process($request);

        return [

        ];
    }

    /**
     * @Route("/copy-paste/", name="orob2b_product_frontend_quick_add_copy_paste")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function copyPasteAction(Request $request)
    {
        $copyPasteForm = $this->createForm(QuickAddCopyPasteType::NAME, new QuickAddCopyPaste())->handleRequest($request);

        $resultForm = $this->createForm(QuickAddOrderType::NAME);

        /** @var QuickAddCopyPaste $copyPasteFormData */
        $copyPasteFormData = $copyPasteForm->getData();

        if ($copyPasteFormData) {
            $resultForm->setData($copyPasteFormData->getCollection());
        }

        return [
            'result' => $copyPasteFormData->getCollection(),
            'form' => $resultForm->createView()
        ];
    }
}
