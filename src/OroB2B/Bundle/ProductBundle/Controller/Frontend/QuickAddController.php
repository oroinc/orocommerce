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
        $response = null;
        $formOptions = [];

        $processor = $this->getProcessor($this->getComponentName($request));
        if ($processor) {
            $formOptions = ['validation_required' => $processor->isValidationRequired()];
        }

        $form = $this->createForm(QuickAddType::NAME, null, $formOptions);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);
            
            if (!$processor) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('orob2b.product.frontend.component_not_found.message')
                );
            }

            if ($form->isValid() && $processor) {
                $products = $form->get('products')->getData();
                $response = $processor->process(is_array($products) ? $products : [], $request);
            }
        }

        if ($response) {
            return $response;
        } else {
            return [
                'form' => $form->createView()
            ];
        }
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddType::NAME);

        return isset($formData['component']) ? $formData['component'] : null;
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
