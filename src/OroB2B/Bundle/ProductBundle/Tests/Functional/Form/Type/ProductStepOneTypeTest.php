<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;

/**
 * @dbIsolation
 */
class ProductStepOneTypeTest extends WebTestCase
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
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);

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
        $submitData['_token'] = $this->tokenManager->getToken('product')->getValue();
        // submit form
        $form = $this->formFactory->create(ProductStepOneType::NAME, []);
        $form->submit($submitData);
        $this->assertEquals($isValid, $form->isValid());
        if ($isValid && $submitData['category']) {
            $this->assertEquals($submitData['category'], $form->get('category')->getViewData());
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
                'submitData' => ['category' => 999],
                'isValid' => false
            ],
            'valid data' => [
                'submitData' => ['category' => 1],
                'isValid' => true
            ],
        ];
    }
}
