<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Oro\Bundle\CheckoutBundle\Exception\CheckoutLineItemConverterNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The registry of checkout line item converters.
 */
class CheckoutLineItemConverterRegistry
{
    /** @var iterable|CheckoutLineItemConverterInterface[] */
    private $converters;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param iterable|CheckoutLineItemConverterInterface[] $converters
     * @param LoggerInterface                               $logger
     */
    public function __construct(iterable $converters, LoggerInterface $logger)
    {
        $this->converters = $converters;
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
}
