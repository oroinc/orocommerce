<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a valuer for a specific WYSIWYG field.
 */
class ComputeRenderedWYSIWYGField implements ProcessorInterface
{
    /** @var WYSIWYGValueRenderer */
    private $wysiwygValueRenderer;

    /** @var string */
    private $fieldName;

    /** @var string */
    private $valueFieldName;

    /** @var string */
    private $styleFieldName;

    /**
     * @param WYSIWYGValueRenderer $wysiwygValueRenderer
     * @param string               $fieldName
     * @param string               $valueFieldName
     * @param string               $styleFieldName
     */
    public function __construct(
        WYSIWYGValueRenderer $wysiwygValueRenderer,
        string $fieldName,
        string $valueFieldName,
        string $styleFieldName
    ) {
        $this->wysiwygValueRenderer = $wysiwygValueRenderer;
        $this->fieldName = $fieldName;
        $this->valueFieldName = $valueFieldName;
        $this->styleFieldName = $styleFieldName;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if ($context->isFieldRequested($this->fieldName, $data)) {
            $data[$this->fieldName] = $this->wysiwygValueRenderer->render(
                $data[$this->valueFieldName] ?? null,
                $data[$this->styleFieldName] ?? null
            );
            $context->setData($data);
        }
    }
}
