<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SlugPrototypesWithRedirect;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class ResolveNodeSlugsMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    protected $factory;
    
    protected function setUp()
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
    
    public function testCreateMessageCreateRedirectAlways()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ALWAYS);
        
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            ['id' => 1]
        );
        
        $message = $this->factory->createMessage($contentNode);
        $expectedMessage = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
        ];
        $this->assertEquals($expectedMessage, $message);
    }
    
    public function testCreateMessageCreateRedirectNever()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_NEVER);
        
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            ['id' => 1]
        );
        
        $message = $this->factory->createMessage($contentNode);
        $expectedMessage = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => false,
        ];
        $this->assertEquals($expectedMessage, $message);
    }
    
    public function testCreateMessageCreateRedirectAsk()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);
        
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            [
                'id' => 1,
                'slugPrototypesWithRedirect' => new SlugPrototypesWithRedirect(new ArrayCollection())
            ]
        );
        
        $message = $this->factory->createMessage($contentNode);
        $expectedMessage = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => false,
        ];
        $this->assertEquals($expectedMessage, $message);
    }

    public function testGetEntityFromMessage()
    {
        $data = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
        ];

        $repository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with($data[ResolveNodeSlugsMessageFactory::ID])
            ->willReturn(new ContentNode());
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->factory->getEntityFromMessage($data);
    }

    public function testGetCreateRedirectFromMessage()
    {
        $data = [
            ResolveNodeSlugsMessageFactory::ID => 1,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true,
        ];

        $createRedirect = $this->factory->getCreateRedirectFromMessage($data);
        $this->assertTrue($createRedirect);
    }
}
