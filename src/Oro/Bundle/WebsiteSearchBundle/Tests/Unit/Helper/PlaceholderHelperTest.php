<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Helper;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderRegistry;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchMappingProvider;

    /** @var PlaceholderHelper */
    private PlaceholderHelper $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->placeholderRegistry = $this->createMock(PlaceholderRegistry::class);
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->helper = new PlaceholderHelper($this->placeholderRegistry);
        $this->helper->setSearchMappingProvider($this->searchMappingProvider);
    }

    /**
     * @dataProvider isNameMatchDataProvider
     *
     * @param string $name
     * @param string $nameValue
     * @param bool $expected
     */
    public function testIsNameMatch($name, $nameValue, $expected): void
    {
        $placeholder = $this->getMockBuilder(AbstractPlaceholder::class)->getMock();

        $placeholder
            ->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $this->placeholderRegistry
            ->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals($expected, $this->helper->isNameMatch($name, $nameValue));
    }

    /**
     * @return array
     */
    public function isNameMatchDataProvider(): array
    {
        return [
            'with placeholder' => [
                'name' => 'oro_test_WEBSITE_ID',
                'nameValue' => 'oro_test_1a',
                'expected' => true
            ]
        ];
    }

    public function testIsNameMatchWithoutPlaceholder(): void
    {
        $name = 'no placeholders';
        $this->placeholderRegistry
            ->expects($this->never())
            ->method('getPlaceholders');

        $this->assertFalse($this->helper->isNameMatch($name, ''));
    }

    /**
     * @dataProvider getEntityClassByResolvedIndexAliasDataProvider
     *
     * @param string $indexAlias
     * @param array $aliases
     * @param string $expected
     */
    public function testGetEntityClassByResolvedIndexAlias(string $indexAlias, array $aliases, string $expected): void
    {
        $this->searchMappingProvider
            ->expects($this->once())
            ->method('getEntitiesListAliases')
            ->willReturn($aliases);

        $placeholder = $this->getMockBuilder(AbstractPlaceholder::class)->getMock();

        $placeholder
            ->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn('WEBSITE_ID');

        $this->placeholderRegistry
            ->expects($this->any())
            ->method('getPlaceholders')
            ->willReturn([$placeholder]);

        $this->assertEquals($expected, $this->helper->getEntityClassByResolvedIndexAlias($indexAlias));
    }

    /**
     * @return array
     */
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
}
