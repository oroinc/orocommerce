<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Twig\UnitVisibilityExtension;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class UnitVisibilityExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $unitVisibility;

    /** @var UnitVisibilityExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.visibility.unit', $this->unitVisibility)
            ->getContainer($this);

        $this->extension = new UnitVisibilityExtension($container);
    }

    public function testIsUnitCodeVisible()
    {
        $code = 'test';

        $this->unitVisibility->expects(self::once())
            ->method('isUnitCodeVisible')
            ->with($code)
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_unit_code_visible', [$code]));
    }
}
