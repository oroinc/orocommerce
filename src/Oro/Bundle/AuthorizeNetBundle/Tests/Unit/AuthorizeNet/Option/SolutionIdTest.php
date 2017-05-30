<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class SolutionIdTest extends AbstractOptionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [new Option\SolutionId()];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionDataProvider()
    {
        return [
            'wrong_type' => [
                ['solution_id' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "solution_id" with value 12345 is expected to be of type "string", but is of type '.
                    '"integer".',
                ],
            ],
            'valid' => [
                ['solution_id' => 'AAA000001'],
                ['solution_id' => 'AAA000001'],
            ],
        ];
    }
}
