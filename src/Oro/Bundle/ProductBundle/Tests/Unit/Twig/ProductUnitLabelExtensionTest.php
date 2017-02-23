<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUnitLabelExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ProductUnitLabelExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(ProductUnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetName()
    {
        $this->assertEquals(ProductUnitLabelExtension::NAME, $this->extension->getName());
    }
}
