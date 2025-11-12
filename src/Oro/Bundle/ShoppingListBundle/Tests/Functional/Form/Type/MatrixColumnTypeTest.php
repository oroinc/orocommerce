<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixColumnType;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class MatrixColumnTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader()
        );
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testBuildFormWithMissedProduct(): void
    {
        $this->markTestSkipped('Skipping due to database constraint violation issue BAP-23215');
        $column = new MatrixCollectionColumn();
        $column->product = null;

        $form = self::createForm(MatrixColumnType::class, $column);

        self::assertFormOptions($form->get('quantity'), [
            'label' => false,
            'attr' => [
                'placeholder' => 'oro.frontend.shoppinglist.view.qty.label',
                'data-floating-error' => ''
            ],
            'precision' => 0,
            'disabled' => true
        ]);
    }

    public function testBuildFormWithConfiguredProduct(): void
    {
        $this->markTestSkipped('Skipping due to database constraint violation issue BAP-23215');
        $matrixCollection = new MatrixCollection();
        $matrixCollectionRow = new MatrixCollectionRow();
        $matrixCollectionColumn = new MatrixCollectionColumn();
        /**
         * @var Product $product
         */
        $product = $this->getReference('product-1');

        $matrixCollection->unit = $product->getPrimaryUnitPrecision()->getUnit();
        $matrixCollection->rows[] = $matrixCollectionRow;
        $matrixCollectionRow->columns[] = $matrixCollectionColumn;
        $matrixCollectionColumn->product = $product;

        $root = self::createForm(MatrixCollectionType::class, $matrixCollection);

        self::assertFormOptions($root->get('rows')[0]->get('columns')[0]->get('quantity'), [
            'label' => false,
            'attr' =>
                [
                    'placeholder' => 'oro.frontend.shoppinglist.view.qty.label',
                    'data-floating-error' => '',
                    'data-validation' =>
                        [
                            'decimal-precision' => [
                                'message' => 'oro.non_valid_precision',
                                'precision' => 0,
                            ],
                        ],
                    'data-precision' => 0,
                    'data-input-widget' => 'number',
                ],
            'precision' => 0,
        ]);
    }
}
