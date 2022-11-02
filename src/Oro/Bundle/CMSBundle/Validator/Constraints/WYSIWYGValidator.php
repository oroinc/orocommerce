<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Error;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This validator checks that WYSIWYG field does not have errors after purify.
 */
class WYSIWYGValidator extends ConstraintValidator
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var HTMLPurifierScopeProvider */
    private $purifierScopeProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        HtmlTagHelper $htmlTagHelper,
        HTMLPurifierScopeProvider $purifierScopeProvider,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->purifierScopeProvider = $purifierScopeProvider;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param WYSIWYG $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value) {
            return;
        }

        // Remove spaces between HTML tags to prevent code reorganizing errors
        $value = preg_replace(
            ['/\r\n/', '/(\>)\s+(\<)/m'],
            ['', '$1$2'],
            $value
        );

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

            $errors = array_map(
                function (Error $error) {
                    return $this->translator->trans(
                        'oro.htmlpurifier.formatted_error',
                        [
                            '{{ message }}' => $error->getMessage(),
                            '{{ place }}' => $error->getPlace(),
                        ]
                    );
                },
                $errorCollector->getErrorsList($value)
            );

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ errorsList }}', implode(';' . PHP_EOL, $errors))
                ->addViolation();
        }
    }

    /**
     * Get field name by property path
     */
    private function resolvePropertyPath(string $propertyPath): string
    {
        $fieldName = $propertyPath;
        // here we will have data.fieldName in case we validate the data against its own constraints
        if (str_starts_with($propertyPath, 'data.')) {
            [, $fieldName] = \explode('data.', $propertyPath, 2);
        }

        return $fieldName;
    }
}
