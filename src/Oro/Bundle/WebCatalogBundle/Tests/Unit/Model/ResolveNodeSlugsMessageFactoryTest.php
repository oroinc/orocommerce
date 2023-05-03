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

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ResolveNodeSlugsMessageFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->factory = new ResolveNodeSlugsMessageFactory(
            $this->doctrineHelper,
            $this->configManager
        );
    }

    /**
     * @dataProvider createMessageCreateRedirectProvider
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

        $contentNode = $this->getEntity(ContentNode::class, $nodeParams);

        $message = $this->factory->createMessage($contentNode);
        self::assertEquals($expectedMessage, $message);
    }

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
        $repository = $this->createMock(ContentNodeRepository::class);
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
     */
    public function testGetCreateRedirectFromMessage(bool $createRedirect): void
    {
        $data = [
            WebCatalogResolveContentNodeSlugsTopic::ID => 1,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => $createRedirect,
        ];

        self::assertEquals($createRedirect, $this->factory->getCreateRedirectFromMessage($data));
    }

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
