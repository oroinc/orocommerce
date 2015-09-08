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
        $flashBag = $this->get('session')->getFlashBag();

        $processor = $this->getProcessor($this->getComponentName($request));
        if ($processor) {
            $formOptions = ['validation_required' => $processor->isValidationRequired()];
        }

        $form = $this->createForm(QuickAddType::NAME, null, $formOptions);
        if ($request->isMethod(Request::METHOD_POST)) {
            if ($processor) {
                $form->submit($request);
                if ($processor->isAllowed()) {
                    if ($form->isValid()) {
                        $products = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
                        $response = $processor->process(is_array($products) ? $products : [], $request);
                        if (!$response) {
                            // reset form
                            $form = $this->createForm(QuickAddType::NAME, null, $formOptions);
                        }
                    }
                } else {
                    $flashBag->add(
                        'error',
                        $this->get('translator')->trans('orob2b.product.frontend.component_not_allowed.message')
                    );
                }
            } else {
                $flashBag->add(
                    'error',
                    $this->get('translator')->trans('orob2b.product.frontend.component_not_found.message')
                );
            }
        }

        return $response ?: ['form' => $form->createView()];
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddType::NAME);

        $name = null;
        if (isset($formData[QuickAddType::COMPONENT_FIELD_NAME])) {
            $name = $formData[QuickAddType::COMPONENT_FIELD_NAME];
        }

        return $name;
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
