<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Form\Type\SalesRepsCollectionType;

class SalesRepsCollectionTypeTest extends FormIntegrationTestCase
{
    const CLASS_USER_ENTITY = 'Oro\Bundle\UserBundle\Entity\User';
    /**
     * @var SalesRepsCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new SalesRepsCollectionType();
        $this->formType->setDataClass(self::CLASS_USER_ENTITY);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                    'required'  => false,
                    'class'  => self::CLASS_USER_ENTITY,
                    'property' => 'fullName',
                    'multiple' => true,
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_entity', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(SalesRepsCollectionType::NAME, $this->formType->getName());
    }
}
