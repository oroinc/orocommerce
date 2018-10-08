<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\Twig\RelatedItemExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class RelatedItemExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var RelatedItemConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var RelatedItemExtension */
    private $extension;

    protected function setUp()
    {
        $this->helper = $this->createMock(RelatedItemConfigHelper::class);
        $this->extension = new RelatedItemExtension($this->helper);
    }

    public function testGetRelatedItemsTranslationKeyReturnsTranslationKey()
    {
        $expected = 'translation_key';

        $this->helper->expects($this->once())->method('getRelatedItemsTranslationKey')->willReturn($expected);
        $this->assertEquals($this->extension->getRelatedItemsTranslationKey(), $expected);
    }
}
