<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Component\WebCatalog\RouteData;

class SystemPageContentVariantTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SystemPageContentVariantType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new SystemPageContentVariantType();
    }

    public function testGetName()
    {
        $this->assertEquals('system_page', $this->type->getName());
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.webcatalog.contentvariant.variant_type.system_page.label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(SystemPageVariantType::class, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->assertTrue($this->type->isAllowed());
    }

    public function testGetRouteData()
    {
        /** @var ContentVariant|\PHPUnit_Framework_MockObject_MockObject $contentVariant **/
        $contentVariant = $this->getMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getSystemPageRoute')
            ->willReturn('test_route');

        $this->assertEquals(new RouteData('test_route'), $this->type->getRouteData($contentVariant));
    }
}
