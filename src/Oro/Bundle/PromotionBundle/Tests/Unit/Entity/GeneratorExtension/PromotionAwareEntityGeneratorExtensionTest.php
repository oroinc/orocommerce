<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\GeneratorExtension;

use CG\Generator\PhpClass;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\GeneratorExtension\PromotionAwareEntityGeneratorExtension;

class PromotionAwareEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider classDataProvider
     * @param string $class
     * @param bool $expected
     */
    public function testSupports($class, $expected)
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $extension->registerSupportedEntity(Order::class);
        $schema = ['class' => $class];
        $this->assertSame($expected, $extension->supports($schema));
    }

    /**
     * @return array
     */
    public function classDataProvider(): array
    {
        return [
            'supported Order' => [Order::class, true],
            'unsupported' => [\stdClass::class, false],
        ];
    }

    public function testGeneratePropertyExists()
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = [];
        /** @var PhpClass|\PHPUnit\Framework\MockObject\MockObject $class */
        $class = $this->createMock(PhpClass::class);
        $class->expects($this->exactly(2))
            ->method('hasProperty')
            ->withConsecutive(
                ['appliedPromotions'],
                ['appliedCoupons']
            )
            ->willReturn(true);
        $class->expects($this->exactly(2))
            ->method('addInterfaceName')
            ->withConsecutive(
                [AppliedPromotionsAwareInterface::class],
                [AppliedCouponsAwareInterface::class]
            );
        $extension->generate($schema, $class);
    }

    public function testGenerateOnlyAppliedCouponsPropertyExists()
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = [];
        /** @var PhpClass|\PHPUnit\Framework\MockObject\MockObject $class */
        $class = $this->createMock(PhpClass::class);
        $class->expects($this->any())
            ->method('hasProperty')
            ->willReturnMap([
                ['appliedPromotions', false],
                ['appliedCoupons', true]
            ]);

        $class->expects($this->once())
            ->method('addInterfaceName')
            ->with(AppliedCouponsAwareInterface::class);

        $extension->generate($schema, $class);
    }

    public function testGeneratePropertyDoesNotExists()
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $schema = [];
        /** @var PhpClass|\PHPUnit\Framework\MockObject\MockObject $class */
        $class = $this->createMock(PhpClass::class);
        $class->expects($this->exactly(2))
            ->method('hasProperty')
            ->withConsecutive(
                ['appliedPromotions'],
                ['appliedCoupons']
            )
            ->willReturn(false);
        $class->expects($this->never())
            ->method('addInterfaceName');
        $extension->generate($schema, $class);
    }
}
