<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Form\Type\ProductInventoryStatusSelectType;

/**
 * @dbIsolation
 */
class ProductInventoryStatusSelectTypeTest extends WebTestCase
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    protected function setUp()
    {
        $this->initClient();

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->tokenManager = $this->getContainer()->get('security.csrf.token_manager');
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitData
     * @param bool $isValid
     */
    public function testSubmit(array $submitData, $isValid)
    {
        // submit form
        $form = $this->formFactory->create(ProductInventoryStatusSelectType::NAME, []);
        $form->submit($submitData);
        $this->assertEquals($isValid, $form->isValid());
        if ($isValid) {
            $this->assertEquals($submitData, $form->getData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty data' => [
                'submitData' => [],
                'isValid' => true,
            ],
            'invalid data' => [
                'submitData' => ['test'],
                'isValid' => false
            ],
            'valid data' => [
                'submitData' => [
                    'in_stock',
                    'out_of_stock',
                    'discontinued'
                ],
                'isValid' => true
            ],
        ];
    }
}
