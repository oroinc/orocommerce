<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    /** @var CategoryPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryPostSetDataListener;

    /** @var  CategoryFormExtension|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryFormExtension;

    protected function setUp()
    {
        $this->categoryPostSetDataListener = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFormExtension = new CategoryFormExtension($this->categoryPostSetDataListener);
    }

    public function testBuildForm()
    {
        /** @var  FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'categoryVisibility',
                'oro_enum_select',
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
                        'placeholder' => 'orob2b.account.categoryvisibility.default.label',
                    ],
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'visibilityForAccount',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\Account',
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup',
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SET_DATA,
                [$this->categoryPostSetDataListener, 'onPostSetData']
            )
            ->willReturn($builder);

        $this->categoryFormExtension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->categoryFormExtension->getExtendedType(), CategoryType::NAME);
    }
}
