<?php

namespace Oro\Bundle\TaxBundle\Matcher;

class AddressMatcherRegistry implements AddressMatcherRegistryInterface
{
    /** @var MatcherInterface[] */
    protected $matchers = [];

    /**
     * {@inheritdoc}
     */
    public function getMatchers()
    {
        return $this->matchers;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherByType($resolvableType)
    {
        $resolvableType = (string)$resolvableType;

        if (array_key_exists($resolvableType, $this->matchers)) {
            return $this->matchers[$resolvableType];
        }

        $message = sprintf('Address Matcher for type "%s" is missing.', $resolvableType);
        if ($this->matchers) {
            $message .= sprintf(' Registered address matchers are "%s"', implode(', ', array_keys($this->matchers)));
        }

        throw new \InvalidArgumentException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function addMatcher($resolvableType, MatcherInterface $matcher)
    {
        $this->matchers[$resolvableType] = $matcher;
    }
}
