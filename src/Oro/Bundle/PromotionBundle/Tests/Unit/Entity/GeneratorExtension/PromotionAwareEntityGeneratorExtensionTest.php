<?php
declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\GeneratorExtension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\GeneratorExtension\PromotionAwareEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

class PromotionAwareEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider classDataProvider
     */
    public function testSupports(string $class, bool $expected)
    {
        $extension = new PromotionAwareEntityGeneratorExtension();
        $extension->registerSupportedEntity(Order::class);
        $schema = ['class' => $class];
        static::assertSame($expected, $extension->supports($schema));
    }

    public function classDataProvider(): array
    {
        return [
            'supported Order' => [Order::class, true],
            'unsupported' => [\stdClass::class, false],
        ];
    }

    public function testGeneratePropertyExists()
    {
        $class = new ClassGenerator();
        $class->addProperty('appliedPromotions');
        $class->addProperty('appliedCoupons');

        (new PromotionAwareEntityGeneratorExtension())->generate([], $class);

        static::assertContains(AppliedPromotionsAwareInterface::class, $class->getImplements());
        static::assertContains(AppliedCouponsAwareInterface::class, $class->getImplements());
    }

    public function testGenerateOnlyAppliedCouponsPropertyExists()
    {
        $class = new ClassGenerator();
        $class->addProperty('appliedPromotions');

        (new PromotionAwareEntityGeneratorExtension())->generate([], $class);

        static::assertContains(AppliedPromotionsAwareInterface::class, $class->getImplements());
        static::assertNotContains(AppliedCouponsAwareInterface::class, $class->getImplements());
    }

    public function testGeneratePropertyDoesNotExists()
    {
        $class = new ClassGenerator();

        (new PromotionAwareEntityGeneratorExtension())->generate([], $class);

        static::assertNotContains(AppliedPromotionsAwareInterface::class, $class->getImplements());
        static::assertNotContains(AppliedCouponsAwareInterface::class, $class->getImplements());
    }
}
