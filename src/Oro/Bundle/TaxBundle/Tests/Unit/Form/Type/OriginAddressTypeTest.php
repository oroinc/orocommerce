<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\TaxBundle\Form\Type\OriginAddressType;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginAddressTypeTest extends AddressFormExtensionTestCase
{
    private OriginAddressType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new OriginAddressType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(Address::class);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return array_merge([
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ], parent::getExtensions());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        self::assertArrayHasKey('data_class', $options);
        self::assertEquals(Address::class, $options['data_class']);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        bool $isValid,
        mixed $defaultData,
        mixed $viewData,
        array $submittedData,
        array $expectedData
    ): void {
        $form = $this->factory->create(OriginAddressType::class, $defaultData);

        self::assertEquals($defaultData, $form->getData());
        self::assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        self::assertEquals($isValid, $form->isValid());

        foreach ($expectedData as $field => $data) {
            self::assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            self::assertEquals($data, $fieldForm->getData());
        }
    }

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

    public function testFinishViewWithoutParent(): void
    {
        $formMock = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishViewWithoutUseDefault(): void
    {
        $formMock = $this->createMock(FormInterface::class);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects(self::once())
            ->method('has')
            ->willReturn(false);

        $formMock->expects(self::once())
            ->method('getParent')
            ->willReturn($parent);

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, []);
    }

    public function testFinishView(): void
    {
        $formMock = $this->createMock(FormInterface::class);

        $useDefault = $this->createMock(FormInterface::class);
        $useDefault->expects(self::once())
            ->method('getData')
            ->willReturn(true);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects(self::once())
            ->method('has')
            ->willReturn(true);
        $parent->expects(self::once())
            ->method('get')
            ->willReturn($useDefault);

        $formMock->expects(self::once())
            ->method('getParent')
            ->willReturn($parent);

        $formView = new FormView();
        $child = new FormView();
        $formView->children[] = $child;
        $this->formType->finishView($formView, $formMock, []);
        self::assertTrue($child->vars['use_parent_scope_value']);
    }
}
