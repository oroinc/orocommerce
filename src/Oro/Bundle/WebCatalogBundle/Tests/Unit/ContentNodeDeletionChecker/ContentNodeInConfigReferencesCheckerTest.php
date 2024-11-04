<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeDeletionChecker;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker\ContentNodeInConfigReferencesChecker;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentNodeInConfigReferencesCheckerTest extends \PHPUnit\Framework\TestCase
{
    private ContentNodeInConfigReferencesChecker $checker;

    private TranslatorInterface $translator;

    private ConfigManager $configManager;

    private WebsiteProviderInterface $websiteProvider;

    private ContentNode $contentNode;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->contentNode = $this->createMock(ContentNode::class);

        $this->websiteProvider
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn([]);

        $this->contentNode
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->checker = new ContentNodeInConfigReferencesChecker(
            $this->translator,
            $this->configManager,
            $this->websiteProvider
        );
    }

    public function testWarningMessageParamsPassed()
    {
        $this->configManager
            ->expects($this->once())
            ->method('getValues')
            ->with(
                $this->equalTo(Configuration::ROOT_NODE . '.' . Configuration::NAVIGATION_ROOT),
                $this->equalTo([]),
                $this->equalTo(false),
                $this->equalTo(true),
            )
            ->willReturn([
                [
                    'value' => 1
                ]
            ]);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oro.webcatalog.system_configuration.label'));

        $this->assertInstanceOf(
            NotDeletableContentNodeResult::class,
            $this->checker->check($this->contentNode)
        );
    }

    public function testThatEmptyWarningMessageParamsReturns()
    {
        $this->configManager
            ->expects($this->once())
            ->method('getValues')
            ->with(
                $this->equalTo(Configuration::ROOT_NODE . '.' . Configuration::NAVIGATION_ROOT),
                $this->equalTo([]),
                $this->equalTo(false),
                $this->equalTo(true),
            )
            ->willReturn([
                [
                    'value' => 2
                ]
            ]);

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->assertNull($this->checker->check($this->contentNode));
    }
}
