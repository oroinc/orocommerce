<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\TaxBundle\Form\Type\OriginAddressType;
use Oro\Bundle\TaxBundle\Model\Address;

class OriginAddressTypeTest extends AbstractAddressTestCase
{
    /** @var OriginAddressType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new OriginAddressType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass('Oro\Bundle\TaxBundle\Model\Address');

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tax_origin_address', $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals('Oro\Bundle\TaxBundle\Model\Address', $options['data_class']);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $country = new Country('US');

        return [
            'valid form' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'valid without region' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'valid without country' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => null,
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'valid without postal code' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return $this->formType;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge([$this->getValidatorExtension(true)], parent::getExtensions());
    }

    public function testFinishViewWithoutParent()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $formMock */
        $formMock = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishViewWithoutUseDefault()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $formMock */
        $formMock = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parent */
        $parent = $this->getMock('Symfony\Component\Form\FormInterface');
        $parent->expects($this->once())->method('has')->willReturn(false);

        $formMock->expects($this->once())->method('getParent')->willReturn($parent);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishView()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $formMock */
        $formMock = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $useDefault */
        $useDefault = $this->getMock('Symfony\Component\Form\FormInterface');
        $useDefault->expects($this->once())->method('getData')->willReturn(true);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parent */
        $parent = $this->getMock('Symfony\Component\Form\FormInterface');
        $parent->expects($this->once())->method('has')->willReturn(true);
        $parent->expects($this->once())->method('get')->willReturn($useDefault);

        $formMock->expects($this->once())->method('getParent')->willReturn($parent);

        $formView = new FormView();
        $child = new FormView();
        $formView->children[] = $child;
        $this->formType->finishView($formView, $formMock, []);
        $this->assertTrue($child->vars['use_parent_scope_value']);
    }
}
