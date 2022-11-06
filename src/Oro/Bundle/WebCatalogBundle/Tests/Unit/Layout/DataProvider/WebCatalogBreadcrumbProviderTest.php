<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
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

        $this->webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
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

        $nodeRepository = $this->createMock(ContentNodeRepository::class);

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

    public function getItemsDataProvider(): array
    {
        return [
            'two levels'   => [
                'rootNode'     => $this->getContentNode([$this->getContentNode()]),
                'expectedData' => [
                    'crumbs' => 2
                ]
            ],
            'three levels' => [
                'rootNode'     => $this->getContentNode([$this->getContentNode([$this->getContentNode()])]),
                'expectedData' => [
                    'crumbs' => 3
                ]
            ]
        ];
    }

    public function testGetItemsWithoutContentVariant()
    {
        $categoryId = 2;
        $request = Request::create('/', Request::METHOD_GET);
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
        $this->categoryBreadcrumbProvider->expects($this->once())
            ->method('getItems')
            ->willReturn($expectedBreadcrumbs);
        $result = $this->breadcrumbDataProvider->getItems();
        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsWithoutContentVariantAndCategory()
    {
        $request = Request::create('/', Request::METHOD_GET);
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
        $nodeTitle = 'node1';
        $nodeUrl = '/';
        $currentNode = $this->getContentNode();
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

        $nodeRepository = $this->createMock(ContentNodeRepository::class);

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

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$currentNode->getTitles()],
                [$currentNode->getLocalizedUrls()]
            )
            ->willReturnOnConsecutiveCalls($nodeTitle, $nodeUrl);

        $currentPageTitle = '220 Lumen Rechargeable Headlamp';
        $categoryId = 1;
        $result = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
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
        $nodeTitle = 'node1';
        $nodeUrl = '/';
        $currentNode = $this->getContentNode();
        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($currentNode);

        $slug = new Slug();
        $request = Request::create('/', Request::METHOD_GET);
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

        $variantRepository = $this->createMock(ContentVariantRepository::class);
        $nodeRepository = $this->createMock(ContentNodeRepository::class);

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

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$currentNode->getTitles()],
                [$currentNode->getLocalizedUrls()]
            )
            ->willReturnOnConsecutiveCalls($nodeTitle, $nodeUrl);

        $currentPageTitle = '220 Lumen Rechargeable Headlamp';
        $categoryId = 1;
        $result = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
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
        $categoryId = 2;
        $currentPageTitle = '220 Lumen Rechargeable Headlamp';
        $request = Request::create('/', Request::METHOD_GET);
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
            ],
            [
                'label' => $currentPageTitle,
                'url' => null
            ]
        ];
        $this->categoryBreadcrumbProvider->expects($this->once())
            ->method('getItemsForProduct')
            ->with($categoryId, $currentPageTitle)
            ->willReturn($expectedBreadcrumbs);

        $result = $this->breadcrumbDataProvider->getItemsForProduct($categoryId, $currentPageTitle);
        $this->assertEquals($expectedBreadcrumbs, $result);
    }

    private function getContentNode(array $children = []): ContentNode
    {
        $rootNode = new ContentNode();
        foreach ($children as $child) {
            $rootNode->addChildNode($child);
        }

        return $rootNode;
    }

    private function findLastNode(ContentNode $node): ContentNode
    {
        $childNodes = $node->getChildNodes();

        if ($childNodes->count() === 0) {
            return $node;
        }

        return $this->findLastNode($childNodes->first());
    }

    private function cascadeToArray(ContentNode $node, array &$out): void
    {
        $childNodes = $node->getChildNodes();
        $out[] = $node;

        if ($childNodes->count() !== 0) {
            $this->cascadeToArray($childNodes->first(), $out);
        }
    }
}
