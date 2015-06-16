<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType = new RequestProductType($translator);
    }

    public function testBuildForm()
    {
        /* @var $builder \PHPUnit_Framework_MockObject_MockBuilder|FormBuilder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder->expects($this->at(0))
            ->method('add')
            ->with('product', null, [
                'required'  => true,
                'label'     => 'orob2b.product.entity_label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('requestProductItems', RequestProductItemCollectionType::NAME, [
                'label'     => 'orob2b.rfpadmin.requestproductitem.entity_plural_label',
                'add_label' => 'orob2b.rfpadmin.requestproductitem.add_label',
                'required'  => false
            ])
            ->will($this->returnSelf())
        ;

        $this->formType->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct',
                'intention'     => 'rfp_admin_request_product',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductType::NAME, $this->formType->getName());
    }
}
