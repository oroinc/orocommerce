<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Box\Spout\Common\Exception\UnsupportedTypeException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;

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

        if ($form->isValid()) {
            $file = $form->getData()[QuickAddImportFromFileType::FILE_FIELD_NAME];
            try {
                $collection = $this->getQuickAddRowCollectionBuilder()->buildFromFile($file);
            } catch (UnsupportedTypeException $e) {
                $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->addError(new FormError(
                    $this->get('translator')->trans(
                        'orob2b.product.frontend.quick_add.invalid_file_type',
                        [],
                        'validators'
                    )
                ));

                return [
                    'form' => $form->createView()
                ];
            }

            $resultForm = $this->createForm(QuickAddType::NAME, $collection->getFormData());

            return $this->render(
                'OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig',
                [
                    'result' => $collection,
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
     * @return array
     */
    public function validationResultAction(Request $request)
    {
        $response = $this->get('orob2b_product.form_handler.quick_add_import')->process($request);

        return ['targetUrl' => $response->getTargetUrl()];
    }

    /**
     * @Route("/copy-paste/", name="orob2b_product_frontend_quick_add_copy_paste")
     * @Template("OroB2BProductBundle:QuickAdd\Frontend:validationResult.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function copyPasteAction(Request $request)
    {
        $copyPasteForm = $this->createForm(QuickAddCopyPasteType::NAME)->handleRequest($request);
        $copyPasteText = $copyPasteForm->getData()[QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME];

        $collection = $this->getQuickAddRowCollectionBuilder()->buildFromCopyPasteText($copyPasteText);

        $resultForm = $this->createForm(QuickAddType::NAME, $collection->getFormData());

        return [
            'result' => $collection,
            'form' => $resultForm->createView()
        ];
    }

    /**
     * @return QuickAddRowCollectionBuilder
     */
    private function getQuickAddRowCollectionBuilder()
    {
        return $this->get('orob2b_product.model.builder.quick_add_row_collection');
    }
}
