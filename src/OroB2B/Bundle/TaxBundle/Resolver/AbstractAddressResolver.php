<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

abstract class AbstractAddressResolver implements ResolverInterface
{
    /** @var MatcherInterface */
    protected $matcher;

    /** @var TaxCalculatorInterface */
    protected $calculator;

    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param MatcherInterface $matcher
     * @param TaxCalculatorInterface $calculator
     */
    public function __construct(
        TaxationSettingsProvider $settingsProvider,
        MatcherInterface $matcher,
        TaxCalculatorInterface $calculator
    ) {
        $this->settingsProvider = $settingsProvider;
        $this->matcher = $matcher;
        $this->calculator = $calculator;
    }
}
