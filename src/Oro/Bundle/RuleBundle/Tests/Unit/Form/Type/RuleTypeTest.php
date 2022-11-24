<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class RuleTypeTest extends FormIntegrationTestCase
{
    public function testGetBlockPrefix()
    {
        $formType = new RuleType();
        $this->assertEquals(RuleType::BLOCK_PREFIX, $formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmitValid(RuleInterface $rule)
    {
        $form = $this->factory->create(RuleType::class, $rule);

        $this->assertSame($rule, $form->getData());

        $form->submit([
            'name' => 'new rule',
            'sortOrder' => '1',
            'expression' => '2'
        ]);

        $newRule = (new Rule())
            ->setName('new rule')
            ->setSortOrder(1)
            ->setEnabled(false)
            ->setExpression('2')
            ->setCreatedAt($rule->getCreatedAt())
            ->setUpdatedAt($rule->getUpdatedAt());

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($newRule, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            [new Rule()],
            [
                (new Rule())
                    ->setName('old name')
                    ->setSortOrder(0)
            ],
        ];
    }
}
