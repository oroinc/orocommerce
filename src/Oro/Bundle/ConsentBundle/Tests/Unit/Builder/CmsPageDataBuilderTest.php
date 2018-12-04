<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Builder\CmsPageDataBuilder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\RouterInterface;

class CmsPageDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CmsPageHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $cmsPageHelper;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var CmsPageDataBuilder */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cmsPageHelper = $this->createMock(CmsPageHelper::class);

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->will($this->returnCallback(function (Collection $collection) {
                return $collection->first()->getString();
            }));

        /**
         * @var RoutingInformationProviderInterface|\PHPUnit\Framework\MockObject\MockObject $routingInformationProvider
         */
        $routingInformationProvider = $this->createMock(RoutingInformationProviderInterface::class);
        $routingInformationProvider->expects($this->any())
            ->method('getRouteData')
            ->willReturnCallback(function (Page $cmsPage) {
                return new RouteData('oro_cms_frontend_page_view', ['id' => $cmsPage->getId()]);
            });

        $this->router = $this->createMock(RouterInterface::class);
        $this->builder = new CmsPageDataBuilder(
            $this->cmsPageHelper,
            $localizationHelper,
            $routingInformationProvider,
            $this->router
        );
    }

    /**
     * @dataProvider buildProvider
     *
     * @param Consent $consent
     * @param ConsentAcceptance|null $consentAcceptance
     * @param Page|null $cmsPage
     * @param string $routerUrl
     * @param CmsPageData|null $expectedResult
     */
    public function testBuild(
        Consent $consent,
        ConsentAcceptance $consentAcceptance = null,
        Page $cmsPage = null,
        $routerUrl,
        CmsPageData $expectedResult = null
    ) {
        $this->cmsPageHelper->expects($this->once())
            ->method('getCmsPage')
            ->with($consent, $consentAcceptance)
            ->willReturn($cmsPage);

        if (!$consentAcceptance || !$cmsPage) {
            $this->router
                ->expects($this->never())
                ->method('generate');
        } else {
            $this->router
                ->expects($this->once())
                ->method('generate')
                ->with('oro_cms_frontend_page_view', ['id' => $expectedResult->getId()])
                ->willReturn($routerUrl);
        }

        $result = $this->builder->build($consent, $consentAcceptance);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildProvider()
    {
        $consentAcceptanceCmsPageId = 15;
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('/cms-page-url-from-content-node');

        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 1,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    [
                        'id' => 12,
                        'localizedUrls' => new ArrayCollection([$fallbackValue]),
                    ]
                ),
            ]
        );

        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 42]);

        return [
            "Consent acceptance is set and cms page exists" => [
                'consent' => $consent,
                'consentAcceptance' => $consentAcceptance,
                'cmsPage' => $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId]),
                'routerUrl' => '/cms-page-url-from-content-acceptance',
                'expected' => (new CmsPageData())
                    ->setId($consentAcceptanceCmsPageId)
                    ->setUrl('/cms-page-url-from-content-acceptance')
            ],
            "Consent acceptance is set and cms page doesn't exist" => [
                'consent' => $consent,
                'consentAcceptance' => $consentAcceptance,
                'cmsPage' => null,
                'routerUrl' => '/cms-page-url-from-content-acceptance',
                'expected' => null
            ],
            "Consent acceptance isn't set and cms page exists" => [
                'consent' => $consent,
                'consentAcceptance' => null,
                'cmsPage' => $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId]),
                'routerUrl' => '/cms-page-url-from-content-acceptance',
                'expected' => (new CmsPageData())
                    ->setId($consentAcceptanceCmsPageId)
                    ->setUrl('/cms-page-url-from-content-node')
            ],
            "Consent acceptance isn't set and cms page doesn't exist" => [
                'consent' => $consent,
                'consentAcceptance' => null,
                'cmsPage' => null,
                'routerUrl' => '/cms-page-url-from-content-acceptance',
                'expected' => null
            ],
        ];
    }
}
