<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\TaxBundle\Form\Type\OriginAddressType;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginAddressTypeTest extends AbstractAddressTestCase
{
    private OriginAddressType $formType;

    protected function setUp(): void
    {
        $this->formType = new OriginAddressType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(Address::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals(Address::class, $options['data_class']);
    }

    /**
     * {@inheritDoc}
     */
    public function submitDataProvider(): array
    {
        [$country, $region] = $this->getValidCountryAndRegion();

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
     * {@inheritDoc}
     */
    protected function getFormTypeClass(): string
    {
        return OriginAddressType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge([
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ], parent::getExtensions());
    }

    public function testFinishViewWithoutParent()
    {
        $formMock = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishViewWithoutUseDefault()
    {
        $formMock = $this->createMock(FormInterface::class);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $formMock->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishView()
    {
        $formMock = $this->createMock(FormInterface::class);

        $useDefault = $this->createMock(FormInterface::class);
        $useDefault->expects($this->once())
            ->method('getData')
            ->willReturn(true);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects($this->once())
            ->method('has')
            ->willReturn(true);
        $parent->expects($this->once())
            ->method('get')
            ->willReturn($useDefault);

        $formMock->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);

        $formView = new FormView();
        $child = new FormView();
        $formView->children[] = $child;
        $this->formType->finishView($formView, $formMock, []);
        $this->assertTrue($child->vars['use_parent_scope_value']);
    }
}
