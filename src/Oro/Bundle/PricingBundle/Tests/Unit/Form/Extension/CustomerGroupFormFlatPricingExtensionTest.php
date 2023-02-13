<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFlatPricingRelationFormListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerGroupFormFlatPricingExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListRelationType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerGroupTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Validation;

class CustomerGroupFormFlatPricingExtensionTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $formExtension = new CustomerGroupFormFlatPricingExtension(
            $this->createMock(CustomerGroupFlatPricingRelationFormListener::class)
        );
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');

        return [
            new PreloadedExtension(
                [
                    new PriceListRelationType(),
                    (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType(),
                    CustomerGroupType::class => new CustomerGroupTypeStub(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => new EntityTypeStub()
                ],
                [
                    CustomerGroupTypeStub::class => [$formExtension]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    public function testGetExtendedTypes()
    {
        $this->assertSame([CustomerGroupType::class], CustomerGroupFormFlatPricingExtension::getExtendedTypes());
    }

    public function testBuildFormFeatureDisabled()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $listener = $this->createMock(CustomerGroupFlatPricingRelationFormListener::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('add');

        $formExtension = new CustomerGroupFormFlatPricingExtension($listener);
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');
        $formExtension->buildForm($builder, []);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(CustomerGroupType::class, [], []);
        $form->submit(['priceListsByWebsites' => $submitted]);
        $data = $form->get('priceListsByWebsites')->getData();
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $data);
    }

    public function submitDataProvider(): array
    {
        return [
            [
                'submitted' => [
                    1 => [
                        'priceList' => 1
                    ],
                ],
                'expected' => [
                    1 => [
                        'priceList' => $this->getPriceList(1)
                    ],
                ]
            ]
        ];
    }
}
