<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

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

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     * @param LoggerInterface $logger
     */
    public function __construct(HtmlTagHelper $htmlTagHelper, LoggerInterface $logger)
    {
        $this->htmlTagHelper = $htmlTagHelper;
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

        $this->htmlTagHelper->sanitize($value, WYSIWYG::HTML_PURIFIER_SCOPE);
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
}
