<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Actions;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Actions\GetNodeDefaultVariantUrl;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetNodeDefaultVariantUrlTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var GetNodeDefaultVariantUrl
     */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->action = new GetNodeDefaultVariantUrl(
            $this->contextAccessor,
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->registry
        );

        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testExecuteException(): void
    {
        $context = new ActionData([
            'content_node' => null
        ]);

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage('Content node is empty');

        $this->action->initialize([
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ]);

        $this->action->execute($context);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        string $slugUrl,
        string $absoluteUrl,
        array $contentVariants
    ): void {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1, 'organization' => $organization]);
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 123, 'webCatalog' => $webCatalog]);
        foreach ($contentVariants as $contentVariant) {
            $contentNode->addContentVariant($contentVariant);
        }

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->mockRegistry([$website], $organization);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalog->getId());

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with($slugUrl)
            ->willReturn($absoluteUrl);

        $context = new ActionData([
            'content_node' => $contentNode
        ]);

        $this->action->initialize([
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ]);
        $this->action->execute($context);

        $expectedResult = [
            'targetUrl' => $absoluteUrl,
            'website' => $website,
            'organization' => $organization
        ];
        $this->assertSame($expectedResult, $context->get('attribute'));
    }

    public function executeDataProvider(): array
    {
        $slugUrl = '/test-url';
        $absoluteUrl = 'http://test.com/test-url';

        $localizedSlug = new Slug();
        $localizedSlug->setUrl($slugUrl);

        return [
            'without default variant slug' => [
                'slugUrl' => $slugUrl,
                'absoluteUrl' => $absoluteUrl,
                'contentVariants' => [
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->setDefault(true),
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->addSlug($localizedSlug)
                        ->setDefault(false)
                ]
            ],
            'with default variant slug' => [
                'slugUrl' => $slugUrl,
                'absoluteUrl' => $absoluteUrl,
                'contentVariants' => [
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->addSlug($localizedSlug)
                        ->setDefault(true)
                ]
            ],
        ];
    }

    public function testExecuteNoWebsitesException(): void
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1, 'organization' => $organization]);
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 123, 'webCatalog' => $webCatalog]);

        $this->mockRegistry([], $organization);

        $context = new ActionData([
            'content_node' => $contentNode
        ]);

        $this->action->initialize([
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ]);

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage('There must be at least one website.');

        $this->action->execute($context);
    }

    private function mockRegistry(array $websites, Organization $organization): void
    {
        $repository = $this->createMock(WebsiteRepository::class);
        $repository->expects($this->once())
            ->method('getAllWebsites')
            ->with($organization)
            ->willReturn($websites);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($entityManager);
    }

    public function testInitializeException(): void
    {
        $this->expectExceptionMessage('The required options "attribute", "content_node" are missing.');
        $this->action->initialize([]);
    }

    public function testInitialize(): void
    {
        $options = [
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
    }
}
