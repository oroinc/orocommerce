<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;

class FrontendLineItemWidgetTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';
    const SHOPPING_LIST_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FrontendLineItemWidgetType */
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

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->type = new FrontendLineItemWidgetType(
            $this->getRegistry(),
            $this->getTokenStorage(),
            $this->translator
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

        $productUnitSelection = new ProductUnitSelectionTypeStub($this->prepareProductUnitSelectionChoices());

        return [
            new PreloadedExtension(
                [
                    FrontendLineItemType::NAME     => $this->getParentForm(),
                    $entityType->getName()         => $entityType,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    QuantityTypeTrait::$name       => $this->getQuantityType(),
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
     * @param ShoppingList $expectedShoppingList
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, ShoppingList $expectedShoppingList)
    {
        $form = $this->factory->create($this->type, $defaultData, []);

        $repo = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createFindForAccountUserQueryBuilder')
            ->will($this->returnValue(null));

        $closure = $form->get('shoppingList')->getConfig()->getOptions()['query_builder'];
        $this->assertNull($closure($repo));

        $this->addRoundingServiceExpect();

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
        $this->assertEquals($expectedShoppingList, $form->get('shoppingList')->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $product = $this->getProductEntityWithPrecision(1, 'kg', 3);
        $defaultLineItem = new LineItem();
        $defaultLineItem->setProduct($product);

        $expectedLineItem = clone $defaultLineItem;
        $expectedLineItem
            ->setQuantity(15.112)
            ->setUnit($product->getUnitPrecision('kg')->getUnit());

        return [
            'New line item with existing shopping list' => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'shoppingList'  => 1,
                    'quantity' => 15.1119,
                    'unit'     => 'kg',
                    'shoppingListLabel' => null
                ],
                'expectedData'  => $expectedLineItem,
                'expectedShoppingList' => $this->getShoppingList(1, 'Shopping List 1')
            ],
        ];
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
        /** @var ShoppingList $shoppingList */
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
        $accountUser = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
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
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findCurrentForAccountUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findCurrentForAccountUser')
            ->willReturn($this->getShoppingList(1, 'Found Current Shopping List'));

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $manager */
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
        return new FrontendLineItemType();
    }
}
