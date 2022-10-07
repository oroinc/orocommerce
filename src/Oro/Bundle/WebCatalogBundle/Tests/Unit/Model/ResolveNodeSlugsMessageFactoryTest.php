<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class ResolveNodeSlugsMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ResolveNodeSlugsMessageFactory $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = new ResolveNodeSlugsMessageFactory(
            $this->doctrineHelper,
            $this->configManager
        );
    }

    /**
     * @dataProvider createMessageCreateRedirectProvider
     * @param string $strategy
     * @param array $nodeParams
     * @param array $expectedMessage
     */
    public function testCreateMessageCreateRedirectAlways(
        string $strategy,
        array $nodeParams,
        array $expectedMessage
    ): void {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            $nodeParams
        );

        $message = $this->factory->createMessage($contentNode);
        self::assertEquals($expectedMessage, $message);
    }

    /**
     * @return array
     */
    public function createMessageCreateRedirectProvider(): array
    {
        return [
            'strategy always' => [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'nodeParams' => [
                    'id' => 1,
                ],
                'expectedMessage' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 1,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
                ],
            ],
            'strategy never' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'nodeParams' => [
                    'id' => 1,
                ],
                'expectedMessage' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 1,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => false,
                ],
            ],
            'strategy ask true' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'nodeParams' => [
                    'id' => 1,
                    'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection()),
                ],
                'expectedMessage' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 1,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
                ],
            ],
            'strategy ask false' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'nodeParams' => [
                    'id' => 1,
                    'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection(), false),
                ],
                'expectedMessage' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 1,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => false,
                ],
            ],
        ];
    }

    public function testGetEntityFromMessage(): void
    {
        $data = [
            WebCatalogResolveContentNodeSlugsTopic::ID => 1,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
        ];

        $expectedContentNode = new ContentNode();
        $repository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('find')
            ->with($data[WebCatalogResolveContentNodeSlugsTopic::ID])
            ->willReturn($expectedContentNode);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $contentNode = $this->factory->getEntityFromMessage($data);
        self::assertEquals($expectedContentNode, $contentNode);
    }

    /**
     * @dataProvider getCreateRedirectFormMessageProvider
     * @param bool $createRedirect
     */
    public function testGetCreateRedirectFromMessage(bool $createRedirect): void
    {
        $data = [
            WebCatalogResolveContentNodeSlugsTopic::ID => 1,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => $createRedirect,
        ];

        self::assertEquals($createRedirect, $this->factory->getCreateRedirectFromMessage($data));
    }

    /**
     * @return array
     */
    public function getCreateRedirectFormMessageProvider(): array
    {
        return [
            'create true' => [
                'createRedirect' => true,
            ],
            'create false' => [
                'createRedirect' => false,
            ],
        ];
    }
}
