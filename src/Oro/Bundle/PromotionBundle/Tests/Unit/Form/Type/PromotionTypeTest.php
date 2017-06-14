<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CronBundle\Tests\Unit\Form\Type\Stub\ScheduleIntervalsCollectionTypeStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PromotionType
     */
    private $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->type = new PromotionType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    new RuleType(),
                    new ScheduleIntervalsCollectionTypeStub(),
                    new ScopeCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionTypeStub(),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $this->markTestSkipped(
            'Remove after BB-10092, for now it is no needed to add stub for temporary solution with EntityType'
        );
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('rule'));
        $this->assertTrue($form->has('useCoupons'));
        $this->assertTrue($form->has('schedules'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('productsSegment'));
        $this->assertTrue($form->has('labels'));
        $this->assertTrue($form->has('descriptions'));
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $submittedData
     * @param Promotion $expectedData
     */
    public function testSubmit(array $submittedData, Promotion $expectedData)
    {
        $this->markTestSkipped(
            'Remove after BB-10092, for now it is no needed to add stub for temporary solution with EntityType'
        );

        $defaultData = new Promotion();

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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

        $useCoupons = true;
        $promotion->setUseCoupons($useCoupons);

        $labelString = 'some label';
        $label = (new LocalizedFallbackValue())
            ->setString($labelString);
        $promotion->addLabel($label);

        $descriptionString = 'some description';
        $description = (new LocalizedFallbackValue())
            ->setText($descriptionString);
        $promotion->addDescription($description);

        return [
            'new promotion' => [
                'submittedData' => [
                    'rule' => [
                        'name' => $ruleName,
                        'enabled' => $ruleEnabled,
                        'sortOrder' => $ruleSortOrder,
                        'stopProcessing' => $ruleStopProcessing,
                        'expression' => $ruleExpression,
                    ],
                    'useCoupons' => $useCoupons,
                    'labels' => [['string' => $labelString]],
                    'descriptions' => [['text' => $descriptionString]],
                ],
                'expectedData' => $promotion,
            ]
        ];
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => Promotion::class
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(PromotionType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PromotionType::NAME, $this->type->getBlockPrefix());
    }
}
