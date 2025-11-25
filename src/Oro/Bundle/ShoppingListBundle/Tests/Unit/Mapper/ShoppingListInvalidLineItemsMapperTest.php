<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Resolver;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class ShoppingListInvalidLineItemsMapperTest extends TestCase
{
    private ShoppingListInvalidLineItemsMapper $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ShoppingListInvalidLineItemsMapper();
    }

    public function testThatExceptionIsThrownWhenValidationGroupsAreEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation groups are required');

        $this->resolver->mapViolationListBySeverity(new ConstraintViolationList(), []);
    }

    public function testGetInvalidItemsIdsWithEmptyViolationList(): void
    {
        $result = $this->resolver->mapViolationListBySeverity(new ConstraintViolationList(), ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithLineItemViolations(): void
    {
        $lineItem1 = $this->createLineItem(1);
        $lineItem2 = $this->createLineItem(2);

        $violation1 = $this->createViolation('lineItems[0].product.id', $lineItem1, ['checkout', 'rfq']);
        $violation2 = $this->createViolation('lineItems[1].quantity', $lineItem2, ['checkout']);
        $violation3 = $this->createViolation('lineItems[0].unit', $lineItem1, ['checkout']);

        $violationList = new ConstraintViolationList([$violation1, $violation2, $violation3]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);
        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation1, $violation3],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ],
                2 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation2],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ]
            ],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithKitItemLineItemViolations(): void
    {
        $lineItem = $this->createLineItem(1);
        $kitItemLineItem = $this->createKitItemLineItem($lineItem, 2);

        $shoppingListMock = $this->createMock(ShoppingList::class);

        $violation = new ConstraintViolation(
            'This is a violation',
            null,
            [],
            $shoppingListMock,
            'lineItems[0].kitItemLineItems[1].product.id',
            $kitItemLineItem,
            null,
            null,
            $this->createConstraint(['checkout'])
        );

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => [
                        2 => [
                            ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation]
                        ]
                    ]
                ],
            ],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithNonLineItemViolations(): void
    {
        $violation = $this->createViolation('shoppingList.name', null, ['checkout']);

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithInvalidPropertyPath(): void
    {
        $violation = $this->createViolation('invalid[path', null, ['checkout']);

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithNullInvalidValue(): void
    {
        $violation = $this->createViolation('lineItems[0].product.id', null, ['checkout']);

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithGroupedViolations(): void
    {
        $lineItem1 = $this->createLineItem(1);
        $lineItem2 = $this->createLineItem(2);

        $constraint1 = $this->createConstraint(['checkout']);
        $constraint2 = $this->createConstraint(['checkout']);

        $violation1 = $this->createViolation('lineItems[0].product.id', $lineItem1, ['checkout'], $constraint1);
        $violation2 = $this->createViolation('lineItems[1].quantity', $lineItem2, ['checkout'], $constraint2);

        $violationList = new ConstraintViolationList([$violation1, $violation2]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation1],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ],
                2 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation2],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ],
            ],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithViolationsWithoutConstraint(): void
    {
        $lineItem = $this->createLineItem(1);

        $violation = $this->createViolation('lineItems[0].product.id', $lineItem, []);

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ]
            ],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithDifferentShoppingListGroupKeys(): void
    {
        $lineItem1 = $this->createLineItem(1);
        $lineItem2 = $this->createLineItem(2);

        /** @var Constraint $constraint1 */
        $constraint1 = $this->createMock(Constraint::class);
        $constraint1->groups = ['checkout'];
        $constraint1->payload = ['shopping_list_group_key' => 'different_group_key1'];

        /** @var Constraint $constraint2 */
        $constraint2 = $this->createMock(Constraint::class);
        $constraint2->groups = ['rfq'];
        $constraint2->payload = ['shopping_list_group_key' => 'different_group_key2'];

        $violation1 = $this->createViolation('lineItems[0].product.id', $lineItem1, [], $constraint1);
        $violation2 = $this->createViolation('lineItems[1].quantity', $lineItem2, [], $constraint2);

        $violationList = new ConstraintViolationList([$violation1, $violation2]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout', 'rfq']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation1],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ],
                2 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation2],
                    ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                ]
            ],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithSameShoppingListGroupKey(): void
    {
        $lineItem1 = $this->createLineItem(1);
        $lineItem2 = $this->createLineItem(2);

        /** @var Constraint $constraint1 */
        $constraint1 = $this->createMock(Constraint::class);
        $constraint1->groups = ['checkout'];
        $constraint1->payload = ['shopping_list_group_key' => 'same_group_key'];

        /** @var Constraint $constraint2 */
        $constraint2 = $this->createMock(Constraint::class);
        $constraint2->groups = ['rfq'];
        $constraint2->payload = ['shopping_list_group_key' => 'same_group_key'];

        $violation1 = $this->createViolation('lineItems[0].product.id', $lineItem1, [], $constraint1);
        $violation2 = $this->createViolation('lineItems[1].quantity', $lineItem2, [], $constraint2);

        $violationList = new ConstraintViolationList([$violation1, $violation2]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout', 'rfq']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [1 => [
                ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation1],
                ShoppingListInvalidLineItemsMapper::SUB_DATA => []
            ], 2 => [
                ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation2],
                ShoppingListInvalidLineItemsMapper::SUB_DATA => []
            ]],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ], $result);
    }

    public function testGetInvalidItemsIdsWithEmptyValidationGroups(): void
    {
        $lineItem = $this->createLineItem(1);
        $violation = $this->createViolation('lineItems[0].product.id', $lineItem, []);

        $violationList = new ConstraintViolationList([$violation]);

        $result = $this->resolver->mapViolationListBySeverity($violationList, ['checkout']);

        self::assertEquals([
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [1 => [
                ShoppingListInvalidLineItemsMapper::MESSAGES => [$violation],
                ShoppingListInvalidLineItemsMapper::SUB_DATA => []
            ]],
        ], $result);
    }

    private function createLineItem(int $id): LineItem
    {
        $lineItem = new LineItemStub();
        $lineItem->setId($id);

        return $lineItem;
    }

    private function createKitItemLineItem(LineItem $lineItem, ?int $id = null): ProductKitItemLineItem
    {
        $kitItemLineItem = new ProductKitItemLineItem();
        $kitItemLineItem->setLineItem($lineItem);
        ReflectionUtil::setId($kitItemLineItem, $id);

        return $kitItemLineItem;
    }

    private function createViolation(
        string $propertyPath,
        $invalidValue,
        array $groups = [],
        ?Constraint $constraint = null
    ): ConstraintViolation {
        $constraint = $constraint ?? $this->createConstraint($groups);

        return new ConstraintViolation(
            'This is a violation',
            null,
            [],
            $invalidValue,
            $propertyPath,
            $invalidValue,
            null,
            null,
            $constraint
        );
    }

    private function createConstraint(array $groups = []): MockObject&Constraint
    {
        $constraint = $this->createMock(Constraint::class);
        $constraint->groups = $groups;

        return $constraint;
    }
}
