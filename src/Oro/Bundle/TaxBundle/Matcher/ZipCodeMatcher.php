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
 * Finds tax rules by an address country, region and ZIP code.
 */
class ZipCodeMatcher implements MatcherInterface, ResetInterface
{
    use TaxRuleMergeTrait;

    private const CACHE_KEY_DELIMITER = ':';

    private ManagerRegistry $doctrine;
    private MatcherInterface $regionMatcher;
    private array $cache = [];

    public function __construct(ManagerRegistry $doctrine, MatcherInterface $regionMatcher)
    {
        $this->doctrine = $doctrine;
        $this->regionMatcher = $regionMatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes): array
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();
        $zipCode = $address->getPostalCode();

        $cacheKey = $this->getCacheKey($country, $region, $regionText, $zipCode, $taxCodes);
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $regionTaxRules = $this->regionMatcher->match($address, $taxCodes);
        if (null === $country || (null === $region && !$regionText) || !$taxCodes->isFullFilledTaxCode()) {
            return $regionTaxRules;
        }

        $zipCodeTaxRules = $this->doctrine->getRepository(TaxRule::class)->findByZipCodeAndTaxCode(
            $taxCodes,
            $zipCode,
            $country,
            $region,
            $regionText
        );

        $taxRules = $this->mergeTaxRules($regionTaxRules, $zipCodeTaxRules);
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

    private function getCacheKey(
        ?Country $country,
        ?Region $region,
        ?string $regionText,
        ?string $zipCode,
        TaxCodes $taxCodes
    ): string {
        return
            (null !== $country ? $country->getIso2Code() : '')
            . self::CACHE_KEY_DELIMITER . (null !== $region ? $region->getCombinedCode() : '')
            . self::CACHE_KEY_DELIMITER . $regionText
            . self::CACHE_KEY_DELIMITER . $zipCode
            . self::CACHE_KEY_DELIMITER . $taxCodes->getHash();
    }
}
