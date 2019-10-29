<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * This validator checks that WYSIWYG field does not have errors after purify.
 */
class WYSIWYGValidator extends ConstraintValidator
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var HTMLPurifierScopeProvider */
    private $purifierScopeProvider;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     * @param HTMLPurifierScopeProvider $purifierScopeProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        HtmlTagHelper $htmlTagHelper,
        HTMLPurifierScopeProvider $purifierScopeProvider,
        LoggerInterface $logger
    ) {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->purifierScopeProvider = $purifierScopeProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param WYSIWYG $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        // Remove spaces between HTML tags to prevent code reorganizing errors
        $value = preg_replace('/(\>)\s*(\<)/m', '$1$2', $value);
        $scope = null;

        $contextObject = $this->context->getObject();
        $className = $contextObject ? get_class($contextObject) : $this->context->getClassName();
        $fieldName = $this->context->getPropertyName();
        if (!$fieldName) {
            $fieldName = $this->resolvePropertyPath($this->context->getPropertyPath());
        }
        $scope = $this->purifierScopeProvider->getScope($className, $fieldName);

        if (!$scope) {
            return;
        }

        $this->htmlTagHelper->sanitize($value, $scope);
        $errorCollector = $this->htmlTagHelper->getLastErrorCollector();
        if ($errorCollector && $errorCollector->getRaw()) {
            /** @var array $errors */
            $errors = $errorCollector->getRaw();
            foreach ($errors as $error) {
                $this->logger->debug(sprintf(
                    'WYSIWYG validation error: %s',
                    $error[\HTMLPurifier_ErrorCollector::MESSAGE]
                ), [
                    'line' => $error[\HTMLPurifier_ErrorCollector::LINENO],
                    'severity' => $error[\HTMLPurifier_ErrorCollector::SEVERITY]
                ]);
            }

            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    /**
     * Get field name by property path
     *
     * @param string $propertyPath
     * @return string
     */
    private function resolvePropertyPath(string $propertyPath): string
    {
        $fieldName = $propertyPath;
        // here we will have data.fieldName in case we validate the data against its own constraints
        if (\strpos($propertyPath, 'data.') === 0) {
            list(, $fieldName) = \explode('data.', $propertyPath, 2);
        }

        return $fieldName;
    }
}
