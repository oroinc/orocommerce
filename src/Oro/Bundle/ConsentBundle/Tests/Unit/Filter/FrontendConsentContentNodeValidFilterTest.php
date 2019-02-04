<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class FrontendConsentContentNodeValidFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ConsentContentNodeValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeValidator;

    /**
     * @var FrontendConsentContentNodeValidFilter
     */
    private $filter;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webCatalogProvider;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->contentNodeValidator = $this->createMock(ConsentContentNodeValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $this->filter = new FrontendConsentContentNodeValidFilter(
            $this->webCatalogProvider,
            $this->logger,
            $this->websiteManager,
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->consentAcceptanceProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->logger,
            $this->webCatalogProvider,
            $this->websiteManager,
            $this->filter
        );
    }

    public function testIsConsentPassedFilterNoContentNode()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 999]);

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->consentAcceptanceProvider->expects($this->never())
            ->method('getCustomerConsentAcceptanceByConsentId');

        $this->webCatalogProvider->expects($this->never())
            ->method('getWebCatalog');

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->logger->expects($this->never())
            ->method('error');

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterBackendRequest()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->consentAcceptanceProvider->expects($this->never())
            ->method('getCustomerConsentAcceptanceByConsentId');

        $this->webCatalogProvider->expects($this->never())
            ->method('getWebCatalog');

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->logger->expects($this->never())
            ->method('error');

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterConsentAcceptanceExists()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn($consentAcceptance);

        $this->webCatalogProvider->expects($this->never())
            ->method('getWebCatalog');

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->logger->expects($this->never())
            ->method('error');

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterNoWebCatalog()
    {
        $contentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $notContentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);

        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    ['webCatalog' => $contentNodeWebCatalog]
                )
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn(null);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($notContentNodeWebCatalog);

        $currentWebsite = $this->getEntity(Website::class, ['id' => 1]);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                "Consent with id '999' point to the WebCatalog that doesn't use in the website with id '1'!"
            );

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertFalse($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterInvalidContentNode()
    {
        $contentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    ['webCatalog' => $contentNodeWebCatalog]
                )
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn(null);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($contentNodeWebCatalog);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->logger->expects($this->never())
            ->method('error');

        $this->contentNodeValidator->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->assertFalse($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterValidContentNode()
    {
        $contentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    ['webCatalog' => $contentNodeWebCatalog]
                )
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn(null);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($contentNodeWebCatalog);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');

        $this->logger->expects($this->never())
            ->method('error');

        $this->contentNodeValidator->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendConsentContentNodeValidFilter::NAME, $this->filter->getName());
    }
}
