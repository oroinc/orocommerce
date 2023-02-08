<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Oro\Bundle\CronBundle\Tests\Unit\Form\Type\Stub\ScheduleIntervalsCollectionTypeStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductCollectionSegmentTypeStub;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountConfigurationType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\ScopeStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class PromotionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(Product::class, false)
            ->willReturn($this->createMock(EntityManagerInterface::class));

        return [
            new PreloadedExtension(
                [
                    ScheduleIntervalsCollectionType::class => new ScheduleIntervalsCollectionTypeStub(),
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    ProductCollectionSegmentType::class => new ProductCollectionSegmentTypeStub(),
                    DiscountConfigurationType::class => new EntityTypeStub([
                        'order' => $this->getEntity(DiscountConfiguration::class, ['type' => 'order'])
                    ]),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(PromotionType::class);
        $this->assertTrue($form->has('rule'));
        $this->assertTrue($form->has('useCoupons'));
        $this->assertTrue($form->has('discountConfiguration'));
        $this->assertTrue($form->has('schedules'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('productsSegment'));
        $this->assertTrue($form->has('labels'));
        $this->assertTrue($form->has('descriptions'));
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(Promotion $defaultData, array $submittedData, Promotion $expectedData)
    {
        $form = $this->factory->create(PromotionType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
    {
        $promotion = new Promotion();
        $ruleName = 'some name';
        $ruleEnabled = true;
        $ruleSortOrder = 10;
        $ruleStopProcessing = true;
        $ruleExpression = 'some expression';
        $rule = (new Rule())
            ->setName($ruleName)
            ->setEnabled($ruleEnabled)
            ->setSortOrder($ruleSortOrder)
            ->setStopProcessing($ruleStopProcessing)
            ->setExpression($ruleExpression);
        $promotion->setRule($rule);

        $labelString = 'some label';
        $label = (new LocalizedFallbackValue())
            ->setString($labelString);
        $promotion->addLabel($label);
        $promotion->addScope((new ScopeStub())->setLocale('EN'));

        $descriptionString = 'some description';
        $description = (new LocalizedFallbackValue())
            ->setText($descriptionString);
        $promotion->addDescription($description);

        /** @var DiscountConfiguration $discountConfiguration */
        $discountConfiguration = $this->getEntity(DiscountConfiguration::class, ['type' => 'order']);
        $promotion->setDiscountConfiguration($discountConfiguration);
        $promotion->setProductsSegment((new Segment())->setName('some name'));

        $editedRuleEnabled = false;
        $editedRuleName = 'some new name';
        $editedRuleSortOrder = 15;
        $editedRuleStopProcessing = false;
        $editedRuleExpression = 'some new expression';
        $editedPromotion = clone $promotion;
        $editedRule = clone $rule;
        $editedRule->setEnabled($editedRuleEnabled);
        $editedRule->setName($editedRuleName);
        $editedRule->setSortOrder($editedRuleSortOrder);
        $editedRule->setStopProcessing($editedRuleStopProcessing);
        $editedRule->setExpression($editedRuleExpression);
        $editedPromotion->setRule($editedRule);

        return [
            'new promotion' => [
                'defaultData' => new Promotion(),
                'submittedData' => [
                    'rule' => [
                        'name' => $ruleName,
                        'enabled' => $ruleEnabled,
                        'sortOrder' => $ruleSortOrder,
                        'stopProcessing' => $ruleStopProcessing,
                        'expression' => $ruleExpression,
                    ],
                    'discountConfiguration' => 'order',
                    'productsSegment' => ['name' => 'some name'],
                    'labels' => [['string' => $labelString]],
                    'descriptions' => [['text' => $descriptionString]],
                    'scopes' => [
                        ['locale' => 'EN']
                    ]
                ],
                'expectedData' => $promotion,
            ],
            'edit promotion' => [
                'defaultData' => $promotion,
                'submittedData' => [
                    'rule' => [
                        'name' => $editedRuleName,
                        'enabled' => $editedRuleEnabled,
                        'sortOrder' => $editedRuleSortOrder,
                        'stopProcessing' => $editedRuleStopProcessing,
                        'expression' => $editedRuleExpression,
                    ],
                    'discountConfiguration' => 'order',
                    'scopes' => [
                        ['locale' => 'EN']
                    ]
                ],
                'expectedData' => $editedPromotion,
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Promotion::class]);

        $type = new PromotionType();
        $type->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $type = new PromotionType();
        $this->assertEquals(PromotionType::NAME, $type->getBlockPrefix());
    }
}
