<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\Routing\RouteData;

class SystemPageContentVariantTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SystemPageContentVariantType
     */
    protected $type;

    protected function setUp(): void
    {
        $this->type = new SystemPageContentVariantType();
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
        /** @var ContentVariant|\PHPUnit\Framework\MockObject\MockObject $contentVariant **/
        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getSystemPageRoute')
            ->willReturn('test_route');

        $this->assertEquals(new RouteData('test_route'), $this->type->getRouteData($contentVariant));
    }

    public function testGetApiResourceClassName()
    {
        $this->assertEquals(SystemPage::class, $this->type->getApiResourceClassName());
    }

    public function testGetApiResourceIdentifierDqlExpression()
    {
        $this->assertEquals(
            'e.systemPageRoute',
            $this->type->getApiResourceIdentifierDqlExpression('e')
        );
    }
}
