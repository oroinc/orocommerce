<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

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
     * @param string $title
     */
    public function __construct(FormInterface $form, $title)
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
