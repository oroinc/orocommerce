<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Condition\ShippingMethodHasEnabledShippingRules;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ShippingMethodHasEnabledShippingRulesTest extends \PHPUnit\Framework\TestCase
{
    private const PROPERTY_PATH_NAME = 'testPropertyPath';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PropertyPathInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyPath;

    /** @var ShippingMethodHasEnabledShippingRules */
    private $condition;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->propertyPath = $this->createMock(PropertyPathInterface::class);
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->willReturn(self::PROPERTY_PATH_NAME);
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->willReturn([self::PROPERTY_PATH_NAME]);

        $this->condition = new ShippingMethodHasEnabledShippingRules($this->doctrine);
    }

    public function testGetName(): void
    {
        self::assertEquals('shipping_method_has_enabled_shipping_rules', $this->condition->getName());
    }

    public function testInitializeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "method_identifier" option');

        $this->condition->initialize([]);
    }

    public function testInitialize(): void
    {
        self::assertSame(
            $this->condition,
            $this->condition->initialize(['method_identifier'])
        );
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(array $rules, bool $expected): void
    {
        $repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getEnabledRulesByMethod')
            ->willReturn($rules);

        $this->condition->initialize(['method_identifier']);
        self::assertEquals($expected, $this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            'no_rules' => [
                'rules' => [],
                'expected' => false,
            ],
            'with_rules' => [
                'rules' => [
                    new ShippingMethodsConfigsRule(),
                    new ShippingMethodsConfigsRule(),
                ],
                'expected' => true,
            ],
        ];
    }

    public function testToArray(): void
    {
        $result = $this->condition->initialize([$this->propertyPath])->toArray();

        $this->assertEquals(
            sprintf('$%s', self::PROPERTY_PATH_NAME),
            $result['@shipping_method_has_enabled_shipping_rules']['parameters'][0]
        );
    }

    public function testCompile(): void
    {
        $result = $this->condition->compile('$factoryAccessor');

        self::assertStringContainsString(
            '$factoryAccessor->create(\'shipping_method_has_enabled_shipping_rules\'',
            $result
        );
    }
}
