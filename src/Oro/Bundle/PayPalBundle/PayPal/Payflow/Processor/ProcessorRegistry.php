<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;

class ProcessorRegistry
{
    /** @var ProcessorInterface[] */
    protected $processors = [];

    /** @var ProcessorInterface */
    protected $fallback;

    /**
     * @param ProcessorInterface $processor
     * @return $this
     */
    public function addProcessor(ProcessorInterface $processor)
    {
        $this->processors[$processor->getCode()] = $processor;

        return $this;
    }

    /**
     * @param string $code
     * @return ProcessorInterface
     * @throws \InvalidArgumentException If processor is missing
     */
    public function getProcessor($code)
    {
        $code = (string)$code;

        if (!array_key_exists($code, Partner::$partners)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Processor "%s" is missing. Registered processors are "%s"',
                    $code,
                    implode(', ', array_keys($this->processors))
                )
            );
        }

        if (array_key_exists($code, $this->processors)) {
            return $this->processors[$code];
        }

        return $this->getFallbackProcessor();
    }

    /**
     * @return ProcessorInterface
     */
    protected function getFallbackProcessor()
    {
        if (!$this->fallback) {
            $this->fallback = new FallbackProcessor();
        }

        return $this->fallback;
    }
}
