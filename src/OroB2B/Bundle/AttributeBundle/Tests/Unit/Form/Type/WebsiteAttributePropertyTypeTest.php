<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributePropertyFallbackType;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Form\Type\WebsiteAttributePropertyType;
use OroB2B\Bundle\AttributeBundle\Form\Type\WebsiteCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\CheckboxTypeStub;

class WebsiteAttributePropertyTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WebsiteAttributePropertyType
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

        $this->formType = new WebsiteAttributePropertyType();
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
                    WebsiteCollectionType::NAME => new WebsiteCollectionType($this->registry),
                    CheckboxTypeStub::NAME => new CheckboxTypeStub(),
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
        $this->assertTrue($form->isValid());
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
                    WebsiteAttributePropertyType::FIELD_DEFAULT => null,
                    WebsiteAttributePropertyType::FIELD_WEBSITES => [
                        1 => new FallbackType(FallbackType::SYSTEM),
                        2 => new FallbackType(FallbackType::SYSTEM),
                        3 => new FallbackType(FallbackType::SYSTEM),
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
            'checkbox with full data' => [
                'options' => ['type' => CheckboxTypeStub::NAME, 'options' => ['value' => 't']],
                'defaultData' => [
                    null => true,
                    1    => false,
                    2    => new FallbackType(FallbackType::SYSTEM),
                    3    => new FallbackType(FallbackType::SYSTEM),
                ],
                'viewData' => [
                    WebsiteAttributePropertyType::FIELD_DEFAULT => 't',
                    WebsiteAttributePropertyType::FIELD_WEBSITES => [
                        1 => '',
                        2 => new FallbackType(FallbackType::SYSTEM),
                        3 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
                'submittedData' => [
                    WebsiteAttributePropertyType::FIELD_DEFAULT => 't',
                    WebsiteAttributePropertyType::FIELD_WEBSITES => [
                        1 => ['fallback' => FallbackType::SYSTEM],
                        2 => ['fallback' => ''],
                        3 => ['fallback' => FallbackType::SYSTEM],
                    ]
                ],
                'expectedData' => [
                    null => true,
                    1    => new FallbackType(FallbackType::SYSTEM),
                    2    => false,
                    3    => new FallbackType(FallbackType::SYSTEM),
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
            ->will($this->returnValue($this->getWebsites()));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('website.id', 'ASC')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('website')
            ->will($this->returnValue($queryBuilder));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BWebsiteBundle:Website')
            ->will($this->returnValue($repository));
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        $first  = $this->createWebsite(1, 'first');
        $second = $this->createWebsite(2, 'second');
        $third  = $this->createWebsite(3, 'third');

        return [$first, $second, $third];
    }

    /**
     * @param int $id
     * @param string $name
     * @return Website
     */
    protected function createWebsite($id, $name)
    {
        $website = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->disableOriginalConstructor()
            ->getMock();
        $website->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $website->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $website;
    }

    public function testGetName()
    {
        $this->assertEquals(WebsiteAttributePropertyType::NAME, $this->formType->getName());
    }
}
