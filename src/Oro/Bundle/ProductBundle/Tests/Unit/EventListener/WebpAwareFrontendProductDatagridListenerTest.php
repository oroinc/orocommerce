<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\EventListener\WebpAwareFrontendProductDatagridListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\UIBundle\Tools\UrlHelper;

class WebpAwareFrontendProductDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    private DataGridThemeHelper|\PHPUnit\Framework\MockObject\MockObject $themeHelper;

    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePlaceholderProvider;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpAwareFrontendProductDatagridListener $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->themeHelper = $this->createMock(DataGridThemeHelper::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects(self::any())
            ->method('getAbsolutePath')
            ->willReturnCallback(static fn (string $path) => '/absolute' . $path);

        $this->listener = new WebpAwareFrontendProductDatagridListener(
            $this->themeHelper,
            $this->imagePlaceholderProvider,
            $this->webpConfiguration,
            $urlHelper
        );
    }

    /**
     * @dataProvider getOnPreBuildDataProvider
     */
    public function testOnPreBuild(
        string $gridName,
        bool $enabledIfSupported,
        array $expectedConfig
    ): void {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn($enabledIfSupported);

        $config = DatagridConfiguration::createNamed($gridName, []);
        $params = new ParameterBag();
        $event  = new PreBuild($config, $params);

        $this->listener->onPreBuild($event);

        self::assertEquals($expectedConfig, $config->toArray());
    }

    public function getOnPreBuildDataProvider(): array
    {
        $gridName = 'grid-name';

        return [
            'webp strategy not enabledIfSupported' => [
                'gridName' => $gridName,
                'enabledIfSupported' => false,
                'expectedConfig' => [
                    'name' => $gridName,
                ]
            ],
            'webp strategy is enabledIfSupported' => [
                'gridName' => $gridName,
                'enabledIfSupported' => true,
                'expectedConfig' => [
                    'name' => $gridName,
                    'columns' => [
                        'imageWebp'=> ['label' => 'oro.product.webp_image.label'],
                    ],
                ]
            ],
        ];
    }

    public function testOnResultAfterWebpStrategyNotEnabledIfSupported(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(false);

        $event = $this->createMock(SearchResultAfter::class);
        $event
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->themeHelper
            ->expects(self::any())
            ->method('getTheme');

        $this->listener->onResultAfter($event);

        self::assertEquals([], $event->getRecords());
    }

    public function testOnResultAfter(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        $records = [
            new ResultRecord(['id' => 2]),
            new ResultRecord(
                [
                    'id'  => 3,
                    'image_product_medium_webp'  => '/image/3/medium/webp'
                ]
            )
        ];

        $event = $this->createMock(SearchResultAfter::class);
        $event->expects(self::once())
            ->method('getRecords')
            ->willReturn($records);

        $gridName = 'grid-name';
        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper
            ->expects(self::any())
            ->method('getTheme')
            ->willReturn(DataGridThemeHelper::VIEW_TILES);

        $noImageWebp = '/path/no_image.jpg.webp';
        $this->imagePlaceholderProvider
            ->expects(self::once())
            ->method('getPath')
            ->with('product_medium', 'webp')
            ->willReturn($noImageWebp);

        $event->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->listener->onResultAfter($event);

        $expectedData = [
            [
                'id'        => 2,
                'imageWebp' => $noImageWebp,
            ],
            [
                'id'        => 3,
                'imageWebp' => '/absolute/image/3/medium/webp',
            ],
        ];
        foreach ($expectedData as $expectedRecord) {
            $record = current($records);
            self::assertEquals($expectedRecord['id'], $record->getValue('id'));
            self::assertEquals($expectedRecord['imageWebp'], $record->getValue('imageWebp'));
            next($records);
        }
    }
}
