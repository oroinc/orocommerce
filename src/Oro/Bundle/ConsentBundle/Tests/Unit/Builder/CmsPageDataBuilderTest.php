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
use Symfony\Component\Routing\RequestContext;
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

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var RoutingInformationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $routingInformationProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cmsPageHelper = $this->createMock(CmsPageHelper::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProviderInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->builder = new CmsPageDataBuilder(
            $this->cmsPageHelper,
            $this->localizationHelper,
            $this->routingInformationProvider,
            $this->router
        );
    }

    public function testBuildCmsPageNotExists()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 1]);

        $this->cmsPageHelper->expects($this->once())
            ->method('getCmsPage')
            ->with($consent, null)
            ->willReturn(null);

        $this->router
            ->expects($this->never())
            ->method('generate');

        $this->assertNull($this->builder->build($consent));
    }

    public function testBuildConceptAcceptanceSet()
    {
        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->willReturnCallback(function (Page $cmsPage) {
                return new RouteData('oro_cms_frontend_page_view', ['id' => $cmsPage->getId()]);
            });

        $consent = $this->getConsent();
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 42]);
        $consentAcceptanceCmsPageId = 15;
        $cmsPage = $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId]);
        $routerUrl = '/cms-page-url-from-content-acceptance';
        $expectedResult = (new CmsPageData())
            ->setId($consentAcceptanceCmsPageId)
            ->setUrl($routerUrl);

        $this->cmsPageHelper->expects($this->once())
            ->method('getCmsPage')
            ->with($consent, $consentAcceptance)
            ->willReturn($cmsPage);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_cms_frontend_page_view', ['id' => $expectedResult->getId()])
            ->willReturn($routerUrl);

        $result = $this->builder->build($consent, $consentAcceptance);
        $this->assertEquals($expectedResult, $result);
    }

    public function testBuildConceptAcceptanceNotSet()
    {
        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->will($this->returnCallback(function (Collection $collection) {
                return $collection->first();
            }));

        $consent = $this->getConsent();
        $consentAcceptanceCmsPageId = 15;
        $cmsPage = $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId]);

        $baseUrl = '/base-url';
        $expectedResult = (new CmsPageData())
            ->setId($consentAcceptanceCmsPageId)
            ->setUrl($baseUrl . '/cms-page-url-from-content-node');

        $this->cmsPageHelper->expects($this->once())
            ->method('getCmsPage')
            ->with($consent, null)
            ->willReturn($cmsPage);

        $requestContext = $this->createMock(RequestContext::class);
        $requestContext->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->router->expects($this->once())
            ->method('getContext')
            ->willReturn($requestContext);

        $result = $this->builder->build($consent);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return Consent
     */
    private function getConsent(): Consent
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('/cms-page-url-from-content-node');

        return $this->getEntity(
            Consent::class,
            [
                'id' => 1,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    [
                        'id' => 12,
                        'localizedUrls' => new ArrayCollection([$fallbackValue]),
                    ]
                )
            ]
        );
    }
}
