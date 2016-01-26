<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;

class QuickAddFormProvider
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        FormFactoryInterface $formFactory
    ) {
        $this->formFactory = $formFactory;
    }

    /**
     * @param array $options
     * @return FormInterface
     */
    public function getForm(array $options = [])
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(QuickAddType::NAME, null, $options);
        }
        return $this->form;
    }
}
