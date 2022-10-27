<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class MessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var MessageFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->factory = new MessageFactory($this->doctrineHelper);
    }

    /**
     * @dataProvider productIdsDataProvider
     */
    public function testGetProductIds(array $data, array $expected)
    {
        $this->assertEquals($expected, $this->factory->getProductIds($data));
    }

    public function productIdsDataProvider(): array
    {
        return [
            'empty message' => [[], []],
            'with products' => [['products' => [1, 2]], [1, 2]]
        ];
    }

    public function testGetContextWithoutContext()
    {
        $this->assertNull($this->factory->getContext([]));
    }

    public function testGetContext()
    {
        $class = Website::class;
        $id = 1;
        $data = [
            'products' => [],
            'context' => [
                'class' => $class,
                'id' => $id
            ]
        ];

        $entity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with($class, $id)
            ->willReturn($entity);
        $this->assertEquals($entity, $this->factory->getContext($data));
    }

    /**
     * @dataProvider messageDataProvider
     * @param array $products
     * @param object|null $context
     * @param array $expected
     */
    public function testCreateShoppingListItemsActualizeMessage(array $products, $context, array $expected)
    {
        if ($context) {
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityClass')
                ->with($context)
                ->willReturn(get_class($context));
            $this->doctrineHelper->expects($this->once())
                ->method('getSingleEntityIdentifier')
                ->with($context, false)
                ->willReturn($context->getId());
        }

        $this->assertEquals(
            $expected,
            $this->factory->createShoppingTotalsInvalidateMessage($context, $products)
        );
    }

    public function messageDataProvider(): array
    {
        $products = [1, 2];

        return [
            'without context' => [
                $products,
                null,
                ['products' => $products]
            ],
            'with context' => [
                $products,
                $this->getEntity(Website::class, ['id' => 42]),
                [
                    'products' => $products,
                    'context' => [
                        'class' => Website::class,
                        'id' => 42
                    ]
                ]
            ]
        ];
    }

    public function testCreateShoppingListItemsActualizeMessageForConfigScopeGlobal()
    {
        $this->assertEquals([], $this->factory->createShoppingListTotalsInvalidateMessageForConfigScope('global', 0));
    }

    public function testCreateShoppingListItemsActualizeMessageForConfigScopeWebsite()
    {
        $this->assertEquals(
            [
                'context' => [
                    'class' => Website::class,
                    'id' => 42
                ]
            ],
            $this->factory->createShoppingListTotalsInvalidateMessageForConfigScope('website', 42)
        );
    }
}
