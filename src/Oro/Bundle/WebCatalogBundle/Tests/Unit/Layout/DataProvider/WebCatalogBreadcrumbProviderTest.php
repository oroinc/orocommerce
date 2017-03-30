<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\WebCatalogBreadcrumbProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;

class WebCatalogBreadcrumbProviderTest extends \PHPUnit_Framework_TestCase
{
    const WEBCATALOG_ID = 1;
    const LASTNODE_ID   = 999;

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
     * @var WebCatalog
     */
    protected $webCatalog;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var CategoryProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryProvider;

    /**
     * @var WebCatalogBreadcrumbProvider
     */
    protected $breadcrumbDataProvider;

    protected function setUp()
    {
        $this->registry     = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryProvider = $this->getMockBuilder(CategoryProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadcrumbDataProvider = new WebCatalogBreadcrumbProvider(
            $this->registry,
            $this->localizationHelper,
            $this->requestStack,
            $this->categoryProvider
        );

        $this->webCatalog = $this->getEntity(WebCatalog::class, ['id' => self::WEBCATALOG_ID]);
    }

    /**
     * @dataProvider getItemsDataProvider
     *
     * @param ContentNode $rootNode
     * @param array       $expectedData
     */
    public function testGetItems(ContentNode $rootNode, array $expectedData)
    {
        $currentNode = $this->findLastNode($rootNode);
        $scope       = new Scope();

        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->any())
            ->method('getNode')
            ->willReturn($currentNode);

        $request             = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag([
                                                    '_web_content_scope' => $scope,
                                                    '_content_variant'   => $contentVariant
                                                ]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $path = [];
        $this->cascadeToArray($rootNode, $path);
        $nodeRepository->expects($this->once())
            ->method('getPath')
            ->with($currentNode)
            ->willReturn($path);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

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
