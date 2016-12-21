<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\DataProvider\MenuDataProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var WebCatalogProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webCatalogProvider;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentNodeTreeResolverFacade;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var MenuDataProvider
     */
    protected $menuDataProvider;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->webCatalogProvider = $this->getMockBuilder(WebCatalogProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->contentNodeTreeResolverFacade = $this->getMock(ContentNodeTreeResolverInterface::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuDataProvider = new MenuDataProvider(
            $this->registry,
            $this->webCatalogProvider,
            $this->contentNodeTreeResolverFacade,
            $this->localizationHelper,
            $this->requestStack
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetItems()
    {
        $webCatalogId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $rootNode = new ContentNode();
        $scope = new Scope();

        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag(['_web_content_scope' => $scope]);
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $resolvedRootNodeVariant = new ResolvedContentVariant();
        $resolvedRootNodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString('/'));

        
        $childNodeVariant = new ResolvedContentVariant();
        $childNodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString('/node2'));

        $resolvedRootNodeTitle = 'node1';
        $resolvedRootNodeTitleCollection =  new ArrayCollection([(new LocalizedFallbackValue())
            ->setString($resolvedRootNodeTitle)]);
        $resolvedRootNode = new ResolvedContentNode(
            1,
            'root',
            $resolvedRootNodeTitleCollection,
            $resolvedRootNodeVariant
        );

        $childNodeTitle = 'node2';
        $childNodeTitlesCollection = new ArrayCollection([(new LocalizedFallbackValue())->setString($childNodeTitle)]);
        $childNode = new ResolvedContentNode(
            1,
            'root__node2',
            $childNodeTitlesCollection,
            $childNodeVariant
        );

        $resolvedRootNode->addChildNode($childNode);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($webCatalog);

        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeRepository->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->contentNodeTreeResolverFacade->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn($resolvedRootNode);

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->will($this->returnCallback(function (ArrayCollection $collection) {
                return $collection->first()->getString();
            }));

        $actual = $this->menuDataProvider->getItems();
        $this->assertEquals(
            [
                [
                    MenuDataProvider::IDENTIFIER => 'root',
                    MenuDataProvider::LABEL => 'node1',
                    MenuDataProvider::URL => '/',
                    MenuDataProvider::CHILDREN => [
                        [
                            MenuDataProvider::IDENTIFIER => 'root__node2',
                            MenuDataProvider::LABEL => 'node2',
                            MenuDataProvider::URL => '/node2',
                            MenuDataProvider::CHILDREN => []
                        ]
                    ]
                ]
            ],
            $actual
        );
    }
}
