<?php

namespace Oro\Component\WebCatalog\Model;

use Symfony\Component\Form\FormInterface;

class ContentVariantFormPrototype
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var string
     */
    protected $title;

    /**
     * @param FormInterface $form
     * @param string|null   $title
     */
    public function __construct(FormInterface $form, $title = null)
    {
        $this->form = $form;
        $this->title = $title;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
