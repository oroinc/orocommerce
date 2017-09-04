<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemCollectionHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LineItemCollectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shoppingListManager;

    /**
     * @var ShoppingList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shoppingList;

    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->registry = $this->createMock(Registry::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->shoppingList = $this->createMock(ShoppingList::class);
    }

    public function testProcessWrongMethod()
    {
        $this->registry
            ->expects(static::never())
            ->method('getManagerForClass');

        $lineItemCollectionHandler = new LineItemCollectionHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertFalse($lineItemCollectionHandler->process());
    }

    public function testProcessFormNotValid()
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(static::once())
            ->method('beginTransaction');
        $manager->expects(static::never())
            ->method('commit');
        $manager->expects(static::once())
            ->method('rollback');

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with('OroShoppingListBundle:LineItem')
            ->will($this->returnValue($manager));

        $this->request = Request::create('/', 'POST');

        $this->form->expects(static::once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects(static::once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $lineItemCollectionHandler = new LineItemCollectionHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertFalse($lineItemCollectionHandler->process());
    }

    public function testProcess()
    {
        $this->request = Request::create('/', 'PUT');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(static::once())
            ->method('beginTransaction');
        $manager->expects(static::once())
            ->method('commit');
        $manager->expects(static::never())
            ->method('rollback');

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with('OroShoppingListBundle:LineItem')
            ->will($this->returnValue($manager));

        $this->form->expects(static::once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects(static::once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $lineItem = $this->createMock(LineItem::class);

        $this->form->expects(static::any())
            ->method('getData')
            ->willReturn($this->shoppingList);

        $lineItems = new ArrayCollection([$lineItem]);
        $this->shoppingList->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->request->request->add(['oro_shopping_list_line_item_collection' => ['lineItems' => []]]);

        $this->shoppingListManager->expects(static::once())
            ->method('addLineItem')
            ->willReturn($this->shoppingList);

        $customerUser = $this->createMock(CustomerUser::class);
        $this->shoppingList->expects(static::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $organization = $this->createMock(OrganizationInterface::class);
        $this->shoppingList->expects(static::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $lineItem->expects(static::once())
            ->method('setShoppingList')
            ->with($this->shoppingList);
        $lineItem->expects(static::once())
            ->method('setCustomerUser')
            ->with($customerUser);
        $lineItem->expects(static::once())
            ->method('setOrganization')
            ->with($organization);

        $lineItemCollectionHandler = new LineItemCollectionHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertTrue($lineItemCollectionHandler->process());
    }
}
