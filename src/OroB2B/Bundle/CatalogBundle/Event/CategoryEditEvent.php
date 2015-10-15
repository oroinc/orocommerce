<?php

namespace OroB2B\Bundle\CatalogBundle\Event;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class CategoryEditEvent extends Event implements FormAwareInterface
{
    const NAME = 'orob2b.catalog.category_edit';

    /** @var FormInterface */
    private $form;

    public function __construct(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }
}
