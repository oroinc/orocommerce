<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Twig\ProductImageExtension;

class ProductImageExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var ProductImageExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->attachmentManager = $this->getMockBuilder(AttachmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ProductImageExtension($this->attachmentManager);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductImageExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['filtered_product_image_url', [$this->attachmentManager, 'getFilteredImageUrl']],
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->extension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }
}
