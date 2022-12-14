<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\TaxBundle\Matcher\AddressMatcherRegistry;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AddressMatcherRegistryTest extends \PHPUnit\Framework\TestCase
{
    private const REGION = 'region';
    private const COUNTRY = 'country';

    /** @var MatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $regionMatcher;

    /** @var MatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $countryMatcher;

    /** @var AddressMatcherRegistry */
    private $matcherRegistry;

    protected function setUp(): void
    {
        $this->regionMatcher = $this->createMock(MatcherInterface::class);
        $this->countryMatcher = $this->createMock(MatcherInterface::class);

        $matcherContainer = TestContainerBuilder::create()
            ->add(self::REGION, $this->regionMatcher)
            ->add(self::COUNTRY, $this->countryMatcher)
            ->getContainer($this);

        $this->matcherRegistry = new AddressMatcherRegistry(
            [self::REGION, self::COUNTRY],
            $matcherContainer
        );
    }

    public function testGetMatchers()
    {
        $matchers = $this->matcherRegistry->getMatchers();
        $this->assertCount(2, $matchers);
        $this->assertEquals($this->regionMatcher, $matchers[self::REGION]);
        $this->assertEquals($this->countryMatcher, $matchers[self::COUNTRY]);
    }

    public function testGetMatcherByTypeWhenMatchersCollectionIsNotInitializedYet()
    {
        $this->assertEquals($this->regionMatcher, $this->matcherRegistry->getMatcherByType(self::REGION));
        $this->assertEquals($this->countryMatcher, $this->matcherRegistry->getMatcherByType(self::COUNTRY));
    }

    public function testGetMatcherByTypeWhenMatchersCollectionIsAlreadyInitialized()
    {
        // initialize matchers collection
        $this->matcherRegistry->getMatchers();

        $this->assertEquals($this->regionMatcher, $this->matcherRegistry->getMatcherByType(self::REGION));
        $this->assertEquals($this->countryMatcher, $this->matcherRegistry->getMatcherByType(self::COUNTRY));
    }

    public function testGetMatcherByTypeForNotExistingType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Address Matcher for type "not_existing" is missing. Registered address matchers are "region, country".'
        );

        $this->matcherRegistry->getMatcherByType('not_existing');
    }

    public function testGetMatcherByTypeForNotExistingTypeAndNoRefisteredMatchers()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address Matcher for type "not_existing" is missing.');

        $matcherRegistry = new AddressMatcherRegistry(
            [],
            TestContainerBuilder::create()->getContainer($this)
        );
        $matcherRegistry->getMatcherByType('not_existing');
    }
}
