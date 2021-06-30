<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListNotesType;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListFormProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShoppingListFormProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShoppingListFormProvider */
    protected $dataProvider;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

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
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 42]);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(ShoppingListNotesType::class, $shoppingList)
            ->willReturn($form);

        // Get form without existing data in locale cache
        $result = $this->dataProvider->getShoppingListNotesFormView($shoppingList);
        $this->assertSame($formView, $result);

        // Get form with existing data in locale cache
        $result = $this->dataProvider->getShoppingListNotesFormView($shoppingList);
        $this->assertSame($formView, $result);
    }
}
