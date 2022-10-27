<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

class ContentVariantTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testAddPageType()
    {
        $pageTypeName = 'test_type';

        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType */
        $pageType = $this->createMock(ContentVariantTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn($pageTypeName);

        $registry = new ContentVariantTypeRegistry([$pageType]);

        $this->assertEquals([$pageTypeName => $pageType], $registry->getContentVariantTypes());
    }

    public function testGetPageType()
    {
        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType */
        $pageType = $this->createMock(ContentVariantTypeInterface::class);

        $pageType->expects($this->any())
            ->method('getName')
            ->willReturn('test_type');

        $registry = new ContentVariantTypeRegistry([$pageType]);

        $actualPageType = $registry->getContentVariantType($pageType->getName());
        $this->assertSame($pageType, $actualPageType);
    }

    public function testGetPlaceholderException()
    {
        $unknownPageType = 'unknown';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Content variant type "%s" is not known.', $unknownPageType));

        $registry = new ContentVariantTypeRegistry([]);
        $registry->getContentVariantType($unknownPageType);
    }

    public function testGetPageTypes()
    {
        $pageType1Name = 'test_type_1';
        $pageType2Name = 'test_type_2';

        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType1 */
        $pageType1 = $this->createMock(ContentVariantTypeInterface::class);

        $pageType1->expects($this->any())
            ->method('getName')
            ->willReturn($pageType1Name);

        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType2 */
        $pageType2 = $this->createMock(ContentVariantTypeInterface::class);

        $pageType2->expects($this->any())
            ->method('getName')
            ->willReturn($pageType2Name);

        $registry = new ContentVariantTypeRegistry([$pageType1, $pageType2]);

        $this->assertIsArray($registry->getContentVariantTypes());

        $this->assertEquals(
            [
                $pageType1Name => $pageType1,
                $pageType2Name => $pageType2
            ],
            $registry->getContentVariantTypes()
        );
    }

    public function testGetAllowedPageTypes()
    {
        $pageType1Name = 'test_type_1';
        $pageType2Name = 'test_type_2';

        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType1 */
        $pageType1 = $this->createMock(ContentVariantTypeInterface::class);

        $pageType1->expects($this->any())
            ->method('getName')
            ->willReturn($pageType1Name);
        $pageType1->expects($this->any())
            ->method('isAllowed')
            ->willReturn(true);

        /** @var ContentVariantTypeInterface|\PHPUnit\Framework\MockObject\MockObject $pageType2 */
        $pageType2 = $this->createMock(ContentVariantTypeInterface::class);

        $pageType2->expects($this->any())
            ->method('getName')
            ->willReturn($pageType2Name);
        $pageType2->expects($this->any())
            ->method('isAllowed')
            ->willReturn(false);

        $registry = new ContentVariantTypeRegistry([$pageType1, $pageType2]);

        $this->assertIsArray($registry->getAllowedContentVariantTypes());

        $this->assertEquals(
            [
                $pageType1Name => $pageType1
            ],
            $registry->getAllowedContentVariantTypes()
        );
    }

    public function testGetFormType()
    {
        $type1 = $this->createMock(ContentVariantTypeInterface::class);
        $type1->expects($this->any())
            ->method('getName')
            ->willReturn('type1');
        $type1->expects($this->never())
            ->method('getFormType');

        $type2 = $this->createMock(ContentVariantTypeInterface::class);
        $type2->expects($this->any())
            ->method('getName')
            ->willReturn('type2');
        $type2->expects($this->once())
            ->method('getFormType')
            ->willReturn('form.type');

        $registry = new ContentVariantTypeRegistry([$type1, $type2]);

        $this->assertEquals('form.type', $registry->getFormTypeByType('type2'));
    }
}
