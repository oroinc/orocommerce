<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\RedirectBundle\Form\DataTransformer\PrefixWithRedirectToStringTransformer;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;

class PrefixWithRedirectToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PrefixWithRedirectToStringTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new PrefixWithRedirectToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?string $value, ?PrefixWithRedirect $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
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
     */
    public function testReverseTransform(?PrefixWithRedirect $value, ?string $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
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
