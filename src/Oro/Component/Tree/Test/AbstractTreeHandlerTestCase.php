<?php

namespace Oro\Component\Tree\Test;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

/**
 * Provides a few assertions for testing trees (category tree, etc.)
 */
abstract class AbstractTreeHandlerTestCase extends WebTestCase
{
    /** @var AbstractTreeHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures($this->getFixtures());

        $this->handler = $this->getContainer()->get($this->getHandlerId());
    }

    /**
     * @return string[]
     */
    abstract protected function getFixtures(): array;

    abstract protected function getHandlerId(): string;

    protected function assertTreeCreated(array $expectedData, ?object $root, bool $includeRoot): void
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

    protected function assertNodeMove(
        array $expectedStatus,
        array $expectedData,
        int $entityId,
        int $parentId,
        int $position
    ): void {
        $result = $this->handler->moveNode($entityId, $parentId, $position);
        $this->assertEquals($expectedStatus, $result);
        $this->assertEquals($expectedData, $this->getActualNodeHierarchy($entityId, $parentId, $position));
    }

    protected function getActualNodeHierarchy(int $entityId, int $parentId, int $position): array
    {
        return [];
    }
}
