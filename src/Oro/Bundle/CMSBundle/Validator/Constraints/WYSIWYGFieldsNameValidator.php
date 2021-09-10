<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Responsible for validation when creating WYSIWYG fields
 */
class WYSIWYGFieldsNameValidator extends ConstraintValidator
{
    /**
     * @param FieldConfigModel $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s supported only, %s given',
                    FieldConfigModel::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (WYSIWYGType::TYPE === $value->getType()) {
            //Check if additional WYSIWYG (_style, _properties) fields can be created
            $this->checkIfAdditionalFieldsCanBeCreated($value, $constraint);
        }

        //Check if additional WYSIWYG fields already exist
        $this->checkIfWYSIWYGAdditionalFieldsExists($value, $constraint);
    }

    private function checkIfAdditionalFieldsCanBeCreated(
        FieldConfigModel $fieldConfigModel,
        Constraint $constraint
    ): void {
        $additionalFields = $this->generateAdditionalProperty($fieldConfigModel);
        $entityConfigModel = $fieldConfigModel->getEntity();
        $fields = $entityConfigModel->getFields(function (FieldConfigModel $configModel) use ($additionalFields) {
            return in_array($configModel->getFieldName(), $additionalFields);
        });

        if ($fields->count()) {
            $this->addViolation($constraint);
        }
    }

    /**
     * Additional validation.
     *
     * WYSIWYG field do not have schema properties, `field_properties`, `field_style` field config model and don't have.
     * Need to generate additional property dynamically and check it.
     */
    private function checkIfWYSIWYGAdditionalFieldsExists(
        FieldConfigModel $fieldConfigModel,
        Constraint $constraint
    ): void {
        $entityConfigModel = $fieldConfigModel->getEntity();
        $fields = $entityConfigModel->getFields(function (FieldConfigModel $configModel) use ($fieldConfigModel) {
            if (WYSIWYGType::TYPE === $configModel->getType()) {
                $additionalFields = $this->generateAdditionalProperty($configModel);

                return in_array($fieldConfigModel->getFieldName(), $additionalFields);
            }

            return false;
        });

        if ($fields->count()) {
            $this->addViolation($constraint);
        }
    }

    private function addViolation(Constraint $constraint): void
    {
        $this->context
            ->buildViolation($constraint->message)
            ->atPath('fieldName')
            ->addViolation();
    }

    private function generateAdditionalProperty(FieldConfigModel $fieldConfigModel): array
    {
        return [
            $fieldConfigModel->getFieldName() . WYSIWYGPropertiesType::TYPE_SUFFIX,
            $fieldConfigModel->getFieldName() . WYSIWYGStyleType::TYPE_SUFFIX,
        ];
    }
}
