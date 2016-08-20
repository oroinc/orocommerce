<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\JsTree;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadPageData;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Test\AbstractTreeHandlerTestCase;

/**
 * @dbIsolation
 */
class PageTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return 'Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData';
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId()
    {
        return 'orob2b_cms.page_tree_handler';
    }

    /**
     * @dataProvider createDataProvider
     * @param string|null $entityReference
     * @param bool $includeRoot
     * @param array $expectedData
     */
    public function testCreateTree($entityReference, $includeRoot, array $expectedData)
    {
        $entity = null;
        if ($entityReference !== null) {
            /** @var Page $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var Page $entity */
            $entity = $this->getPage($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $entity->getTitle();
            if ($data['parent'] !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
                $data['parent'] = $this->getPage($data['parent'])->getId();
            }
            unset($data['entity']);
            $result[$data['id']] = $data;
            return $result;
        }, []);

        $this->assertTreeCreated($expectedData, $entity, $includeRoot);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'page.1 without root' => [
                'root' => 'page.1',
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => 'page.1_2',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'page.1_3',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'page.1' => [
                'root' => 'page.1',
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => 'page.1',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'page.1_2',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'page.1_3',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'all without root' => [
                'root' => null,
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => 'page.1',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'page.1_2',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'page.1_3',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadPageData::ABOUT_TITLE,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => LoadPageData::CONTENT_US_TITLE,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                ]
            ],
            'all' => [
                'root' => null,
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => 'page.1',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'page.1_2',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'page.1_3',
                        'parent' => 'page.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadPageData::ABOUT_TITLE,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => LoadPageData::CONTENT_US_TITLE,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider moveDataProvider
     * @param string $entityReference
     * @param string $parent
     * @param int $position
     * @param array $expectedStatus
     * @param array $expectedData
     */
    public function testMove($entityReference, $parent, $position, array $expectedStatus, array $expectedData)
    {
        $entityId = $this->getPage($entityReference)->getId();
        if ($parent !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
            $parent = $this->getReference($parent)->getId();
        }

        $this->assertNodeMove($expectedStatus, $expectedData, $entityId, $parent, $position);
    }

    /**
     * @return array
     */
    public function moveDataProvider()
    {
        return [
            [
                'entity' => 'page.1_3',
                'parent' => 'page.1_2',
                'position' => 0,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    'page.1' => [],
                    'page.1_2' => [
                        'parent' => 'page.1'
                    ],
                    'page.1_3' => [
                        'parent' => 'page.1_2'
                    ],
                    LoadPageData::CONTENT_US_TITLE => [],
                    LoadPageData::ABOUT_TITLE => [],
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getActualNodeHierarchy($entityId, $parentId, $position)
    {
        $entities = $this->getContainer()->get('doctrine')->getManagerForClass('OroCMSBundle:Page')
            ->getRepository('OroCMSBundle:Page')->findBy([], ['level' => 'DESC', 'left' => 'DESC']);
        return array_reduce($entities, function ($result, Page $category) {
            $result[$category->getTitle()] = [];
            if ($category->getParentPage()) {
                $result[$category->getTitle()]['parent'] = $category->getParentPage()->getTitle();
            }
            return $result;
        }, []);
    }

    /**
     * @param string $title
     * @return Page
     */
    protected function getPage($title)
    {
        $page = $this->getReferenceRepository()->hasReference($title) ? $this->getReference($title) : null;
        if (!$page) {
            $page = $this->getContainer()->get('doctrine')->getManagerForClass('OroCMSBundle:Page')
                ->getRepository('OroCMSBundle:Page')->findOneByTitle($title);
        }
        return $page;
    }
}
