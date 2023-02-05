<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendLineItemWidgetTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    /** @var TranslatorInterface */
    private $translator;

    /** @var FrontendLineItemWidgetType */
    private $type;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var array */
    private $units = [
        'item',
        'kg'
    ];

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);

        $this->type = new FrontendLineItemWidgetType(
            $this->translator,
            $this->currentShoppingListManager
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new FrontendLineItemType(),
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getShoppingList(1, 'Shopping List 1'),
                        2 => $this->getShoppingList(2, 'Shopping List 2'),
                    ]),
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        $this->prepareProductUnitSelectionChoices()
                    ),
                    $this->getQuantityType(),
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
     */
    public function testSubmit(
        LineItem $defaultData,
        array $submittedData,
        LineItem $expectedData,
        ShoppingList $expectedShoppingList
    ) {
        $form = $this->factory->create(FrontendLineItemWidgetType::class, $defaultData, []);
        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(ShoppingListRepository::class);
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
        $this->assertEquals($expectedShoppingList, $form->get('shoppingList')->getData());
    }

    public function submitDataProvider(): array
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
        $this->currentShoppingListManager->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);
        $view = $this->createMock(FormView::class);
        $view->children['shoppingList'] = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);

        $this->type->finishView($view, $form, []);

        $this->assertEquals($shoppingList, $view->children['shoppingList']->vars['currentShoppingList']);
    }

    private function getShoppingList(int $id, string $label): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        $shoppingList->setLabel($label);

        return $shoppingList;
    }

    private function prepareProductUnitSelectionChoices(): array
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
