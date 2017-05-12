<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class SolutionId implements OptionInterface
{
    const SOLUTION_ID = 'solution_id';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SolutionId::SOLUTION_ID)
            ->addAllowedTypes(SolutionId::SOLUTION_ID, 'string');
    }
}
