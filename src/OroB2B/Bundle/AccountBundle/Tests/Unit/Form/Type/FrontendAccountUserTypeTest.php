<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;

use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendAccountUserTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountUserType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new FrontendAccountUserType();
    }

    public function testForm()
    {
        /** @var $resolver OptionsResolver|PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())->method('setDefaults')->with(['frontend' => true]);
        $this->formType->configureOptions($resolver);
        $this->assertEquals($this->formType->getParent(), AccountUserType::NAME);
        $this->assertEquals($this->formType->getName(), FrontendAccountUserType::NAME);
    }
}
