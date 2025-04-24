<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardInterface;
use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardsRegistryInterface;
use Oro\Bundle\CommerceBundle\ContentWidget\ScorecardContentWidgetType;
use Oro\Bundle\CommerceBundle\Form\Type\ScorecardSelectType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

final class ScorecardContentWidgetTypeTest extends FormIntegrationTestCase
{
    private ScorecardsRegistryInterface&MockObject $scorecardsRegistry;

    private ScorecardContentWidgetType $widgetType;

    #[\Override]
    protected function setUp(): void
    {
        $this->scorecardsRegistry = $this->createMock(ScorecardsRegistryInterface::class);

        $this->widgetType = new ScorecardContentWidgetType($this->scorecardsRegistry);

        parent::setUp();
    }

    public function testGetName(): void
    {
        self::assertSame('scorecard', $this->widgetType::getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame('oro.commerce.content_widget_type.scorecard.label', $this->widgetType->getLabel());
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
        $scorecard = $this->createMock(ScorecardInterface::class);
        $scorecard->expects(self::once())
            ->method('getData')
            ->willReturn('2');

        $scorecard->expects(self::once())
            ->method('isVisible')
            ->willReturn(true);

        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['scorecard' => 'test-scorecard', 'link' => 'oro_test_route']);

        $this->scorecardsRegistry->expects(self::once())
            ->method('getProviderByName')
            ->with('test-scorecard')
            ->willReturn($scorecard);

        self::assertSame(
            [
                'scorecard' => 'test-scorecard',
                'link' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'visible' => true,
                'metric' => ['data' => '2']
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoProvider(): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['scorecard' => 'test-scorecard', 'link' => 'oro_test_route']);

        $this->scorecardsRegistry->expects(self::once())
            ->method('getProviderByName')
            ->with('test-scorecard')
            ->willReturn(null);

        self::assertSame(
            [
                'scorecard' => 'test-scorecard',
                'link' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'visible' => false,
                'metric' => null
            ],
            $this->widgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoProviderData(): void
    {
        $scorecard = $this->createMock(ScorecardInterface::class);
        $scorecard->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $scorecard->expects(self::once())
            ->method('isVisible')
            ->willReturn(true);

        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['scorecard' => 'test-scorecard', 'link' => 'oro_test_route']);

        $this->scorecardsRegistry->expects(self::once())
            ->method('getProviderByName')
            ->with('test-scorecard')
            ->willReturn($scorecard);

        self::assertSame(
            [
                'scorecard' => 'test-scorecard',
                'link' => 'oro_test_route',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
                'visible' => false,
                'metric' => ['data' => null]
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
        $contentWidget->setSettings(['scorecard' => 'test-scorecard', 'link' => 'oro_test_route']);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@OroCommerce/ScorecardContentWidget/options.html.twig',
                [
                    'scorecard' => 'test-scorecard',
                    'link' => 'oro_test_route',
                    'defaultLabel' => $contentWidget->getDefaultLabel(),
                    'labels' => $contentWidget->getLabels(),
                    'visible' => false,
                    'metric' => null
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
                    ScorecardSelectType::class => new ScorecardSelectType($this->scorecardsRegistry)
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
