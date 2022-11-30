<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Finds tax rules by an address country.
 */
class CountryMatcher implements MatcherInterface, ResetInterface
{
    private const CACHE_KEY_DELIMITER = ':';

    private ManagerRegistry $doctrine;
    private array $cache = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes): array
    {
        $country = $address->getCountry();
        if (null === $country || !$taxCodes->isFullFilledTaxCode()) {
            return [];
        }

        $cacheKey = $country->getIso2Code() . self::CACHE_KEY_DELIMITER . $taxCodes->getHash();
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $taxRules = $this->doctrine->getRepository(TaxRule::class)->findByCountryAndTaxCode($taxCodes, $country);
        $this->cache[$cacheKey] = $taxRules;

        return $taxRules;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cache = [];
    }
}
