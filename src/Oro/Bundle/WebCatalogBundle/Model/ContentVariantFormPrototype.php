<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Symfony\Component\Form\FormInterface;

/**
 * Holds a form prototype and its display title for a specific content variant type.
 *
 * This model is used by {@see ContentVariantCollectionType} to store form prototypes for each available
 * content variant type (system page, landing page, category, product page, product collection). Each prototype
 * contains the form structure and a translatable title that is displayed on the "Add ..." button in the UI.
 */
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
