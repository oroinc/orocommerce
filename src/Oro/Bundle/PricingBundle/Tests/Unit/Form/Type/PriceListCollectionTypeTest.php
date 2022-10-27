<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Form\Extension\PriceListFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PriceListCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'entry_options' => [
                'data_class' => PriceListToWebsite::class
            ]
        ];

        $form = $this->factory->create(PriceListCollectionType::class, $existing, $options);
        $form->submit($submitted);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $pl1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $pl2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $pl3 = $this->getEntity(PriceList::class, ['id' => 3]);

        return [
            'test' => [
                'existing' => [
                    (new PriceListToWebsite())->setSortOrder(100)->setPriceList($pl1)->setMergeAllowed(true),
                    (new PriceListToWebsite())->setSortOrder(200)->setPriceList($pl2)->setMergeAllowed(false),
                    (new PriceListToWebsite())->setSortOrder(300)->setPriceList($pl3)->setMergeAllowed(true)
                ],
                'submitted' => [
                    [
                        PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '3',
                        SortableExtension::POSITION_FIELD_NAME => '500',
                        PriceListFormExtension::MERGE_ALLOWED_FIELD => true
                    ],
                    [
                        PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '1',
                        SortableExtension::POSITION_FIELD_NAME => '400',
                        PriceListFormExtension::MERGE_ALLOWED_FIELD => false
                    ],
                    [
                        PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '2',
                        SortableExtension::POSITION_FIELD_NAME => '600',
                        PriceListFormExtension::MERGE_ALLOWED_FIELD => true
                    ],
                    [
                        PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '',
                        SortableExtension::POSITION_FIELD_NAME => '',
                        PriceListFormExtension::MERGE_ALLOWED_FIELD => true
                    ]
                ],
                'expected' => [
                    (new PriceListToWebsite())->setSortOrder(400)->setPriceList($pl1)->setMergeAllowed(false),
                    (new PriceListToWebsite())->setSortOrder(600)->setPriceList($pl2)->setMergeAllowed(true),
                    (new PriceListToWebsite())->setSortOrder(500)->setPriceList($pl3)->setMergeAllowed(true)
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return (new PriceListCollectionTypeExtensionsProvider())->getExtensions();
    }

    public function testGetParent()
    {
        $type = new PriceListCollectionType();
        $this->assertSame(CollectionType::class, $type->getParent());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $type = new PriceListCollectionType();
        $type->finishView($view, $this->createMock(FormInterface::class), ['render_as_widget' => true]);

        $this->assertArrayHasKey('render_as_widget', $view->vars);
        $this->assertTrue($view->vars['render_as_widget']);
    }
}
