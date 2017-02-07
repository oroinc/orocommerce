<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class MatrixCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var MatrixCollectionType */
    protected $type;

    protected function setUp()
    {
        $this->type = new MatrixCollectionType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param MatrixCollection $defaultData
     * @param array $submittedData
     * @param MatrixCollection $expectedData
     */
    public function testSubmit(MatrixCollection $defaultData, array $submittedData, MatrixCollection $expectedData)
    {
        $form = $this->factory->create($this->type, $defaultData);
        $form->submit($submittedData);
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'with quantities' => [
                'defaultData' => $this->createCollection(),
                'submittedData' => [
                    'rows' => [
                        [
                            'columns' => [
                                [
                                    'quantity' => 3,
                                ],
                                [],
                            ]
                        ],
                        [
                            'columns' => [
                                [],
                                [
                                    'quantity' => 5,
                                ],
                            ]
                        ],
                    ],
                ],
                'expectedData' => $this->createCollection(true),
            ],
            'empty data' => [
                'defaultData' => $this->createCollection(),
                'submittedData' => [],
                'expectedData' => $this->createCollection(),
            ],
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('data_class', $options);
                    $this->assertEquals(MatrixCollection::class, $options['data_class']);
                }
            );

        $this->type->configureOptions($resolver);
    }

    /**
     * @param bool $withQuantities
     * @return MatrixCollection
     */
    private function createCollection($withQuantities = false)
    {
        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnMediumGreen->product = $simpleProductMediumGreen;

        if ($withQuantities) {
            $columnSmallRed->quantity = 3;
            $columnMediumGreen->quantity = 5;
        }

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $collection = new MatrixCollection();

        $unit = new ProductUnit();
        $unit->setCode('item');

        $collection->unit = $unit;
        $collection->rows = [$rowSmall, $rowMedium];

        return $collection;
    }
}
