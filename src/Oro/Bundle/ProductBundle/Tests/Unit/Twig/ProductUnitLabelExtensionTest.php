<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUnitLabelExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ProductUnitLabelExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnitLabelFormatterInterface */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(UnitLabelFormatterInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.formatter.product_unit_label', $this->formatter)
            ->getContainer($this);

        $this->extension = new ProductUnitLabelExtension($container);
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     *
     * @dataProvider formatProvider
     */
    public function testFormat($unitCode, $isShort, $isPlural, $expected)
    {
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($unitCode, $isShort, $isPlural)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_format_product_unit_label',
                [$unitCode, $isShort, $isPlural]
            )
        );
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return [
            'format full single' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => false,
                'expected'  => 'kilogram',
            ],
            'format short plural' => [
                'unitCode'  => 'kg',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => 'kgs',
            ],
        ];
    }

    /**
     * @param string $unitCode
     * @param bool $isPlural
     * @param string $expected
     *
     * @dataProvider formatShortProvider
     */
    public function testFormatShort($unitCode, $isPlural, $expected)
    {
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($unitCode, true, $isPlural)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_format_short_product_unit_label',
                [$unitCode, $isPlural]
            )
        );
    }

    public function formatShortProvider(): array
    {
        return [
            'format single' => [
                'unitCode'  => 'kg',
                'isPlural'  => false,
                'expected'  => 'kilogram',
            ],
            'format plural' => [
                'unitCode'  => 'kg',
                'isPlural'  => true,
                'expected'  => 'kgs',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitLabelExtension::NAME, $this->extension->getName());
    }
}
