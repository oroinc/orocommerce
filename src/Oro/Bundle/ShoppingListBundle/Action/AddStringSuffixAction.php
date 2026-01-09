<?php

namespace Oro\Bundle\ShoppingListBundle\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Action for appending a suffix to a string with optional length constraints.
 *
 * This action is used in shopping list workflows, particularly when duplicating shopping lists,
 * to generate new names by appending suffixes (like timestamps or copy indicators) to existing names.
 * It intelligently handles maximum length constraints by truncating the original string
 * and adding an ellipsis when necessary to ensure the final result fits within the specified length limit.
 * This ensures that duplicated shopping list names remain within database field constraints
 * while still being descriptive and unique.
 */
class AddStringSuffixAction extends AbstractAction
{
    public const ATTRIBUTE_OPTION = 'attribute';
    public const MAX_LENGTH_OPTION = 'maxLength';
    public const STRING_OPTION = 'string';
    public const STRING_SUFFIX_OPTION = 'stringSuffix';

    /**
     * @var array
     */
    protected $options;

    public function __construct(ContextAccessor $contextAccessor)
    {
        parent::__construct($contextAccessor);
    }

    #[\Override]
    protected function executeAction($context)
    {
        $title = $this->getOriginalString($context);
        $suffix = $this->getStringSuffix($context);
        $isRestrictByLengthRequired = array_key_exists(self::MAX_LENGTH_OPTION, $this->options);
        if ($isRestrictByLengthRequired) {
            $title = $this->cutTitle($title, $suffix);
        }
        $title = $title . $suffix;
        if ($isRestrictByLengthRequired) {
            $title = mb_substr($title, 0, $this->options[self::MAX_LENGTH_OPTION]);
        }

        $this->contextAccessor->setValue($context, $this->options[self::ATTRIBUTE_OPTION], $title);
    }

    /**
     * Allowed options:
     *  - attribute - contains property path used to save result string
     *  - string - string used to format, first argument of
     *  - stringSuffix - string that will be added to the tail of main string
     *  - maxLength - max result length
     *
     */
    #[\Override]
    public function initialize(array $options)
    {
        if (!array_key_exists(self::ATTRIBUTE_OPTION, $options)) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options[self::ATTRIBUTE_OPTION] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }
        if (!array_key_exists(self::STRING_OPTION, $options)) {
            throw new InvalidParameterException('String parameter must be specified');
        }
        if (!array_key_exists(self::STRING_SUFFIX_OPTION, $options)) {
            throw new InvalidParameterException('String suffix parameter must be specified');
        }
        $this->checkMaxLengthOption($options);

        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     *
     * @return string
     */
    private function getOriginalString($context)
    {
        return (string)$this->contextAccessor->getValue($context, $this->options[self::STRING_OPTION]);
    }

    /**
     * @param mixed $context
     *
     * @return string
     */
    private function getStringSuffix($context)
    {
        return (string)$this->contextAccessor->getValue($context, $this->options[self::STRING_SUFFIX_OPTION]);
    }

    /**
     * @param string $title
     * @param string $suffix
     *
     * @return string
     */
    private function cutTitle($title, $suffix)
    {
        $maxLength = $this->options[self::MAX_LENGTH_OPTION];
        $originalLength = mb_strlen($title);
        $suffixLength = mb_strlen($suffix);

        if ($suffixLength > $maxLength) {
            return $title;
        }
        $resultLength = $maxLength - $suffixLength;
        if ($resultLength < $originalLength) {
            $title = mb_substr($title, 0, $resultLength - 1) . '…'; // -1 for '…'
        }

        return $title;
    }

    /**
     * @throws InvalidParameterException
     */
    private function checkMaxLengthOption(array $options)
    {
        if (array_key_exists(self::MAX_LENGTH_OPTION, $options)) {
            if (!is_int($options[self::MAX_LENGTH_OPTION])) {
                throw new InvalidParameterException('Max length must be integer');
            }
            if ($options[self::MAX_LENGTH_OPTION] <= 0) {
                throw new InvalidParameterException('Max length must be positive');
            }
        }
    }
}
