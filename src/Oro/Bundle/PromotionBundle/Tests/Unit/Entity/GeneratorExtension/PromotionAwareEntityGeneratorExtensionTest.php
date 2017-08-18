<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\GeneratorExtension;

use CG\Generator\PhpClass;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\GeneratorExtension\PromotionAwareEntityGeneratorExtension;

class PromotionAwareEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider classDataProvider
     * @param string $class
     * @param bool $expected
     */
    public function testSupports($class, $expected)
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = ['class' => $class];
        $this->assertSame($expected, $extension->supports($schema));
    }

    /**
     * @return array
     */
    public function classDataProvider(): array
    {
        return [
            'supported' => [Order::class, true],
            'unsupported' => [\stdClass::class, false],
        ];
    }

    public function testGeneratePropertyExists()
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = [];
        /** @var PhpClass|\PHPUnit_Framework_MockObject_MockObject $class */
        $class = $this->createMock(PhpClass::class);
        $class->expects($this->exactly(2))
            ->method('hasProperty')
            ->withConsecutive(
                ['appliedDiscounts'],
                ['appliedCoupons']
            )
            ->willReturn(true);
        $class->expects($this->exactly(2))
            ->method('addInterfaceName')
            ->withConsecutive(
                [AppliedDiscountsAwareInterface::class],
                [AppliedCouponsAwareInterface::class]
            );
        $extension->generate($schema, $class);
    }

    public function testGeneratePropertyDoesNotExists()
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = [];
        /** @var PhpClass|\PHPUnit_Framework_MockObject_MockObject $class */
        $class = $this->createMock(PhpClass::class);
        $class->expects($this->exactly(2))
            ->method('hasProperty')
            ->withConsecutive(
                ['appliedDiscounts'],
                ['appliedCoupons']
            )
            ->willReturn(false);
        $class->expects($this->never())
            ->method('addInterfaceName');
        $extension->generate($schema, $class);
    }
}
