<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class CmsPageHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeResolver;

    /** @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentContextProvider;

    /** @var CmsPageHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->consentContextProvider = $this->createMock(ConsentContextProvider::class);

        $this->helper = new CmsPageHelper(
            $this->contentNodeTreeResolver,
            $this->consentContextProvider
        );
    }

    public function testGetCmsPageConsentAcceptanceSetCmsPageNotSet()
    {
        $consent = new Consent();
        $consentAcceptance = new ConsentAcceptance();

        $this->assertNull($this->helper->getCmsPage($consent, $consentAcceptance));
    }

    public function testGetCmsPageConsentAcceptanceSetCmsPageSet()
    {
        $consent = new Consent();
        $cmsPage = new Page();

        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, [
            'id' => 13,
            'landingPage' => $cmsPage,
        ]);

        $this->assertSame(
            $cmsPage,
            $this->helper->getCmsPage($consent, $consentAcceptance)
        );
    }

    public function testGetCmsPageConsentAcceptanceNotSetNoContentNode()
    {
        $consent = new Consent();
        $consent->setContentNode(null);

        $this->assertNull($this->helper->getCmsPage($consent));
    }

    public function testGetCmsPageConsentAcceptanceNotSetNoScope()
    {
        $consent = new Consent();
        $contentNode = new ContentNode();

        $consent->setContentNode($contentNode);

        $this->consentContextProvider->expects($this->once())
            ->method('getScope')
            ->willReturn(null);

        $this->assertNull($this->helper->getCmsPage($consent));
    }

    public function testGetCmsPageConsentAcceptanceNotSetNoResolvedNode()
    {
        $consent = new Consent();
        $contentNode = new ContentNode();
        $scope = new Scope();

        $consent->setContentNode($contentNode);

        $this->consentContextProvider->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);

        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scope)
            ->willReturn(null);

        $this->assertNull($this->helper->getCmsPage($consent));
    }

    public function testGetCmsPageConsentAcceptanceNotSetInvalidResolvedVariant()
    {
        $consent = new Consent();
        $contentNode = new ContentNode();
        $scope = new Scope();
        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $resolvedContentVariant = $this->createMock(ResolvedContentVariant::class);

        $consent->setContentNode($contentNode);

        $this->consentContextProvider->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);

        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scope)
            ->willReturn($resolvedContentNode);

        $resolvedContentNode->expects($this->once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $resolvedContentVariant->expects($this->once())
            ->method('getType')
            ->willReturn(null);

        $this->assertNull($this->helper->getCmsPage($consent));
    }

    public function testGetCmsPageConsentAcceptanceNotSetValidResolvedVariant()
    {
        $consent = new Consent();
        $contentNode = new ContentNode();
        $scope = new Scope();
        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $resolvedContentVariant = $this->createMock(ResolvedContentVariant::class);
        $cmsPage = new Page();

        $consent->setContentNode($contentNode);

        $this->consentContextProvider->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);

        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scope)
            ->willReturn($resolvedContentNode);

        $resolvedContentNode->expects($this->once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $resolvedContentVariant->expects($this->once())
            ->method('getType')
            ->willReturn(CmsPageContentVariantType::TYPE);

        $resolvedContentVariant->expects($this->once())
            ->method('__get')
            ->willReturn($cmsPage);

        $this->assertSame(
            $cmsPage,
            $this->helper->getCmsPage($consent)
        );
    }
}
