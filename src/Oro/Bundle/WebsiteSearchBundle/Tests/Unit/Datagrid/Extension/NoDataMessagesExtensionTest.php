<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Datagrid\Extension\NoDataMessagesExtension;

class NoDataMessagesExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractExtension|\PHPUnit\Framework\MockObject\MockObject
     */
    private $noDataMessagesExtension;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchMappingProvider;

    /**
     * @var NoDataMessagesExtension|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extension;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->noDataMessagesExtension = $this->createMock(AbstractExtension::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->extension = new NoDataMessagesExtension(
            $this->noDataMessagesExtension,
            $this->frontendHelper,
            $this->searchMappingProvider
        );
    }

    public function testProcessConfigsSearchSource(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => SearchDatasource::TYPE,
                'query' => [
                    'select' => ['entity.id'],
                    'from' => ['alias']
                ]
            ]
        ]);

        $expected = [
            'entityHint' => 'stdclass.entity_plural_label',
        ];

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->searchMappingProvider->expects($this->once())
            ->method('getEntityClass')
            ->with('alias')
            ->willReturn(\stdClass::class);

        $this->noDataMessagesExtension->expects($this->once())
            ->method('processConfigs');

        $this->extension->processConfigs($config);

        $this->assertEquals($expected, $config->offsetGetByPath('options'));
    }

    public function testProcessConfigsNotFrontendRequest(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => SearchDatasource::TYPE,
                'query' => [
                    'select' => ['entity.id'],
                    'from' => ['alias']
                ]
            ]
        ]);

        $this->searchMappingProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->processConfigs($config);

        $this->assertNull($config->offsetGetByPath('options'));
    }

    public function testProcessConfigsNotSearchSource(): void
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type' => SearchDatasource::TYPE,
                'query' => [
                    'select' => ['entity.id'],
                    'from' => ['alias']
                ]
            ]
        ]);

        $this->searchMappingProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->processConfigs($config);

        $this->assertNull($config->offsetGetByPath('options'));
    }
}
