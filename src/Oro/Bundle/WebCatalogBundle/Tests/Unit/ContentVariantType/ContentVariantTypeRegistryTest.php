<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ContentVariantTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantTypeRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new ContentVariantTypeRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testAddPageType()
    {
        $pageTypeName = 'test_type';

        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType */
        $pageType = $this->getMock(ContentVariantTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn($pageTypeName);

        $this->registry->addContentVariantType($pageType);

        $this->assertEquals([$pageTypeName => $pageType], $this->registry->getContentVariantTypes());
    }

    public function testGetPageType()
    {
        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType */
        $pageType = $this->getMock(ContentVariantTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn('test_type');

        $this->registry->addContentVariantType($pageType);

        $actualPageType = $this->registry->getContentVariantType($pageType->getName());
        $this->assertSame($pageType, $actualPageType);
    }

    public function testGetPlaceholderException()
    {
        $unknownPageType = 'unknown';

        $this->setExpectedException(
            InvalidArgumentException::class,
            sprintf('Content variant type "%s" is not known.', $unknownPageType)
        );
        
        $this->registry->getContentVariantType($unknownPageType);
    }

    public function testGetPageTypes()
    {
        $pageType1Name = 'test_type_1';
        $pageType2Name = 'test_type_2';

        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType1 */
        $pageType1 = $this->getMock(ContentVariantTypeInterface::class);

        $pageType1->expects($this->any())
            ->method('getName')
            ->willReturn($pageType1Name);

        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType2 */
        $pageType2 = $this->getMock(ContentVariantTypeInterface::class);

        $pageType2->expects($this->any())
            ->method('getName')
            ->willReturn($pageType2Name);

        $this->registry->addContentVariantType($pageType1);
        $this->registry->addContentVariantType($pageType2);

        $this->assertInternalType('array', $this->registry->getContentVariantTypes());

        $this->assertEquals(
            [
                $pageType1Name => $pageType1,
                $pageType2Name => $pageType2
            ],
            $this->registry->getContentVariantTypes()
        );
    }

    public function testGetAllowedPageTypes()
    {
        $pageType1Name = 'test_type_1';
        $pageType2Name = 'test_type_2';

        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType1 */
        $pageType1 = $this->getMock(ContentVariantTypeInterface::class);

        $pageType1->expects($this->any())
            ->method('getName')
            ->willReturn($pageType1Name);
        $pageType1->expects($this->any())
            ->method('isAllowed')
            ->willReturn(true);

        /** @var ContentVariantTypeInterface|\PHPUnit_Framework_MockObject_MockObject $pageType2 */
        $pageType2 = $this->getMock(ContentVariantTypeInterface::class);

        $pageType2->expects($this->any())
            ->method('getName')
            ->willReturn($pageType2Name);
        $pageType2->expects($this->any())
            ->method('isAllowed')
            ->willReturn(false);

        $this->registry->addContentVariantType($pageType1);
        $this->registry->addContentVariantType($pageType2);

        $this->assertInternalType('array', $this->registry->getAllowedContentVariantTypes());

        $this->assertEquals(
            [
                $pageType1Name => $pageType1
            ],
            $this->registry->getAllowedContentVariantTypes()
        );
    }

    public function testGetFormType()
    {
        $type1 = $this->getMock(ContentVariantTypeInterface::class);
        $type1->expects($this->any())
            ->method('getName')
            ->willReturn('type1');
        $type1->expects($this->never())
            ->method('getFormType');

        $type2 = $this->getMock(ContentVariantTypeInterface::class);
        $type2->expects($this->any())
            ->method('getName')
            ->willReturn('type2');
        $type2->expects($this->once())
            ->method('getFormType')
            ->willReturn('form.type');

        $this->registry->addContentVariantType($type1);
        $this->registry->addContentVariantType($type2);

        $this->assertEquals('form.type', $this->registry->getFormTypeByType('type2'));
    }
}
