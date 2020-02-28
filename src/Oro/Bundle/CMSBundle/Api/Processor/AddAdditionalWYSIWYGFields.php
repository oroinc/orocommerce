<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\Type\ArrayType;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Add additional WYSIWYG fields to form.
 */
class AddAdditionalWYSIWYGFields implements ProcessorInterface
{
    /**
     * @var WYSIWYGFieldsProvider
     */
    private $wysiwygFieldsProvider;

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */
        $formBuilder = $context->getFormBuilder();
        if (!$formBuilder) {
            return;
        }

        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($context->getClassName());

        foreach ($wysiwygFields as $fieldName) {
            if ($formBuilder->has($fieldName)) {
                $formBuilder->add($fieldName . WYSIWYGStyleType::TYPE_SUFFIX);
                $formBuilder->add($fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX, ArrayType::class);
            }
        }
    }
}
