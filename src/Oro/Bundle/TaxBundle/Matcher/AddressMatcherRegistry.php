<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of services that find tax rules by an address.
 */
class AddressMatcherRegistry implements ResetInterface
{
    /** @var string[] */
    private array $resolvableTypes;
    private ContainerInterface $matcherContainer;
    /** @var MatcherInterface[]|null */
    private ?array $matchers = null;

    /**
     * @param string[]           $resolvableTypes
     * @param ContainerInterface $matcherContainer
     */
    public function __construct(array $resolvableTypes, ContainerInterface $matcherContainer)
    {
        $this->resolvableTypes = $resolvableTypes;
        $this->matcherContainer = $matcherContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->matchers = null;
    }

    /**
     * @return MatcherInterface[] [resolvable type => matcher, ...]
     */
    public function getMatchers(): array
    {
        if (null === $this->matchers) {
            $this->matchers = [];
            foreach ($this->resolvableTypes as $resolvableType) {
                $this->matchers[$resolvableType] = $this->matcherContainer->get($resolvableType);
            }
        }

        return $this->matchers;
    }

    /**
     * @throws \InvalidArgumentException ff a matcher for the given resolvable type is not registered
     */
    public function getMatcherByType(string $resolvableType): MatcherInterface
    {
        $matcher = null;
        if (null === $this->matchers) {
            if ($this->matcherContainer->has($resolvableType)) {
                $matcher = $this->matcherContainer->get($resolvableType);
            }
        } elseif (isset($this->matchers[$resolvableType])) {
            $matcher = $this->matchers[$resolvableType];
        }

        if (null === $matcher) {
            $message = sprintf('Address Matcher for type "%s" is missing.', $resolvableType);
            if ($this->resolvableTypes) {
                $message .= sprintf(' Registered address matchers are "%s".', implode(', ', $this->resolvableTypes));
            }
            throw new \InvalidArgumentException($message);
        }

        return $matcher;
    }
}
