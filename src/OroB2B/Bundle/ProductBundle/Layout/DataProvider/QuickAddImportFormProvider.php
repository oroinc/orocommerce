<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;

class QuickAddImportFormProvider
{
    /**
     * @var FormAccessor
     */
    protected $data;

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
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = new FormAccessor(
                $this->getForm(),
                FormAction::createByRoute('orob2b_product_frontend_quick_add_import')
            );
        }
        return $this->data;
    }

    /**
     * @param array $options
     * @return FormInterface
     */
    public function getForm(array $options = [])
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(QuickAddImportFromFileType::NAME, null, $options);
        }
        return $this->form;
    }
}
