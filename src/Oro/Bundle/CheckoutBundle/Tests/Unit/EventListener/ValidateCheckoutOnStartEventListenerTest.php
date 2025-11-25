<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ValidateCheckoutOnStartEventListener;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidateCheckoutOnStartEventListenerTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;

    private CheckoutValidationGroupsBySourceEntityProvider&MockObject $validationGroupsProvider;

    private FeatureChecker&MockObject $featureChecker;

    private ValidateCheckoutOnStartEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validationGroupsProvider = $this->createMock(CheckoutValidationGroupsBySourceEntityProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ValidateCheckoutOnStartEventListener(
            $this->validator,
            $this->validationGroupsProvider
        );

        $this->listener->setFeatureChecker($this->featureChecker);
    }

    public function testOnStartWhenEntityNotCheckout(): void
    {
        $context = (new ActionData())
            ->set('checkout', new \stdClass());
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStart($event);
    }

    public function testOnStartWhenNoViolations(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $context = (new ActionData())
            ->set('checkout', $checkout);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_start%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($checkout, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList());

        $this->listener->onStart($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartWhenHasViolations(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $context = (new ActionData())
            ->set('checkout', $checkout);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_start%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $violation1 = new ConstraintViolation('sample violation1', null, [], $checkout, null, $checkout);
        $violation2 = new ConstraintViolation('sample violation2', null, [], $checkout, null, $checkout);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($checkout, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList([$violation1, $violation2]));

        $this->listener->onStart($event);

        self::assertEquals(
            [
                ['message' => $violation1->getMessage(), 'context' => $violation1],
                ['message' => $violation2->getMessage(), 'context' => $violation2],
            ],
            $event->getErrors()->toArray()
        );
    }

    public function testOnStartFromShoppingListWhenEntityNotShoppingList(): void
    {
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => null, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStartFromShoppingList($event);
    }

    public function testOnStartFromShoppingListWhenNoViolationsWhenFeatureDisabled(): void
    {
        $this->listener->addFeature('feature');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_start%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($shoppingList, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList());

        $this->listener->onStartFromShoppingList($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartFromShoppingListWhenNoViolationsWhenFeatureEnabled(): void
    {
        $this->listener->addFeature('feature');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $invalidShoppingListLineItemsProvider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $this->listener->setInvalidShoppingListLineItemsProvider($invalidShoppingListLineItemsProvider);

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->validationGroupsProvider
            ->expects(self::never())
            ->method('getValidationGroupsBySourceEntity');

        $invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->willReturn([
                'errors' => []
            ]);

        $this->validator
            ->expects(self::never())
            ->method('validate');

        $this->validationGroupsProvider
            ->expects(self::never())
            ->method('getValidationGroupsBySourceEntity');

        $this->listener->onStartFromShoppingList($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartFromShoppingListWhenHasViolationsOld(): void
    {
        $this->listener->addFeature('feature');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_start%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $violation1 = new ConstraintViolation('sample violation1', null, [], $shoppingList, null, $shoppingList);
        $violation2 = new ConstraintViolation('sample violation2', null, [], $shoppingList, null, $shoppingList);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($shoppingList, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList([$violation1, $violation2]));

        $this->listener->onStartFromShoppingList($event);

        self::assertEquals(
            [
                ['message' => $violation1->getMessage(), 'context' => $violation1],
                ['message' => $violation2->getMessage(), 'context' => $violation2],
            ],
            $event->getErrors()->toArray()
        );
    }

    public function testOnStartFromShoppingListWhenHasViolationsFromProvider()
    {
        $this->listener->addFeature('feature');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $invalidShoppingListLineItemsProvider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $this->listener->setInvalidShoppingListLineItemsProvider($invalidShoppingListLineItemsProvider);

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->validationGroupsProvider
            ->expects(self::never())
            ->method('getValidationGroupsBySourceEntity');

        $violation1 = new ConstraintViolation('sample violation1', null, [], $shoppingList, null, $shoppingList);
        $violation2 = new ConstraintViolation('sample violation2', null, [], $shoppingList, null, $shoppingList);
        $subViolation1 = new ConstraintViolation('sub violation1', null, [], $shoppingList, null, $shoppingList);
        $subViolation2 = new ConstraintViolation('sub violation2', null, [], $shoppingList, null, $shoppingList);

        $invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->willReturn([
                'errors' => [
                    0 => [
                        'messages' => [$violation1, $violation2],
                        'subData' => [
                            0 => [
                                'messages' => [$subViolation1, $subViolation2],
                            ]
                        ]
                    ]
                ]
            ]);

        $this->listener->onStartFromShoppingList($event);

        self::assertEquals(
            [
                ['message' => $violation1->getMessage(), 'context' => $violation1],
                ['message' => $violation2->getMessage(), 'context' => $violation2],
                ['message' => $subViolation1->getMessage(), 'context' => $subViolation1],
                ['message' => $subViolation2->getMessage(), 'context' => $subViolation2],
            ],
            $event->getErrors()->toArray()
        );
    }

    public function testOnStartFromShoppingListWhenHasViolationsWhenDivideToGroupsDisabled()
    {
        $this->listener->addFeature('feature');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList, 'checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_start%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $violation1 = new ConstraintViolation('sample violation1', null, [], $shoppingList, null, $shoppingList);
        $violation2 = new ConstraintViolation('sample violation2', null, [], $shoppingList, null, $shoppingList);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($shoppingList, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList([$violation1, $violation2]));

        $this->listener->onStartFromShoppingList($event);

        self::assertEquals(
            [
                ['message' => $violation1->getMessage(), 'context' => $violation1],
                ['message' => $violation2->getMessage(), 'context' => $violation2],
            ],
            $event->getErrors()->toArray()
        );
    }
}
