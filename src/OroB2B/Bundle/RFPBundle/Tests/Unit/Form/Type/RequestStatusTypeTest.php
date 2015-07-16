<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusType;

class RequestStatusTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStatusType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.languages')
            ->willReturn([]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new RequestStatusType($configManager, $localeSettings);
        $this->type->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestStatus');
    }

    /**
     * Test buildForm
     */
    public function testBuildForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilderInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $optionsResolver */
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $optionsResolver->expects($this->once())
            ->method('setDefaults');

        $this->type->setDefaultOptions($optionsResolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestStatusType::NAME, $this->type->getName());
    }
}
