<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestStatusType;

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
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.languages')
            ->willReturn([]);

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new RequestStatusType($configManager, $localeSettings);
        $this->type->setDataClass('OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus');
    }

    /**
     * Test buildForm
     */
    public function testBuildForm()
    {
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
