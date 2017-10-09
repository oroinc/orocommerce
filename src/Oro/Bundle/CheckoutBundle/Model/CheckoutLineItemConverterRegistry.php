<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Oro\Bundle\CheckoutBundle\Exception\CheckoutLineItemConverterNotFoundException;
use Psr\Log\LoggerInterface;

class CheckoutLineItemConverterRegistry
{
    /** @var array|CheckoutLineItemConverterInterface[] */
    protected $converters = [];

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed $source
     *
     * @return CheckoutLineItemConverterInterface
     *
     * @throws CheckoutLineItemConverterNotFoundException
     */
    public function getConverter($source)
    {
        foreach ($this->converters as $converter) {
            if ($converter->isSourceSupported($source)) {
                return $converter;
            }
        }

        $exception = new CheckoutLineItemConverterNotFoundException($source);

        $this->logger->critical($exception->getMessage(), ['source_instance' => $source]);

        throw $exception;
    }

    /**
     * @param CheckoutLineItemConverterInterface $converter
     * @param string $alias
     *
     * @return $this
     */
    public function addConverter(CheckoutLineItemConverterInterface $converter, $alias)
    {
        $this->converters[$alias] = $converter;

        return $this;
    }
}
