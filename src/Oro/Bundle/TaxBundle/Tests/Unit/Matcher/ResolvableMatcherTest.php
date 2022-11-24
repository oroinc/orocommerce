<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\AddressMatcherRegistry;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Matcher\ResolvableMatcher;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

class ResolvableMatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var MatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $matcher;

    /** @var ResolvableMatcher */
    private $resolvableMatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = $this->createMock(MatcherInterface::class);

        $addressMatcherRegistry = $this->createMock(AddressMatcherRegistry::class);
        $addressMatcherRegistry->expects($this->once())
            ->method('getMatcherByType')
            ->with('test_granularity')
            ->willReturn($this->matcher);

        $addressResolverSettingsProvider = $this->createMock(AddressResolverSettingsProvider::class);
        $addressResolverSettingsProvider->expects($this->once())
            ->method('getAddressResolverGranularity')
            ->willReturn('test_granularity');

        $this->resolvableMatcher = new ResolvableMatcher($addressMatcherRegistry, $addressResolverSettingsProvider);
    }

    public function testMatch()
    {
        $address = $this->createMock(Address::class);
        $taxCodes = TaxCodes::create([
            TaxCode::create('PRODUCT_TAX_CODE', TaxCodeInterface::TYPE_PRODUCT),
            TaxCode::create('ACCOUNT_TAX_CODE', TaxCodeInterface::TYPE_ACCOUNT)
        ]);
        $taxRules = [$this->createMock(TaxRule::class)];

        $this->matcher->expects(self::once())
            ->method('match')
            ->with($address, $taxCodes)
            ->willReturn($taxRules);

        self::assertSame($taxRules, $this->resolvableMatcher->match($address, $taxCodes));
    }
}
