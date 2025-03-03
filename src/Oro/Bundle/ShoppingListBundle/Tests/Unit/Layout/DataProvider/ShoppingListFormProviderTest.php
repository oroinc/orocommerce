<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListNotesType;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListFormProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShoppingListFormProviderTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private UrlGeneratorInterface&MockObject $router;
    private ShoppingListFormProvider $dataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->dataProvider = new ShoppingListFormProvider($this->formFactory, $this->router);
    }

    public function testGetShoppingListNotesFormView(): void
    {
        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 42);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(ShoppingListNotesType::class, $shoppingList)
            ->willReturn($form);

        // Get form without existing data in locale cache
        $result = $this->dataProvider->getShoppingListNotesFormView($shoppingList);
        self::assertSame($formView, $result);

        // Get form with existing data in locale cache
        $result = $this->dataProvider->getShoppingListNotesFormView($shoppingList);
        self::assertSame($formView, $result);
    }
}
