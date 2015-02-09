<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributePropertyFallbackType;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedAttributePropertyType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocalizedAttributePropertyTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizedAttributePropertyType
     */
    protected $formType;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        parent::setUp();

        $this->formType = new LocalizedAttributePropertyType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    AttributePropertyFallbackType::NAME => new AttributePropertyFallbackType(),
                    FallbackValueType::NAME => new FallbackValueType(),
                    LocaleCollectionType::NAME => new LocaleCollectionType($this->registry),
                    PercentTypeStub::NAME => new PercentTypeStub(),
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $viewData, $submittedData, $expectedData)
    {
        $this->setRegistryExpectations();

        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        foreach ($viewData as $field => $data) {
            $this->assertEquals($data, $form->get($field)->getViewData());
        }

        $form->submit($submittedData);
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'text with null data' => [
                'options' => ['type' => 'text'],
                'defaultData' => null,
                'viewData' => [
                    LocalizedAttributePropertyType::FIELD_DEFAULT => null,
                    LocalizedAttributePropertyType::FIELD_LOCALES => [
                        1 => new FallbackType(FallbackType::SYSTEM),
                        2 => new FallbackType(FallbackType::PARENT_LOCALE),
                        3 => new FallbackType(FallbackType::PARENT_LOCALE),
                    ]
                ],
                'submittedData' => null,
                'expectedData' => [
                    null => null,
                    1    => null,
                    2    => null,
                    3    => null,
                ],
            ],
            'percent with full data' => [
                'options' => ['type' => PercentTypeStub::NAME, 'options' => ['type' => 'integer']],
                'defaultData' => [
                    null => 5,
                    1    => 10,
                    2    => new FallbackType(FallbackType::SYSTEM),
                    3    => new FallbackType(FallbackType::PARENT_LOCALE),
                ],
                'viewData' => [
                    LocalizedAttributePropertyType::FIELD_DEFAULT => 5,
                    LocalizedAttributePropertyType::FIELD_LOCALES => [
                        1 => 10,
                        2 => new FallbackType(FallbackType::SYSTEM),
                        3 => new FallbackType(FallbackType::PARENT_LOCALE),
                    ]
                ],
                'submittedData' => [
                    LocalizedAttributePropertyType::FIELD_DEFAULT => '10',
                    LocalizedAttributePropertyType::FIELD_LOCALES => [
                        1 => ['value' => '', 'fallback' => FallbackType::SYSTEM],
                        2 => ['value' => '5', 'fallback' => ''],
                        3 => ['value' => '', 'fallback' => FallbackType::PARENT_LOCALE],
                    ]
                ],
                'expectedData' => [
                    null => 10,
                    1    => new FallbackType(FallbackType::SYSTEM),
                    2    => 5,
                    3    => new FallbackType(FallbackType::PARENT_LOCALE),
                ],
            ],
        ];
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

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('locale.parentLocale', 'parentLocale')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('locale.id', 'ASC')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('locale')
            ->will($this->returnValue($queryBuilder));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BWebsiteBundle:Locale')
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

    public function testGetName()
    {
        $this->assertEquals(LocalizedAttributePropertyType::NAME, $this->formType->getName());
    }
}
