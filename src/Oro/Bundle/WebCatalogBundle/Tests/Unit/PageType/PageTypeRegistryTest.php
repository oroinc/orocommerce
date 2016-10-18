<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\PageType;

use Oro\Component\WebCatalog\PageTypeInterface;
use Oro\Bundle\WebCatalogBundle\PageType\PageTypeRegistry;

class PageTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PageTypeRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new PageTypeRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testAddPageType()
    {
        $pageTypeName = 'test_type';

        /** @var PageTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType */
        $pageType = $this->getMock(PageTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn($pageTypeName);

        $this->registry->addPageType($pageType);

        $this->assertEquals([$pageTypeName => $pageType], $this->registry->getPageTypes());
    }

    public function testGetPageType()
    {
        /** @var PageTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType */
        $pageType = $this->getMock(PageTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn('test_type');

        $this->registry->addPageType($pageType);

        $actualPageType = $this->registry->getPageType($pageType->getName());
        $this->assertSame($pageType, $actualPageType);
    }

    public function testGetPlaceholderException()
    {
        $unknownPageType = 'unknown';

        $this->setExpectedException(
            'Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException',
            sprintf('Page type "%s" does not exist.', $unknownPageType)
        );
        
        $this->registry->getPageType($unknownPageType);
    }

    public function testGetPageTypes()
    {
        $pageType1Name = 'test_type_1';
        $pageType2Name = 'test_type_2';

        /** @var PageTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType1 */
        $pageType1 = $this->getMock(PageTypeInterface::class);

        $pageType1->expects($this->any())
            ->method('getName')
            ->willReturn($pageType1Name);

        /** @var PageTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType2 */
        $pageType2 = $this->getMock(PageTypeInterface::class);

        $pageType2->expects($this->any())
            ->method('getName')
            ->willReturn($pageType2Name);

        $this->registry->addPageType($pageType1);
        $this->registry->addPageType($pageType2);

        $this->assertInternalType('array', $this->registry->getPageTypes());

        $this->assertEquals(
            [
                $pageType1Name => $pageType1,
                $pageType2Name => $pageType2
            ],
            $this->registry->getPageTypes()
        );
    }
}
