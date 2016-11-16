<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListFormProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ShoppingListFormProvider */
    protected $dataProvider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);
        $this->router = $this->getMock(UrlGeneratorInterface::class);

        $this->dataProvider = new ShoppingListFormProvider($this->formFactory, $this->router);
    }

    public function testGetShoppingListFormViewWithoutId()
    {
        $shoppingList = $this->getEntity(ShoppingList::class);
        $action = 'form_action';

        $formView = $this->getMock(FormView::class);

        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(ShoppingListType::NAME, $shoppingList, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(ShoppingListFormProvider::SHOPPING_LIST_CREATE_ROUTE_NAME, [])
            ->willReturn($action);

        // Get form without existing data in locale cache
        $result = $this->dataProvider->getShoppingListFormView($shoppingList);
        $this->assertInstanceOf(FormView::class, $result);

        // Get form with existing data in locale cache
        $result = $this->dataProvider->getShoppingListFormView($shoppingList);
        $this->assertInstanceOf(FormView::class, $result);
    }

    public function testGetShoppingListFormViewWithId()
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 2]);
        $action = 'form_action';

        $formView = $this->getMock(FormView::class);

        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(ShoppingListType::NAME, $shoppingList, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(ShoppingListFormProvider::SHOPPING_LIST_VIEW_ROUTE_NAME, ['id' => 2])
            ->willReturn($action);

        // Get form without existing data in locale cache
        $result = $this->dataProvider->getShoppingListFormView($shoppingList);
        $this->assertInstanceOf(FormView::class, $result);

        // Get form with existing data in locale cache
        $result = $this->dataProvider->getShoppingListFormView($shoppingList);
        $this->assertInstanceOf(FormView::class, $result);
    }
}
