<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TaxBundle\Form\Type\OriginAddressType;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginAddressTypeTest extends AbstractAddressTestCase
{
    /** @var OriginAddressType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new OriginAddressType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass('Oro\Bundle\TaxBundle\Model\Address');

        parent::setUp();
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
        list($country, $region) = $this->getValidCountryAndRegion();

        return [
            'valid form' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => $region,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004_stripped',
                ],
            ],
            'valid without region' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITHOUT_REGION,
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => new Country(self::COUNTRY_WITHOUT_REGION),
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004_stripped',
                ],
            ],
            'valid without country' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => null,
                    'region' => self::REGION_WITH_COUNTRY,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => $region,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004_stripped',
                ],
            ],
            'valid without postal code' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => $region,
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormTypeClass()
    {
        return OriginAddressType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge([
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ], parent::getExtensions());
    }

    public function testFinishViewWithoutParent()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishViewWithoutUseDefault()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $parent */
        $parent = $this->createMock('Symfony\Component\Form\FormInterface');
        $parent->expects($this->once())->method('has')->willReturn(false);

        $formMock->expects($this->once())->method('getParent')->willReturn($parent);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishView()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $formMock */
        $formMock = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $useDefault */
        $useDefault = $this->createMock('Symfony\Component\Form\FormInterface');
        $useDefault->expects($this->once())->method('getData')->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $parent */
        $parent = $this->createMock('Symfony\Component\Form\FormInterface');
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
