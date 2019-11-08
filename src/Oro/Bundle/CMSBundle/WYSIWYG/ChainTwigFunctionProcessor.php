<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

/**
 * Delegates the processing of twig function to all child processors.
 */
class ChainTwigFunctionProcessor implements WYSIWYGTwigFunctionProcessorInterface
{
    /** @var iterable|WYSIWYGTwigFunctionProcessorInterface[] */
    private $processors;

    /** @var string[] */
    private $applicableFieldTypes;

    /** @var string[] */
    private $acceptedTwigFunctions;

    /**
     * @param iterable|WYSIWYGTwigFunctionProcessorInterface[] $processors
     */
    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableFieldTypes(): array
    {
        if ($this->applicableFieldTypes === null) {
            $this->applicableFieldTypes = [];
            foreach ($this->processors as $processor) {
                $this->applicableFieldTypes[] = $processor->getApplicableFieldTypes();
            }

            if ($this->applicableFieldTypes) {
                $this->applicableFieldTypes = \array_values(
                    \array_unique(
                        \array_merge(...$this->applicableFieldTypes)
                    )
                );
            }
        }

        return $this->applicableFieldTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAcceptedTwigFunctions(): array
    {
        if ($this->acceptedTwigFunctions === null) {
            $this->acceptedTwigFunctions = [];
            foreach ($this->processors as $processor) {
                $this->acceptedTwigFunctions[] = $processor->getAcceptedTwigFunctions();
            }

            if ($this->acceptedTwigFunctions) {
                $this->acceptedTwigFunctions = \array_values(
                    \array_unique(
                        \array_merge(...$this->acceptedTwigFunctions)
                    )
                );
            }
        }

        return $this->acceptedTwigFunctions;
    }

    /**
     * {@inheritdoc}
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool
    {
        $isFlushNeeded = false;
        $fieldType = $processedDTO->getProcessedEntity()->getFieldType();
        foreach ($this->processors as $processor) {
            if (\in_array($fieldType, $processor->getApplicableFieldTypes(), false)) {
                $processorTwigFunctionCalls = array_intersect_key(
                    $twigFunctionCalls,
                    \array_flip($processor->getAcceptedTwigFunctions())
                );

                $isFlushNeeded = $processor->processTwigFunctions($processedDTO, $processorTwigFunctionCalls)
                    || $isFlushNeeded;
            }
        }

        return $isFlushNeeded;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRemove(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $isFlushNeeded = false;
        foreach ($this->processors as $processor) {
            $isFlushNeeded = $processor->onPreRemove($processedDTO) || $isFlushNeeded;
        }

        return $isFlushNeeded;
    }
}
