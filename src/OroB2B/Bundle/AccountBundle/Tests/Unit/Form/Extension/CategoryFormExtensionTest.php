<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ValidatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';
    const LOCALE_CLASS = 'OroB2B\Bundle\WebsiteBundle\Entity\Locale';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
    }


    public function testBuildForm()
    {
        $this->setRegistryExpectations();

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $form = $this->getFactory()->create(CategoryType::NAME);

        $this->assertTrue($form->has('categoryVisibility'));
        $this->assertTrue($form->has('visibilityForAccount'));
        $this->assertTrue($form->has('visibilityForAccountGroup'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $this->setRegistryExpectations();
        $this->setRepositoryExpectations();

        $form = $this->getFactory()->create(CategoryType::NAME, $defaultData, ['data_class' => self::DATA_CLASS]);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $category = $this->getCategory(1);

        return [
            'Visibility' => [
                'defaultData'   => $category,
                'submittedData' => [
                    'categoryVisibility'        => CategoryVisibility::VISIBLE,
                    'visibilityForAccount'      => json_encode(
                        [
                            1 => [
                                'entity' => new Account(),
                                'data'   => [
                                    'visibility' => AccountCategoryVisibility::HIDDEN
                                ]
                            ]
                        ]
                    ),
                    'visibilityForAccountGroup' => json_encode(
                        [
                            1 => [
                                'entity' => new AccountGroup(),
                                'data'   => [
                                    'visibility' => AccountGroupCategoryVisibility::CONFIG
                                ]
                            ]
                        ]
                    ),
                ],
                'expectedData'  => $category,
            ],
        ];
    }

    public function testGetExtendedType()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $er */
        $er = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($er);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $extension = new CategoryFormExtension($this->registry);
        $this->assertEquals(CategoryType::NAME, $extension->getExtendedType());
    }

    /**
     * @param integer $id
     *
     * @return Category
     */
    protected function getCategory($id)
    {
        return $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', $id);
    }


    /**
     * @param string $className
     * @param int    $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
     * @return FormFactoryInterface
     */
    protected function getFactory()
    {
        return Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }
}
