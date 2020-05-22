<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\RedirectBundle\Form\DataTransformer\PrefixWithRedirectToStringTransformer;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

class PrefixWithRedirectToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PrefixWithRedirectToStringTransformer
     */
    protected $transformer;

    protected function setUp(): void
    {
        $this->transformer = new PrefixWithRedirectToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param string|null $value
     * @param PrefixWithRedirect|null $expected
     */
    public function testTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            [
                'value' => '',
                'expected' => (new PrefixWithRedirect())->setPrefix('')->setCreateRedirect(false)
            ],
            [
                'value' => 'test',
                'expected' => (new PrefixWithRedirect())->setPrefix('test')->setCreateRedirect(false)
            ],
            [
                'value' => null,
                'expected' => null
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param PrefixWithRedirect|null $value
     * @param string|null $expected
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            [
                'value' => (new PrefixWithRedirect())->setPrefix('')->setCreateRedirect(false),
                'expected' => ''
            ],
            [
                'value' => (new PrefixWithRedirect())->setPrefix('test')->setCreateRedirect(false),
                'expected' => 'test'
            ],
            [
                'value' => null,
                'expected' => null
            ]
        ];
    }
}
