<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Helper;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderRegistry;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchMappingProvider;

    /** @var PlaceholderHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->placeholderRegistry = $this->createMock(PlaceholderRegistry::class);
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->helper = new PlaceholderHelper(
            $this->placeholderRegistry,
            $this->searchMappingProvider
        );
    }

    /**
     * @dataProvider isNameMatchDataProvider
     */
    public function testIsNameMatch(string $name, string $nameValue, bool $expected): void
    {
        $placeholder = $this->createMock(AbstractPlaceholder::class);

        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $this->placeholderRegistry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals($expected, $this->helper->isNameMatch($name, $nameValue));
    }

    public function isNameMatchDataProvider(): array
    {
        return [
            'with placeholder' => [
                'name' => 'oro_test_WEBSITE_ID',
                'nameValue' => 'oro_test_1a',
                'expected' => true,
            ],
            [
                'name' => 'test_WEBSITE_ID',
                'nameValue' => 'oro_test_1a',
                'expected' => false,
            ],
            [
                'name' => 'oro_test_WEBSITE_ID',
                'nameValue' => 'test_1a',
                'expected' => false,
            ],
            [
                'name' => 'test_WEBSITE_ID',
                'nameValue' => 'test_oro_1a',
                'expected' => true, // Probably it is a bug
            ],
            [
                'name' => 'test_oro_WEBSITE_ID',
                'nameValue' => 'test_1a',
                'expected' => false,
            ],
            [
                'name' => 'test_oro_WEBSITE_ID_data',
                'nameValue' => 'test_oro_1a_data',
                'expected' => true,
            ],
            [
                'name' => 'test_oro_WEBSITE_ID_data',
                'nameValue' => 'test_oro_1a_data_2b',
                'expected' => true,
            ],
        ];
    }

    public function testIsNameMatchWithoutPlaceholder(): void
    {
        $name = 'no placeholders';
        $this->placeholderRegistry->expects($this->never())
            ->method('getPlaceholders');

        $this->assertFalse($this->helper->isNameMatch($name, ''));
    }

    /**
     * @dataProvider getEntityClassByResolvedIndexAliasDataProvider
     */
    public function testGetEntityClassByResolvedIndexAlias(string $indexAlias, array $aliases, string $expected): void
    {
        $this->searchMappingProvider->expects($this->once())
            ->method('getEntitiesListAliases')
            ->willReturn($aliases);

        $placeholder = $this->createMock(AbstractPlaceholder::class);
        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $this->placeholderRegistry->expects($this->any())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals($expected, $this->helper->getEntityClassByResolvedIndexAlias($indexAlias));
    }

    public function getEntityClassByResolvedIndexAliasDataProvider(): array
    {
        return [
            'empty alias' => [
                'indexAlias' => '',
                'aliases' => [],
                'expected' => ''
            ],
            'existing alias' => [
                'indexAlias' => 'std_class_2',
                'aliases' => [\stdClass::class => 'std_class_WEBSITE_ID'],
                'expected' => \stdClass::class
            ],
            'unknown alias' => [
                'indexAlias' => 'unknown_alias',
                'aliases' => [\stdClass::class => 'std_class_WEBSITE_ID'],
                'expected' => ''
            ],
            'empty aliases list' => [
                'indexAlias' => 'std_class_2',
                'aliases' => [],
                'expected' => ''
            ],
        ];
    }

    public function testGetPlaceholderKeys(): void
    {
        $placeholder = $this->createMock(PlaceholderInterface::class);
        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $this->placeholderRegistry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals(['WEBSITE_ID'], $this->helper->getPlaceholderKeys());
    }
}
