<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\CustomerFlatPricingRelationFormListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerFormFlatPricingExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListRelationType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerFormFlatPricingExtensionTest extends FormIntegrationTestCase
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

        $formExtension = new CustomerFormFlatPricingExtension(
            $this->createMock(CustomerFlatPricingRelationFormListener::class)
        );
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');

        return [
            new PreloadedExtension(
                [
                    new PriceListRelationType(),
                    (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType(),
                    CustomerType::class => new CustomerTypeStub(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => new EntityTypeStub()
                ],
                [
                    CustomerTypeStub::class => [$formExtension]
                ]
            ),
            $this->getValidatorExtension()
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
        $this->assertSame([CustomerType::class], CustomerFormFlatPricingExtension::getExtendedTypes());
    }

    public function testBuildFormFeatureDisabled()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $listener = $this->createMock(CustomerFlatPricingRelationFormListener::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('add');

        $formExtension = new CustomerFormFlatPricingExtension($listener);
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');
        $formExtension->buildForm($builder, []);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(CustomerType::class, [], []);
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
