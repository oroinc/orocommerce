<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ShoppingListBundle\EventListener\Form\Type\LineItemSubscriber;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendLineItemWidgetTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';
    const SHOPPING_LIST_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';
    const NEW_SHOPPING_LIST_ID = 10;

    /**
     * @var FrontendLineItemWidgetType
     */
    protected $type;

    /**
     * @var array
     */
    protected $units = [
        'item',
        'kg'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListManager
            ->expects($this->any())
            ->method('createCurrent')
            ->willReturn($this->getShoppingList(self::NEW_SHOPPING_LIST_ID, 'New Shopping List'));

        $this->type = new FrontendLineItemWidgetType(
            $this->getRegistry(),
            $shoppingListManager,
            $this->getTokenStorage()
        );

        $this->type->setShoppingListClass(self::SHOPPING_LIST_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                1 => $this->getShoppingList(1, 'Shopping List 1'),
                2 => $this->getShoppingList(2, 'Shopping List 2'),
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        return [
            new PreloadedExtension(
                [
                    FrontendLineItemType::NAME     => $this->getParentForm(),
                    $entityType->getName()         => $entityType,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $lineItem = (new LineItem())
            ->setProduct($this->getProductEntityWithPrecision(1, 'kg', 3));

        $form = $this->factory->create($this->type, $lineItem);

        $this->assertTrue($form->has('shoppingList'));
        $this->assertTrue($form->has('quantity'));
        $this->assertTrue($form->has('unit'));
        $this->assertTrue($form->has('shoppingListLabel'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $product = $this->getProductEntityWithPrecision(1, 'kg', 3);
        $defaultLineItem = new LineItem();
        $defaultLineItem->setProduct($product);

        /** @var ShoppingList $expectedShoppingList */
        $expectedShoppingList = $this->getShoppingList(1, 'Shopping List 1');
        $expectedLineItem = clone $defaultLineItem;
        $expectedLineItem
            ->setQuantity(15.112)
            ->setUnit($product->getUnitPrecision('kg')->getUnit())
            ->setShoppingList($expectedShoppingList);

        $expectedLineItem2 = clone $defaultLineItem;
        $expectedLineItem2
            ->setQuantity(10)
            ->setUnit($product->getUnitPrecision('kg')->getUnit())
            ->setShoppingList($this->getShoppingList(self::NEW_SHOPPING_LIST_ID, 'New Shopping List'));

        return [
            'New line item with existing shopping list' => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'shoppingList'  => 1,
                    'quantity' => 15.1119,
                    'unit'     => 'kg',
                    'shoppingListLabel' => null
                ],
                'expectedData'  => $expectedLineItem
            ],
            'New line item with new shopping list' => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'shoppingList'  => null,
                    'quantity' => 10,
                    'unit'     => 'kg',
                    'shoppingListLabel' => 'New Shopping List'
                ],
                'expectedData'  => $expectedLineItem2,
            ]
        ];
    }

    public function testCheckShoppingListLabel()
    {
        /** @var ConstraintViolationBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $violationBuilder */
        $violationBuilder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('shoppingListLabel')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface $context */
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context
            ->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $lineItem = new LineItem();
        $this->type->checkShoppingListLabel($lineItem, $context);
    }

    public function testFinishView()
    {
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject| $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->type->finishView($view, $form, []);

        $shoppingList = $this->getShoppingList(1, 'Found Current Shopping List');
        $this->assertEquals($shoppingList, $view->children['shoppingList']->vars['currentShoppingList']);
    }

    /**
     * @param integer $id
     * @param string  $label
     *
     * @return ShoppingList
     */
    protected function getShoppingList($id, $label)
    {
        /** @var \OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $id);
        $shoppingList->setLabel($label);

        return $shoppingList;
    }

    /**
     * @return TokenStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTokenStorage()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $accountUser */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUser $accountUser */
        $accountUser = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($accountUser);

        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $tokenStorage;
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistry()
    {
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findCurrentForAccountUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findCurrentForAccountUser')
            ->willReturn($this->getShoppingList(1, 'Found Current Shopping List'));

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $manager */
        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @param integer $productId
     * @param string  $unitCode
     * @param integer $precision
     *
     * @return Product
     */
    protected function getProductEntityWithPrecision($productId, $unitCode, $precision = 0)
    {
        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }

    /**
     * @param string $className
     * @param int    $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
        return $entity;
    }

    /**
     * @return array
     */
    protected function prepareProductUnitSelectionChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    /**
     * @return FrontendLineItemType
     */
    protected function getParentForm()
    {
        $form = new FrontendLineItemType();
        $form->setLineItemSubscriber($this->getLineItemSubscriber());
        $form->setDataClass(self::DATA_CLASS);

        return $form;
    }

    /**
     * @return LineItemSubscriber
     */
    protected function getLineItemSubscriber()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemManager $lineItemManager */
        $lineItemManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager')
            ->disableOriginalConstructor()
            ->getMock();
        $lineItemManager->expects($this->any())
            ->method('roundProductQuantity')
            ->willReturnCallback(
                function ($product, $unit, $quantity) {
                    /** @var \PHPUnit_Framework_MockObject_MockObject|Product $product */
                    return round($quantity, $product->getUnitPrecision($unit)->getPrecision());
                }
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $lineItemSubscriber = new LineItemSubscriber($lineItemManager, $registry);

        return $lineItemSubscriber;
    }
}
