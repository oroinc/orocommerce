<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListRelationType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class PriceListRelationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var PriceListRelationType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new PriceListRelationType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => new EntityTypeStub([])
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmit()
    {
        $form = $this->factory->create(PriceListRelationType::class);
        $form->submit(['priceList' => 1]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $expectedData = [
            'priceList' => $this->getEntity(PriceList::class, ['id' => 1])
        ];
        $this->assertEquals($expectedData, $form->getData());
    }
}
