<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddOrderType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Model\QuickAddCopyPaste;

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

        $formData = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();

        if ($formData) {
            $resultForm = $this->createForm(QuickAddOrderType::NAME);
            $resultForm->setData($formData);

            return $this->render(
                'OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig',
                [
                    'result' => $formData,
                    'form' => $resultForm->createView(),
                    'backToUrl' => $request->getUri()
                ]
            );
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/validation/result/", name="orob2b_product_frontend_quick_add_validation_result")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:validationRedirectResult.html.twig")
     *
     * @param Request $request
     * @return Response
     */
    public function validationResultAction(Request $request)
    {
        $result = $this->get('orob2b_product.form_handler.quick_add_import')->process($request);

        /** @var RedirectResponse $response */
        $response = $result['response'];

        if (!$response) {
            $response = new RedirectResponse($this->generateUrl('orob2b_product_frontend_quick_add'));
        }

        return ['targetUrl' => $response->getTargetUrl()];
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
        $copyPasteForm = $this->createForm(
            QuickAddCopyPasteType::NAME,
            new QuickAddCopyPaste()
        );

        $copyPasteForm->handleRequest($request);

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
