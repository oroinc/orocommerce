<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSet;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\AccountBundle\Form\Extension\CategoryFormExtension;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup';

    /** @var CategoryPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryPostSetDataListener;

    /** @var CategoryPostSubmitListener|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryPostSubmitListener;

    /** @var  CategoryFormExtension|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->categoryFormExtension = new CategoryFormExtension(
            $this->categoryPostSetDataListener,
            $this->categoryPostSubmitListener
        );
        $this->categoryFormExtension->setAccountGroupClass(self::ACCOUNT_GROUP_CLASS);
        $this->categoryFormExtension->setAccountClass(self::ACCOUNT_CLASS);
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
                    'required' => true,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
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
                    'class' => self::ACCOUNT_CLASS,
                    'constraints' => [new VisibilityChangeSet(['entityClass' => self::ACCOUNT_CLASS])]
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => self::ACCOUNT_GROUP_CLASS,
                    'constraints' => [new VisibilityChangeSet(['entityClass' => self::ACCOUNT_GROUP_CLASS])]
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->at(3))
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SET_DATA,
                [$this->categoryPostSetDataListener, 'onPostSetData']
            )
            ->willReturn($builder);

        $builder->expects($this->at(4))
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SUBMIT,
                [$this->categoryPostSubmitListener, 'onPostSubmit']
            )
            ->willReturn($builder);

        $this->categoryFormExtension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->categoryFormExtension->getExtendedType(), CategoryType::NAME);
    }
}
