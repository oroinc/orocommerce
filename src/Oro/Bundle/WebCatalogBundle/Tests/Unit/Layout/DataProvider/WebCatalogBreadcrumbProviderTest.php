<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\WebCatalogBreadcrumbProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebCatalogBreadcrumbProviderTest extends \PHPUnit\Framework\TestCase
{
    const WEBCATALOG_ID = 1;
    const LASTNODE_ID   = 999;

    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogProvider;

    /** @var WebCatalog */
    private $webCatalog;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var RequestWebContentVariantProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $requestWebContentVariantProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var CategoryBreadcrumbProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryBreadcrumbProvider;

    /** @var WebCatalogBreadcrumbProvider */
    private $breadcrumbDataProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestWebContentVariantProvider = $this->createMock(RequestWebContentVariantProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->categoryBreadcrumbProvider = $this->createMock(CategoryBreadcrumbProvider::class);

        $this->breadcrumbDataProvider = new WebCatalogBreadcrumbProvider(
            $this->doctrine,
            $this->localizationHelper,
            $this->requestStack,
            $this->requestWebContentVariantProvider,
            $this->categoryBreadcrumbProvider
        );

        $this->webCatalog = $this->getEntity(WebCatalog::class, ['id' => self::WEBCATALOG_ID]);
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testGetItems(ContentNode $rootNode, array $expectedData)
    {
        $currentNode = $this->findLastNode($rootNode);

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($currentNode);

        $request = Request::create('/', Request::METHOD_GET);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $path = [];
        $this->cascadeToArray($rootNode, $path);
        $nodeRepository->expects($this->once())
            ->method('getPath')
            ->with($currentNode)
            ->willReturn($path);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

        $actual = $this->breadcrumbDataProvider->getItems();
        $this->assertCount($expectedData['crumbs'], $actual);
    }

    /**
     * @return array
     */
    public function getItemsDataProvider()
    {
        return [
            'two levels'   => [
                'rootNode'     => $this->getContentNode(
                    1,
                    'root',
                    'node1',
                    '/',
                    [
                        $this->getContentNode(
                            self::LASTNODE_ID,
                            'root__node2',
                            'node2',
                            '/node2'
                        )
                    ]
                ),
                'expectedData' => [
                    'crumbs' => 2
                ]
            ],
            'three levels' => [
                'rootNode'     => $this->getContentNode(
                    1,
                    'root',
                    'node1',
                    '/',
                    [
                        $this->getContentNode(
                            2,
                            'root__node2',
                            'node2',
                            '/node2',
                            [
                                $this->getContentNode(
                                    self::LASTNODE_ID,
                                    'root__node2__node3',
                                    'node3',
                                    '/node3'
                                )
                            ]
                        )
                    ]
                ),
                'expectedData' => [
                    'crumbs' => 3
                ]
            ]
        ];
    }

    public function testGetItemsWithoutContentVariant()
    {
        $categoryId     = 2;
        $request        = Request::create('/', Request::METHOD_GET);
        $request->query = new ParameterBag([
            'categoryId' => $categoryId
        ]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $expectedBreadcrumbs = [
            [
                'label' => 'Main category',
                'url' => '/'
            ],
            [
                'label' => 'Sub category',
                'url' => '/sub-category'
            ]
        ];
        $this->categoryBreadcrumbProvider
            ->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedBreadcrumbs);
        $result = $this->breadcrumbDataProvider->getItems();
        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsWithoutContentVariantAndCategory()
    {
        $request        = Request::create('/', Request::METHOD_GET);
        $request->query = new ParameterBag();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $result = $this->breadcrumbDataProvider->getItems();
        $this->assertEquals([], $result);
    }

    public function testGetItemsForProductWithoutRequest()
    {
        $result = $this->breadcrumbDataProvider->getItemsForProduct(1, '220 Lumen Rechargeable Headlamp');
        $this->assertEquals([], $result);
    }

    public function testGetItemsForProductWithContentVariant()
    {
        $nodeTitle      = 'node1';
        $nodeUrl        = '/';
        $currentNode    = $this->getContentNode(1, 'root', $nodeTitle, $nodeUrl);
        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($currentNode);

        $request = Request::create('/', Request::METHOD_GET);

        $this->requestStack->expects($this->exactly(2))
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestWebContentVariantProvider->expects($this->any())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $path = [];
        $this->cascadeToArray($currentNode, $path);
        $nodeRepository->expects($this->once())
            ->method('getPath')
            ->with($currentNode)
            ->willReturn($path);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

        $this->localizationHelper
            ->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$currentNode->getTitles()],
                [$currentNode->getLocalizedUrls()]
            )
            ->willReturnOnConsecutiveCalls($nodeTitle, $nodeUrl);

        $currentPageTitle    = '220 Lumen Rechargeable Headlamp';
        $categoryId          = 1;
        $result              = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
        $expectedBreadcrumbs = [
            [
                'label' => $nodeTitle,
                'url' => $nodeUrl
            ]
        ];
        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsForProductWithoutContentVariant()
    {
        $nodeTitle      = 'node1';
        $nodeUrl        = '/';
        $currentNode    = $this->getContentNode(1, 'root', $nodeTitle, $nodeUrl);
        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($currentNode);

        $slug                = new Slug();
        $request             = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag([
            '_context_url_attributes' => [
                [
                    '_used_slug' => $slug
                ]
            ]
        ]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $variantRepository = $this->getMockBuilder(ContentVariantRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $path = [];
        $this->cascadeToArray($currentNode, $path);
        $variantRepository->expects($this->once())
            ->method('findVariantBySlug')
            ->with($slug)
            ->willReturn($contentVariant);
        $nodeRepository->expects($this->once())
            ->method('getPath')
            ->with($currentNode)
            ->willReturn($path);

        $this->doctrine->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [ContentVariant::class],
                [ContentNode::class]
            )
            ->willReturnOnConsecutiveCalls($variantRepository, $nodeRepository);

        $this->localizationHelper
            ->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$currentNode->getTitles()],
                [$currentNode->getLocalizedUrls()]
            )
            ->willReturnOnConsecutiveCalls($nodeTitle, $nodeUrl);

        $currentPageTitle    = '220 Lumen Rechargeable Headlamp';
        $categoryId          = 1;
        $result              = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
        $expectedBreadcrumbs = [
            [
                'label' => $nodeTitle,
                'url' => $nodeUrl
            ],
            [
                'label' => $currentPageTitle,
                'url' => null

            ]
        ];

        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsForProductWithoutContextAttributes()
    {
        $categoryId       = 2;
        $currentPageTitle = '220 Lumen Rechargeable Headlamp';
        $request          = Request::create('/', Request::METHOD_GET);
        $request->query   = new ParameterBag([
           'categoryId' => $categoryId
        ]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $expectedBreadcrumbs = [
            [
                'label' => 'Main category',
                'url' => '/'
            ],
            [
                'label' => 'Sub category',
                'url' => '/sub-category'
            ],
            [
                'label' => $currentPageTitle,
                'url' => null
            ]
        ];
        $this->categoryBreadcrumbProvider
            ->expects($this->once())
            ->method('getItemsForProduct')
            ->with($categoryId, $currentPageTitle)
            ->willReturn($expectedBreadcrumbs);

        $result = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    /**
     * @param string        $id
     * @param string        $identifier
     * @param string        $title
     * @param string        $url
     * @param ContentNode[] $children
     *
     * @return ContentNode
     */
    private function getContentNode($id, $identifier, $title, $url, array $children = [])
    {
        $resolvedNodeVariant = new ResolvedContentVariant();
        $resolvedNodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString($url));

        $nodeTitleCollection = new ArrayCollection(
            [
                (new LocalizedFallbackValue())
                    ->setString($title)
            ]
        );

        $rootNode = new ContentNode(
            $id,
            $identifier,
            $nodeTitleCollection,
            $resolvedNodeVariant
        );

        foreach ($children as $child) {
            $rootNode->addChildNode($child);
        }

        return $rootNode;
    }

    /**
     * @param ContentNode $node
     * @return ContentNode
     */
    private function findLastNode($node)
    {
        $childNodes = $node->getChildNodes();

        if ($childNodes->count() === 0) {
            return $node;
        } else {
            return $this->findLastNode($childNodes->first());
        }
    }

    /**
     * @param ContentNode $node
     * @param array       $out
     */
    private function cascadeToArray($node, &$out)
    {
        $childNodes = $node->getChildNodes();
        $out[]      = $node;

        if ($childNodes->count() !== 0) {
            $this->cascadeToArray($childNodes->first(), $out);
        }
    }
}
