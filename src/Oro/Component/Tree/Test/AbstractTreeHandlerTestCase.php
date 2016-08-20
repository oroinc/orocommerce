<?php

namespace Oro\Component\Tree\Test;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

abstract class AbstractTreeHandlerTestCase extends WebTestCase
{
    /**
     * @var AbstractTreeHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures((array)$this->getFixtures());

        $this->handler = $this->getContainer()->get($this->getHandlerId());
    }

    /**
     * @return array
     */
    abstract protected function getFixtures();

    /**
     * @return string
     */
    abstract protected function getHandlerId();

    /**
     * @param array $expectedData
     * @param $root
     * @param $includeRoot
     */
    protected function assertTreeCreated(array $expectedData, $root, $includeRoot)
    {
        $actualTree = $this->handler->createTree($root, $includeRoot);
        $actualTree = array_reduce($actualTree, function ($result, $data) {
            $result[$data['id']] = $data;
            return $result;
        }, []);
        ksort($expectedData);
        ksort($actualTree);
        $this->assertEquals($expectedData, $actualTree);
    }

    /**
     * @param array $expectedStatus
     * @param array $expectedData
     * @param int $entityId
     * @param int|string $parentId
     * @param int $position
     */
    protected function assertNodeMove(array $expectedStatus, array $expectedData, $entityId, $parentId, $position)
    {
        $result = $this->handler->moveNode($entityId, $parentId, $position);
        $this->assertEquals($expectedStatus, $result);
        $this->assertEquals($expectedData, $this->getActualNodeHierarchy($entityId, $parentId, $position));
    }

    /**
     * @param int $entityId
     * @param int $parentId
     * @param int $position
     * @return array
     */
    protected function getActualNodeHierarchy($entityId, $parentId, $position)
    {
        return [];
    }
}
