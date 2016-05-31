<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

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
use Oro\Bundle\LocaleBundle\Form\Type\LocaleCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as EntityIdentifierTypeStub;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use OroB2B\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use OroB2B\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\ImageTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup';

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
            'OroB2B\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener'
        )
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider $visibilityChoicesProvider */
        $visibilityChoicesProvider = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::NAME => new EntityVisibilityType(
                        $postSetDataListener,
                        $visibilityChoicesProvider
                    ),
                    CategoryType::NAME => new CategoryType(),
                    EntityIdentifierType::NAME => new EntityIdentifierTypeStub([]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionType($registry),
                    LocalizedPropertyType::NAME => new LocalizedPropertyType(),
                    LocaleCollectionType::NAME => new LocaleCollectionTypeStub(),
                    DataChangesetType::NAME => new DataChangesetTypeStub(),
                    EntityChangesetType::NAME => new EntityChangesetTypeStub(),
                    OroRichTextType::NAME => new OroRichTextTypeStub(),
                    ImageType::NAME => new ImageTypeStub()
                ],
                [
                    CategoryType::NAME => [$this->categoryFormExtension],
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
            ['data_class' => 'OroB2B\Bundle\CatalogBundle\Entity\Category']
        );
        $this->assertTrue($form->has('visibility'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->categoryFormExtension->getExtendedType(), CategoryType::NAME);
    }
}
