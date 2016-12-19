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
    public function testGetAllFilteredShippingMethodsConfigs()
    {
        $repository = $this->getMockBuilder(ShippingMethodsConfigsRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(static::once())
            ->method('getByDestinationAndCurrency')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $converter = $this->getMockBuilder(ShippingContextToRuleValuesConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converter->expects(static::once())
            ->method('convert')
            ->willReturn([]);

        $result = [
            (new ShippingMethodsConfigsRule())->setCurrency('USD'),
            (new ShippingMethodsConfigsRule())->setCurrency('UAH'),
        ];
        $filtrationService = $this->getMockBuilder(RuleFiltrationServiceInterface::class)
            ->setMethods(['getFilteredRuleOwners'])
            ->getMock();
        $filtrationService->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->willReturn($result);

        $provider = new ShippingMethodsConfigsRulesProvider(
            $filtrationService,
            $converter,
            $repository
        );

        $address = $this->getMock(AddressInterface::class);
        $context = new ShippingContext();
        $context->setShippingAddress($address);

        static::assertSame($result, $provider->getAllFilteredShippingMethodsConfigs($context));
    }
}
