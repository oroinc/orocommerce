<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Symfony\Component\Form\Form;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class ProcessPrecisionsAfterValidation implements ProcessorInterface
{
    protected $doctrineHelper;

    /**
     * ProcessPrecisionsAfterValidation constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */
        if (!$context->hasForm()) {
            return;
        }

        /** @var Form $form */
        $form = $context->getForm();

        if (!$form->isSubmitted()) {
            return;
        }

        if (!$form->isValid()) {
            $this->handleProductUnitPrecisions($context);
        }
    }

    abstract public function handleProductUnitPrecisions(FormContext $formContext);
}
