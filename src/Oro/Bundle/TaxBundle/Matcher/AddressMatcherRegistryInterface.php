<?php

namespace Oro\Bundle\TaxBundle\Matcher;

interface AddressMatcherRegistryInterface
{
    /**
     * @return MatcherInterface[]
     */
    public function getMatchers();

    /**
     * @param string $resolvableType
     * @return MatcherInterface
     * @throws \InvalidArgumentException If matcher not registered
     */
    public function getMatcherByType($resolvableType);

    /**
     * @param string $resolvableType
     * @param MatcherInterface $matcher
     */
    public function addMatcher($resolvableType, MatcherInterface $matcher);
}
