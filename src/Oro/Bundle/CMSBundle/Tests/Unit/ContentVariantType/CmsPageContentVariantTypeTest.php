<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Component\WebCatalog\RouteData;

class CmsPageContentVariantTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var CmsPageContentVariantType
     */
    private $type;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new CmsPageContentVariantType($this->securityFacade, $this->getPropertyAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('cms_page', $this->type->getName());
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.cms.page.entity_label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(CmsPageVariantType::class, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_cms_page_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    public function testIsSupportedPageFalse()
    {
        /** @var ContentVariantInterface|\PHPUnit_Framework_MockObject_MockObject $contentVariant **/
        $contentVariant = $this->getMock(ContentVariantInterface::class);

        $this->assertFalse($this->type->isSupportedPage($contentVariant));
    }

    public function testIsSupportedPageTrue()
    {
        /** @var ContentVariantInterface|\PHPUnit_Framework_MockObject_MockObject $contentVariant **/
        $contentVariant = $this->getMock(ContentVariantInterface::class);
        $contentVariant->expects($this->once())
            ->method('getType')
            ->willReturn('cms_page');

        $this->assertTrue($this->type->isSupportedPage($contentVariant));
    }

    public function testGetRouteData()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();
        
        /** @var Page $page */
        $page = $this->getEntity(Page::class, ['id' => 42]);
        $contentVariant->setCmsPage($page);

        $this->assertEquals(
            new RouteData('oro_cms_frontend_page_view', ['id' => 42]),
            $this->type->getRouteData($contentVariant)
        );
    }
}
