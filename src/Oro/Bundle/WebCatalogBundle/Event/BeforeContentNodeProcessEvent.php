<?php

namespace Oro\Bundle\WebCatalogBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class BeforeContentNodeProcessEvent extends Event
{
    /**
     * @var ContentNode
     */
    protected $contentNode;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var bool
     */
    protected $formProcessInterrupted = false;

    /**
     * @param FormInterface $form
     * @param ContentNode $contentNode
     */
    public function __construct(FormInterface $form, ContentNode $contentNode)
    {
        $this->form = $form;
        $this->contentNode = $contentNode;
    }

    /**
     * @return ContentNode
     */
    public function getContentNode()
    {
        return $this->contentNode;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return BeforeContentNodeProcessEvent
     */
    public function interruptFormProcess()
    {
        $this->formProcessInterrupted = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFormProcessInterrupted()
    {
        return $this->formProcessInterrupted;
    }
}
