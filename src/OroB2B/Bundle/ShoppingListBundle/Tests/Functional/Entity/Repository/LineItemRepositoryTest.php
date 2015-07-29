<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;

/**
 * @dbIsolation
 */
class LineItemRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testFindDuplicate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $repository = $this->getRepository();

        $duplicate = $repository->findDuplicate($lineItem);
        $this->assertNull($duplicate);

        $this->setProperty($lineItem, 'id', ($lineItem->getId() + 1));
        $duplicate = $repository->findDuplicate($lineItem);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', $duplicate);
    }

    /**
     * @return LineItemRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:LineItem');
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
