<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleRelationExpressions extends Constraint
{
    const ALIAS = 'oro_pricing.validator_constraints.price_rule_relation_expressions_validator';

    /**
     * @var string
     */
    public $messageFieldIsNotAllowed = 'oro.pricing.validators.field_is_not_allowed_as.message';

    /**
     * @var string
     */
    public $messageOnlyOneExpressionAllowed = 'oro.pricing.validators.one_expression_allowed.message';

    /**
     * @var string
     */
    public $messageRelationNotUsedInRule = 'oro.pricing.validators.relation_not_in_rule.message';

    /**
     * @var string
     */
    public $messageOnlyPriceRelationAllowed = 'oro.pricing.validators.only_price_relations_available.message';

    /**
     * @var string
     */
    public $messageTooManyRelations = 'oro.pricing.validators.too_many_relations.message';

    /**
     * @return string
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
