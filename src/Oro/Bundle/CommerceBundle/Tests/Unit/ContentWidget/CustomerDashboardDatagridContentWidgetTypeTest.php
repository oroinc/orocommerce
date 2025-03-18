<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CommerceBundle\ContentWidget\CustomerDashboardDatagridContentWidgetType;
use Oro\Bundle\CommerceBundle\ContentWidget\Provider\CustomerDashboardDatagridsProvider;
use Oro\Bundle\CommerceBundle\Form\Type\CustomerDashboardDatagridSelectType;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class CustomerDashboardDatagridContentWidgetTypeTest extends FormIntegrationTestCase
{
    private CustomerDashboardDatagridsProvider&MockObject $customerDashboardDatagridsProvider;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ManagerInterface&MockObject $manager;
    private FrontendHelper&MockObject $frontendHelper;

    private CustomerDashboardDatagridContentWidgetType $widgetType;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerDashboardDatagridsProvider = $this->createMock(CustomerDashboardDatagridsProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->widgetType = new CustomerDashboardDatagridContentWidgetType(
            $this->authorizationChecker,
            $this->manager,
            $this->frontendHelper
        );

        parent::setUp();
    }

    public function testGetName(): void
    {
        self::assertSame('customer_dashboard_datagrid', $this->widgetType::getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame(
            'oro.commerce.content_widget_type.customer_dashboard_datagrid.label',
            $this->widgetType->getLabel()
        );
    }

    public function testIsInline(): void
    {
        self::assertFalse($this->widgetType->isInline());
    }

    public function testGetDefaultTemplate(): void
    {
        self::assertSame(
            '',
            $this->widgetType->getDefaultTemplate(new ContentWidget(), $this->createMock(Environment::class))
        );
    }

    public function testGetWidgetData(): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['datagrid' => 'test-grid', 'viewAll' => 'oro_test_route']);

        $this->manager->expects(self::never())
            ->method('getConfigurationForGrid');

        self::assertSame(
            [
                'datagrid' => 'test-grid',
                'viewAll' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'pageComponentName' => 'test-grid:1'
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoLabel(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['datagrid' => 'test-grid', 'viewAll' => 'oro_test_route']);

        $this->manager->expects(self::never())
            ->method('getConfigurationForGrid');

        self::assertSame(
            [
                'datagrid' => 'test-grid',
                'viewAll' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'pageComponentName' => 'test-grid:1'
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataFrontendRequestWithoutRootEntity(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['datagrid' => 'test-grid', 'viewAll' => 'oro_test_route']);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->manager->expects(self::once())
            ->method('getConfigurationForGrid')
            ->with('test-grid')
            ->willReturn(DatagridConfiguration::create([]));

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            [
                'datagrid' => 'test-grid',
                'viewAll' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'pageComponentName' => 'test-grid:1',
                'visible' => false
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataFrontendRequestWithRootEntity(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['datagrid' => 'test-grid', 'viewAll' => 'oro_test_route']);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $config = $this->createMock(DatagridConfiguration::class);
        $ormQuery = $this->createMock(OrmQueryConfiguration::class);
        $rootEntity = Order::class;

        $config->expects(self::once())
            ->method('getOrmQuery')
            ->willReturn($ormQuery);

        $ormQuery->expects(self::once())
            ->method('getRootEntity')
            ->willReturn($rootEntity);

        $this->manager->expects(self::once())
            ->method('getConfigurationForGrid')
            ->with('test-grid')
            ->willReturn($config);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, new $rootEntity())
            ->willReturn(true);

        self::assertSame(
            [
                'datagrid' => 'test-grid',
                'viewAll' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'pageComponentName' => 'test-grid:1',
                'visible' => true
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetBackOfficeViewSubBlocks(): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['datagrid' => 'test-grid', 'viewAll' => 'oro_test_route']);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@OroCommerce/CustomerDashboardDatagridContentWidget/options.html.twig',
                [
                    'datagrid' => 'test-grid',
                    'viewAll' => 'oro_test_route',
                    'defaultLabel' => $contentWidget->getDefaultLabel(),
                    'labels' => $contentWidget->getLabels(),
                    'pageComponentName' => 'test-grid:1'
                ],
            )
            ->willReturn('rendered settings template');

        self::assertSame(
            [
                [
                    'title' => 'oro.cms.contentwidget.sections.options',
                    'subblocks' => [['data' => ['rendered settings template']]]
                ],
            ],
            $this->widgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    RouteChoiceType::class => new RouteChoiceTypeStub([
                        'some_route' => 'some_route',
                        'other_route' => 'other_route'
                    ]),
                    CustomerDashboardDatagridSelectType::class => new CustomerDashboardDatagridSelectType(
                        $this->customerDashboardDatagridsProvider
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
            ]
        );
    }
}
