<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestProductType
     */
    protected $type;

    protected function setUp()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->type = new RequestProductType($translator);
    }

    public function testBuildForm()
    {
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

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct',
                'intention'     => 'rfp_admin_request_product',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_rfp_admin_request_product', $this->type->getName());
    }
}
