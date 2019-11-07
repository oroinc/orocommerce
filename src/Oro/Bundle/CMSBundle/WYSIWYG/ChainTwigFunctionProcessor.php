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
    private $applicablePositions;

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
        if ($this->applicablePositions === null) {
            $this->applicablePositions = [];
            foreach ($this->processors as $processor) {
                $this->applicablePositions[] = $processor->getApplicableFieldTypes();
            }

            if ($this->applicablePositions) {
                $this->applicablePositions = array_unique(array_merge(...$this->applicablePositions));
            }
        }

        return $this->applicablePositions;
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
                $this->acceptedTwigFunctions = array_unique(array_merge(...$this->acceptedTwigFunctions));
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
        foreach ($this->processors as $processor) {
            $fieldType = $processedDTO->getProcessedEntity()->getFieldType();
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
