<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;

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
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryProductData::class]);

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
        if ($isValid) {
            $this->assertEquals($submitData['category'], $form->get('category')->getViewData());
            $this->assertEquals($submitData['type'], $form->get('type')->getViewData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty category' => [
                'submitData' => ['category' => null, 'type' => 'simple'],
                'isValid' => true,
            ],
            'invalid category' => [
                'submitData' => ['category' => 999, 'type' => 'simple'],
                'isValid' => false
            ],
            'valid data' => [
                'submitData' => ['category' => 1, 'type' => 'simple'],
                'isValid' => true
            ],
            'wrong type' => [
                'submitData' => ['category' => 1, 'type' => 'wrong_type'],
                'isValid' => false
            ],
            'type configurable' => [
                'submitData' => ['category' => 1, 'type' => 'configurable'],
                'isValid' => true
            ]
        ];
    }
}
