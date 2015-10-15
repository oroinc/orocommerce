<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as EntityIdentifierTypeStub;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType;
use OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup';
    const CATEGORY_VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility';

    /** @var CategoryPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryPostSetDataListener;

    /** @var CategoryPostSubmitListener|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryPostSubmitListener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider */
    protected $visibilityChoicesProvider;

    /** @var CategoryFormExtension|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryFormExtension;

    protected function setUp()
    {
        $this->categoryPostSetDataListener = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryPostSubmitListener = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityChoicesProvider = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFormExtension = new CategoryFormExtension(
            $this->categoryPostSetDataListener,
            $this->categoryPostSubmitListener,
            $this->visibilityChoicesProvider
        );
        $this->categoryFormExtension->setAccountGroupClass(self::ACCOUNT_GROUP_CLASS);
        $this->categoryFormExtension->setAccountClass(self::ACCOUNT_CLASS);
        $this->categoryFormExtension->setCategoryVisibilityClass(self::CATEGORY_VISIBILITY_CLASS);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        return [
            new PreloadedExtension(
                [
                    CategoryType::NAME => new CategoryType(),
                    EntityIdentifierType::NAME => new EntityIdentifierTypeStub([]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionType($registry),
                    LocalizedPropertyType::NAME => new LocalizedPropertyType(),
                    LocaleCollectionType::NAME => new LocaleCollectionTypeStub(),
                    EntityChangesetType::NAME => new EntityChangesetTypeStub()
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
        $this->visibilityChoicesProvider->expects($this->once())->method('getFormattedChoices')->willReturn([]);

        $form = $this->factory->create(CategoryType::NAME);
        $this->assertTrue($form->has('categoryVisibility'));
        $this->assertTrue($form->has('visibilityForAccount'));
        $this->assertTrue($form->has('visibilityForAccountGroup'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->categoryFormExtension->getExtendedType(), CategoryType::NAME);
    }
}
