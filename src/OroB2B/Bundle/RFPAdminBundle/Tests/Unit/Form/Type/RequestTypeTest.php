<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductCollectionType;

class RequestTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new RequestType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder->expects($this->at(0))
            ->method('add')
            ->with('requestProducts', RequestProductCollectionType::NAME, [
                'label'     => 'orob2b.rfpadmin.requestproduct.entity_plural_label',
                'add_label' => 'orob2b.rfpadmin.requestproduct.add_label',
                'required'  => false,
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
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\RFPAdminBundle\Entity\Request',
                    'intention' => 'rfp_admin_request',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_rfp_admin_request', $this->type->getName());
    }
}
