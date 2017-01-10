<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRuleValuesConverter;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodsConfigsRulesProvider;

class ShippingMethodsConfigsRulesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingMethodsConfigsRuleRepository */
    private $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingContextToRuleValuesConverter */
    private $converter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RuleFiltrationServiceInterface */
    private $filtrationService;

    /** @var ShippingMethodsConfigsRulesProvider */
    private $provider;

    /** @var array|ShippingMethodsConfigsRule[] */
    private $ruleCollection;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(ShippingMethodsConfigsRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->converter = $this->getMockBuilder(ShippingContextToRuleValuesConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter->expects(static::once())
            ->method('convert')
            ->willReturn([]);

        $this->ruleCollection = [
            (new ShippingMethodsConfigsRule())->setCurrency('USD'),
            (new ShippingMethodsConfigsRule())->setCurrency('UAH'),
        ];

        $this->filtrationService = $this->getMockBuilder(RuleFiltrationServiceInterface::class)
            ->setMethods(['getFilteredRuleOwners'])
            ->getMock();
        $this->filtrationService->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->willReturn($this->ruleCollection);

        $this->provider = new ShippingMethodsConfigsRulesProvider(
            $this->filtrationService,
            $this->converter,
            $this->repository
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithShippingAddress()
    {
        $this->repository->expects(static::once())
            ->method('getByDestinationAndCurrency')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $context = new ShippingContext([
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createMock(AddressInterface::class)
        ]);

        static::assertSame(
            $this->ruleCollection,
            $this->provider->getAllFilteredShippingMethodsConfigs($context)
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithoutShippingAddress()
    {
        $this->repository->expects(static::once())
            ->method('getByCurrencyWithoutDestination')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $context = new ShippingContext([]);

        static::assertSame(
            $this->ruleCollection,
            $this->provider->getAllFilteredShippingMethodsConfigs($context)
        );
    }
}
