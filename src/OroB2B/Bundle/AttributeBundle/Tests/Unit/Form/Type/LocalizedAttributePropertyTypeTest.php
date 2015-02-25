<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use OroB2B\Bundle\AttributeBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributePropertyFallbackType;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedAttributePropertyType;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub;

class LocalizedAttributePropertyTypeTest extends AbstractLocalizedType
{
    /**
     * @var LocalizedAttributePropertyType
     */
    protected $formType;

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

    public function testGetName()
    {
        $this->assertEquals(LocalizedAttributePropertyType::NAME, $this->formType->getName());
    }
}
