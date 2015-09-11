<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;

class QuickAddHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $formFactory
     * @param ComponentProcessorRegistry $componentRegistry
     */
    public function __construct(
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        ComponentProcessorRegistry $componentRegistry
    ) {
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->componentRegistry = $componentRegistry;
    }

    /**
     * @param Request $request
     * @return array ['form' => FormInterface, 'response' => Response|null]
     */
    public function process(Request $request)
    {
        $response = null;
        $formOptions = [];

        $processor = $this->getProcessor($this->getComponentName($request));
        if ($processor) {
            $formOptions['validation_required'] = $processor->isValidationRequired();
        }

        $form = $this->createQuickAddForm($formOptions);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);
            if ($processor && $processor->isAllowed()) {
                if ($form->isValid()) {
                    $products = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
                    $products = is_array($products) ? $products : [];
                    $response = $processor->process($products, $request);
                    if (!$response) {
                        // reset form
                        $form = $this->createQuickAddForm($formOptions);
                    }
                }
            } else {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans('orob2b.product.frontend.component_not_accessible.message')
                );
            }
        }

        return ['form' => $form, 'response' => $response];
    }

    /**
     * @param array $options
     * @return FormInterface
     */
    protected function createQuickAddForm(array $options = [])
    {
        return $this->formFactory->create(QuickAddType::NAME, null, $options);
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddType::NAME, []);

        $name = null;
        if (array_key_exists(QuickAddType::COMPONENT_FIELD_NAME, $formData)) {
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
        return $this->componentRegistry->getProcessorByName($name);
    }
}
