<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

abstract class AbstractAddressResolver implements ResolverInterface
{
    /** @var MatcherInterface */
    protected $matcher;

    /** @var TaxCalculatorInterface */
    protected $calculator;

    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /** @var TaxRoundingService */
    protected $roundingService;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param MatcherInterface $matcher
     * @param TaxCalculatorInterface $calculator
     * @param TaxRoundingService $roundingService
     */
    public function __construct(
        TaxationSettingsProvider $settingsProvider,
        MatcherInterface $matcher,
        TaxCalculatorInterface $calculator,
        TaxRoundingService $roundingService
    ) {
        $this->settingsProvider = $settingsProvider;
        $this->matcher = $matcher;
        $this->calculator = $calculator;
        $this->roundingService = $roundingService;
    }

    /**
     * @param Taxable $taxable
     * @return AbstractAddress
     */
    protected function getAddress(Taxable $taxable)
    {
        /** @todo: shipping origin or destination as base */
        return $taxable->getDestination();
    }
}
