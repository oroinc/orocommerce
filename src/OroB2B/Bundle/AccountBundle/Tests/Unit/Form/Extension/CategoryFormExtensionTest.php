<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as EntityIdentifierTypeStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use Oro\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;
use Oro\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\ImageTypeStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'Oro\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\AccountBundle\Entity\AccountGroup';

    /** @var CategoryFormExtension|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryFormExtension;

    protected function setUp()
    {
        $this->categoryFormExtension = new CategoryFormExtension();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        /** @var VisibilityPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject $postSetDataListener */
        $postSetDataListener = $this->getMockBuilder(
            'Oro\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener'
        )
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider $visibilityChoicesProvider */
        $visibilityChoicesProvider = $this
            ->getMockBuilder('Oro\Bundle\AccountBundle\Provider\VisibilityChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $defaultProductOptions = new CategoryDefaultProductOptionsType();
        $defaultProductOptions->setDataClass('Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions');

        $categoryUnitPrecision = new CategoryUnitPrecisionType();
        $categoryUnitPrecision->setDataClass('Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision');

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::NAME => new EntityVisibilityType(
                        $postSetDataListener,
                        $visibilityChoicesProvider
                    ),
                    CategoryType::NAME => new CategoryType(),
                    CategoryDefaultProductOptionsType::NAME => $defaultProductOptions,
                    CategoryUnitPrecisionType::NAME => $categoryUnitPrecision,
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ]
                    ),
                    EntityIdentifierType::NAME => new EntityIdentifierTypeStub([]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionType($registry),
                    LocalizedPropertyType::NAME => new LocalizedPropertyType(),
                    LocalizationCollectionType::NAME => new LocalizationCollectionTypeStub(),
                    DataChangesetType::NAME => new DataChangesetTypeStub(),
                    EntityChangesetType::NAME => new EntityChangesetTypeStub(),
                    OroRichTextType::NAME => new OroRichTextTypeStub(),
                    ImageType::NAME => new ImageTypeStub()
                ],
                [
                    CategoryType::NAME => [$this->categoryFormExtension],
                    'form' => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(
            CategoryType::NAME,
            new CategoryStub(),
            ['data_class' => 'Oro\Bundle\CatalogBundle\Entity\Category']
        );
        $this->assertTrue($form->has('visibility'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->categoryFormExtension->getExtendedType(), CategoryType::NAME);
    }
}
