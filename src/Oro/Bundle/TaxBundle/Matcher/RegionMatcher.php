<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Finds tax rules by an address country and region.
 */
class RegionMatcher implements MatcherInterface, ResetInterface
{
    use TaxRuleMergeTrait;

    private const CACHE_KEY_DELIMITER = ':';

    private ManagerRegistry $doctrine;
    private MatcherInterface $countryMatcher;
    private array $cache = [];

    public function __construct(ManagerRegistry $doctrine, MatcherInterface $countryMatcher)
    {
        $this->doctrine = $doctrine;
        $this->countryMatcher = $countryMatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes): array
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();

        $cacheKey = $this->getCacheKey($country, $region, $regionText, $taxCodes);
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $countryTaxRules = $this->countryMatcher->match($address, $taxCodes);
        if (null === $country || (null === $region && !$regionText) || !$taxCodes->isFullFilledTaxCode()) {
            return $countryTaxRules;
        }

        $regionTaxRules = $this->doctrine->getRepository(TaxRule::class)->findByRegionAndTaxCode(
            $taxCodes,
            $country,
            $region,
            $regionText
        );

        $taxRules = $this->mergeTaxRules($countryTaxRules, $regionTaxRules);
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

    private function getCacheKey(?Country $country, ?Region $region, ?string $regionText, TaxCodes $taxCodes): string
    {
        return
            (null !== $country ? $country->getIso2Code() : '')
            . self::CACHE_KEY_DELIMITER . (null !== $region ? $region->getCombinedCode() : '')
            . self::CACHE_KEY_DELIMITER . $regionText
            . self::CACHE_KEY_DELIMITER . $taxCodes->getHash();
    }
}
