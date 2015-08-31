<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

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
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

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

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->setConstructorArgs([$this->registry])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator */
        $validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $oroEnumSelect = new EnumSelectType([]);
        $entityChangeSetType = new EntityChangesetType($doctrineHelper);
        $oroIdentifierType = new EntityIdentifierType([]);
        $localizedFallbackType = new LocalizedFallbackValueCollectionType($this->registry);
        $localizedPropertyType = new LocalizedPropertyType();
        $localeCollectionType = new LocaleCollectionType($this->registry);
        $localeCollectionType->setLocaleClass(self::LOCALE_CLASS);
        $localeFallbackValue = new FallbackValueType();
        $localeFallBackProperty = new FallbackPropertyType($translator);
        $categoryType = new CategoryType();

        return [
            new PreloadedExtension(
                [
                    $oroEnumSelect->getName()          => $oroEnumSelect,
                    $entityChangeSetType->getName()    => $entityChangeSetType,
                    $oroIdentifierType->getName()      => $oroIdentifierType,
                    $localizedFallbackType->getName()  => $localizedFallbackType,
                    $localizedPropertyType->getName()  => $localizedPropertyType,
                    $localeCollectionType->getName()   => $localeCollectionType,
                    $localeFallbackValue->getName()    => $localeFallbackValue,
                    $localeFallBackProperty->getName() => $localeFallBackProperty,
                    $categoryType->getName()           => $categoryType,
                ],
                [
                    'form'             => [
                        new FormTypeValidatorExtension($validator),
                    ],
                    CategoryType::NAME => [
                        new CategoryFormExtension($this->registry),
                    ]
                ]
            )
        ];
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

    protected function setRegistryExpectations()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->getLocales()));

        /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('locale.parentLocale', 'parentLocale')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('locale.id', 'ASC')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('locale')
            ->will($this->returnValue($qb));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
    }

    protected function setRepositoryExpectations()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $er */
        $er = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $er->expects($this->once())
            ->method('findOneBy')
            ->willReturn($this->getCategoryVisibility());
        $er->expects($this->at(1))
            ->method('findBy')
            ->willReturn([$this->getAccountCategoryVisibility()]);
        $er->expects($this->at(2))
            ->method('findBy')
            ->willReturn([$this->getAccountGroupCategoryVisibility()]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->at(0))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($er);
        $em->expects($this->at(1))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($er);
        $em->expects($this->at(2))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($er);

        $this->registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($em);
        $this->registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($em);
        $this->registry->expects($this->at(2))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($em);
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        $en = $this->createLocale(1, 'en');
        $enUs = $this->createLocale(2, 'en_US', $en);
        $enCa = $this->createLocale(3, 'en_CA', $en);

        return [$en, $enUs, $enCa];
    }

    /**
     * @param int         $id
     * @param string      $code
     * @param Locale|null $parentLocale
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Locale
     */
    protected function createLocale($id, $code, $parentLocale = null)
    {
        $website = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Locale')
            ->disableOriginalConstructor()
            ->getMock();
        $website->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $website->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($code));
        $website->expects($this->any())
            ->method('getParentLocale')
            ->will($this->returnValue($parentLocale));

        return $website;
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
     * @return \PHPUnit_Framework_MockObject_MockObject|CategoryVisibility
     */
    protected function getCategoryVisibility()
    {
        $categoryVisibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility')
            ->setMethods(['getVisibility'])
            ->getMock();
        $categoryVisibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn(CategoryVisibility::HIDDEN);

        return $categoryVisibility;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountCategoryVisibility
     */
    protected function getAccountCategoryVisibility()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility')
            ->setMethods(['getVisibility', 'getAccount'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccount')
            ->willReturn($account);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn($this->getEnumValue());

        return $visibility;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountGroupCategoryVisibility
     */
    protected function getAccountGroupCategoryVisibility()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $accountGroup->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility')
            ->setMethods(['getVisibility', 'getAccountGroup'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccountGroup')
            ->willReturn($accountGroup);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn($this->getEnumValue());

        return $visibility;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractEnumValue
     */
    protected function getEnumValue()
    {
        $enumValue = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        $enumValue->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        return $enumValue;
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
