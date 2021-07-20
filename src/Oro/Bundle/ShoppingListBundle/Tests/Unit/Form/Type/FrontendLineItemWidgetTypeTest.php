<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendLineItemWidgetTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'Oro\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';
    const SHOPPING_LIST_CLASS = 'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FrontendLineItemWidgetType */
    protected $type;

    /**
     * @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currentShoppingListManager;

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
    protected function setUp(): void
    {
        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');
        $this->currentShoppingListManager = $this->getMockBuilder(CurrentShoppingListManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new FrontendLineItemWidgetType(
            $this->getRegistry(),
            $this->translator,
            $this->currentShoppingListManager
        );

        $this->type->setShoppingListClass(self::SHOPPING_LIST_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub(
            [
                1 => $this->getShoppingList(1, 'Shopping List 1'),
                2 => $this->getShoppingList(2, 'Shopping List 2'),
            ]
        );

        $productUnitSelection = new ProductUnitSelectionTypeStub($this->prepareProductUnitSelectionChoices());

        return [
            new PreloadedExtension(
                [
                    FrontendLineItemWidgetType::class => $this->type,
                    FrontendLineItemType::class     => $this->getParentForm(),
                    EntityType::class              => $entityType,
                    ProductUnitSelectionType::class => $productUnitSelection,
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

        $form = $this->factory->create(FrontendLineItemWidgetType::class, $lineItem);

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
        $form = $this->factory->create(FrontendLineItemWidgetType::class, $defaultData, []);
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
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
                    'quantity' => 15.112,
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
        $shoppingList = $this->getShoppingList(1, 'Found Current Shopping List');
        $this->currentShoppingListManager->method('getCurrent')->willReturn($shoppingList);
        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $view->children['shoppingList'] = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject| $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->type->finishView($view, $form, []);

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
        $shoppingList = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $id);
        $shoppingList->setLabel($label);

        return $shoppingList;
    }

    /**
     * @return TokenStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTokenStorage()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $customerUser */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerUser $customerUser */
        $customerUser = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\CustomerUser')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage */
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $tokenStorage;
    }

    /**
     * @return ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRegistry()
    {
        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findCurrentForCustomerUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findCurrentForCustomerUser')
            ->willReturn($this->getShoppingList(1, 'Found Current Shopping List'));

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry $registry */
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
        /**
         * @var ProductUnitFieldsSettingsInterface|\PHPUnit\Framework\MockObject\MockObject $productUnitFieldsSettings
         */
        $productUnitFieldsSettings = $this->getMockBuilder(ProductUnitFieldsSettingsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitFieldsSettings->expects($this->any())
            ->method('isProductUnitSelectionVisible')
            ->willReturn(true);
        return new FrontendLineItemType($productUnitFieldsSettings);
    }
}
