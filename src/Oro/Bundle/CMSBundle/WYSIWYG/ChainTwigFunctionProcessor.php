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
    private $applicableMapping;

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
    public function getApplicableMapping(): array
    {
        if ($this->applicableMapping === null) {
            $this->applicableMapping = [];
            foreach ($this->processors as $processor) {
                foreach ($processor->getApplicableMapping() as $type => $functionNames) {
                    if (!isset($this->applicableMapping[$type])) {
                        $this->applicableMapping[$type] = $functionNames;
                    } else {
                        $different = \array_diff($functionNames, $this->applicableMapping[$type]);
                        if ($different) {
                            $this->applicableMapping[$type] = \array_merge($this->applicableMapping[$type], $different);
                        }
                    }
                }
            }
        }

        return $this->applicableMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool
    {
        $isFlushNeeded = false;
        foreach ($this->processors as $processor) {
            $preparedFunctionCalls = [];
            foreach ($processor->getApplicableMapping() as $type => $functionNames) {
                if (\array_key_exists($type, $twigFunctionCalls)) {
                    $preparedFunctionCalls[$type] = \array_intersect_key(
                        $twigFunctionCalls[$type],
                        \array_flip($functionNames)
                    );
                }
            }

            if ($preparedFunctionCalls) {
                $isFlushNeeded = $processor->processTwigFunctions($processedDTO, $preparedFunctionCalls)
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
