<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProductStepOneTypeTest extends WebTestCase
{
    const CATEGORY_ID = 1;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var $defaultFamily AttributeFamily;
     */
    protected $defaultFamily;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $tokenManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->defaultFamily = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

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
        $submitData['attributeFamily'] = $this->defaultFamily->getId();
        // submit form
        $form = $this->formFactory->create(ProductStepOneType::class, null);
        $form->submit($submitData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        if ($isValid) {
            $this->assertEquals($submitData['category'], $form->get('category')->getViewData());
            $this->assertEquals($submitData['type'], $form->get('type')->getViewData());
            $this->assertEquals($submitData['attributeFamily'], $form->get('attributeFamily')->getViewData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty category' => [
                'submitData' => ['category' => null, 'type' => 'simple', 'attributeFamily' => null],
                'isValid' => true,
            ],
            'invalid category' => [
                'submitData' => ['category' => 999, 'type' => 'simple', 'attributeFamily' => 999],
                'isValid' => false
            ],
            'valid data' => [
                'submitData' => [
                    'category' => self::CATEGORY_ID,
                    'type' => 'simple',
                ],
                'isValid' => true
            ],
            'wrong type' => [
                'submitData' => [
                    'category' => self::CATEGORY_ID,
                    'type' => 'wrong_type',
                ],
                'isValid' => false
            ],
            'type configurable with family without attributes' => [
                'submitData' => [
                    'category' => self::CATEGORY_ID,
                    'type' => 'configurable',
                ],
                'isValid' => false
            ]
        ];
    }
}
