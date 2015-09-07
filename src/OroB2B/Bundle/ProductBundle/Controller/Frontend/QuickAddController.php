<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;

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
        $form = null;
        $response = null;

        $formData = $request->get(QuickAddType::NAME);

        $processor = $this->getProcessor(isset($formData['component']) ? $formData['component'] : null);
        if (!$processor) {
            $form = $this->createForm(
                QuickAddType::NAME,
                null,
                ['validation_required' => $processor->isValidationRequired()]
            );

            if ($request->isMethod(Request::METHOD_POST)) {
                $form->submit($request);

                if ($form->isValid()) {
                    $products = $form->get('products')->getData();
                    $response = $processor->process(is_array($products) ? $products : [], $request);
                }
            }
        } else {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('orob2b.product.frontend.component_not_found.message')
            );
        }

        if ($response) {
            return $response;
        }

        if (!$form) {
            $form = $this->createForm(QuickAddType::NAME);
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @param string $name
     * @return null|ComponentProcessorInterface
     */
    protected function getProcessor($name)
    {
        $processorsRegistry = $this->get('orob2b_product.component_processor.registry');

        return $processorsRegistry->getProcessorByName($name);
    }
}
