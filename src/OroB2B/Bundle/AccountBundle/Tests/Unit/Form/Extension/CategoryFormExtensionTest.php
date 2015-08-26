<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';
    const LOCALE_CLASS = 'OroB2B\Bundle\WebsiteBundle\Entity\Locale';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $oroEnumSelect = new EnumSelectType([]);

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

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

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
                    $oroEnumSelect->getName() => $oroEnumSelect,
                    $entityChangeSetType->getName() => $entityChangeSetType,
                    $oroIdentifierType->getName() => $oroIdentifierType,
                    $localizedFallbackType->getName() => $localizedFallbackType,
                    $localizedPropertyType->getName() => $localizedPropertyType,
                    $localeCollectionType->getName() => $localeCollectionType,
                    $localeFallbackValue->getName() => $localeFallbackValue,
                    $localeFallBackProperty->getName() => $localeFallBackProperty,
                    $categoryType->getName() => $categoryType,
                ],
                [
                    'form' => [
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

        $form = $this->factory->create(CategoryType::NAME);

        $this->assertTrue($form->has('categoryVisibility'));
        $this->assertTrue($form->has('visibilityForAccount'));
        $this->assertTrue($form->has('visibilityForAccountGroup'));
    }

    public function testSubmit()
    {
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryType::NAME, (new CategoryType())->getName());
    }

    /**
     * @return ManagerRegistry
     */
    protected function setRegistryExpectations()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->getLocales()));

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

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('locale')
            ->will($this->returnValue($qb));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::LOCALE_CLASS)
            ->will($this->returnValue($repository));
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        $en   = $this->createLocale(1, 'en');
        $enUs = $this->createLocale(2, 'en_US', $en);
        $enCa = $this->createLocale(3, 'en_CA', $en);

        return [$en, $enUs, $enCa];
    }

    /**
     * @param int $id
     * @param string $code
     * @param Locale|null $parentLocale
     * @return Locale
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
}
