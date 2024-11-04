<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Constraint to check is url safe.
 */
class UrlSafe extends Regex implements AliasAwareConstraintInterface
{
    const ALIAS = 'url_safe';
    const DELIMITER = SluggableUrlGenerator::CONTEXT_DELIMITER;

    /** @var string */
    public $message = 'This value should contain only latin letters, numbers and symbols "-._~".';

    public string $delimiterMessage = 'This value should not contain reserved keyword "' . self::DELIMITER . '"';

    /** @var string */
    public $pattern = '/^[a-zA-Z0-9\-\.\_\~]*$/';

    public function __construct($options = null)
    {
        if ($options['allowSlashes'] ?? false) {
            $options['pattern'] = '/(?=[^\/])^[a-zA-Z0-9\-\.\_\~\/]*[^\/]$/';
            $options['message'] = 'This value should not start or end with "/" and should contain only latin letters, '
                . 'numbers and symbols "-._~/".';
        }

        unset($options['allowSlashes']);

        $options['pattern'] = $options['pattern'] ?? $this->pattern;

        parent::__construct($options);
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
        return UrlSafeValidator::class;
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
