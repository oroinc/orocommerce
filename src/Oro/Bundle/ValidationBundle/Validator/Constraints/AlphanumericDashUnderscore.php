<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * The constraint can be used to validate that a value contains only latin letters, numbers and symbols "-" or "_"
 */
class AlphanumericDashUnderscore extends Regex implements AliasAwareConstraintInterface
{
    public const ALIAS = 'alphanumeric_dash_underscore';

    public $message = 'This value should contain only latin letters, numbers and symbols "-" or "_".';
    public $pattern = '/^[-_a-zA-Z0-9]*$/';

    public function __construct(
        $pattern = null,
        ?string $message = null,
        ?string $htmlPattern = null,
        ?bool $match = null,
        ?callable $normalizer = null,
        ?array $groups = null,
        $payload = null,
        array $options = []
    ) {
        $pattern = $pattern ?? ['pattern' => $this->pattern];

        parent::__construct(
            $pattern,
            $message,
            $htmlPattern,
            $match,
            $normalizer,
            $groups,
            $payload,
            $options
        );
    }

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return null;
    }

    #[\Override]
    public function getRequiredOptions(): array
    {
        return [];
    }

    #[\Override]
    public function validatedBy(): string
    {
        return RegexValidator::class;
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
