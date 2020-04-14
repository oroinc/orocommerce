<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class ResolveNodeSlugsMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    protected $factory;

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
    public function testCreateMessageCreateRedirectAlways($strategy, $nodeParams, $expectedMessage)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            $nodeParams
        );

        $message = $this->factory->createMessage($contentNode);
        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @return array
     */
    public function createMessageCreateRedirectProvider()
    {
        return [
            'strategy always' => [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'nodeParams' => [
                    'id' => 1,
                ],
                'expectedMessage' => [
                    ResolveNodeSlugsMessageFactory::ID => 1,
                    ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
                ],
            ],
            'strategy never' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'nodeParams' => [
                    'id' => 1,
                ],
                'expectedMessage' => [
                    ResolveNodeSlugsMessageFactory::ID => 1,
                    ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => false,
                ],
            ],
            'strategy ask true' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'nodeParams' => [
                    'id' => 1,
                    'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection())
                ],
                'expectedMessage' => [
                    ResolveNodeSlugsMessageFactory::ID => 1,
                    ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
                ],
            ],
            'strategy ask false' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'nodeParams' => [
                    'id' => 1,
                    'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection(), false)
                ],
                'expectedMessage' => [
                    ResolveNodeSlugsMessageFactory::ID => 1,
                    ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => false,
                ],
            ],
        ];
    }

    public function testGetEntityFromMessage()
    {
        $data = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
        ];

        $expectedContentNode = new ContentNode();
        $repository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with($data[ResolveNodeSlugsMessageFactory::ID])
            ->willReturn($expectedContentNode);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $contentNode = $this->factory->getEntityFromMessage($data);
        $this->assertEquals($expectedContentNode, $contentNode);
    }

    /**
     * @dataProvider getCreateRedirectFormMessageProvider
     * @param bool $createRedirect
     */
    public function testGetCreateRedirectFromMessage($createRedirect)
    {
        $data = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => $createRedirect,
        ];

        $this->assertEquals($createRedirect, $this->factory->getCreateRedirectFromMessage($data));
    }

    /**
     * @return array
     */
    public function getCreateRedirectFormMessageProvider()
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
