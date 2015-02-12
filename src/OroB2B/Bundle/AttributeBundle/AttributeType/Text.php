<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Alphanumeric;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Email;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Letters;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Url;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\UrlSafe;

class Text extends AbstractAttributeType
{
    const NAME = 'text';
    protected $dataTypeField = 'text';

    /**
     * {@inheritdoc}
     */
    public function getFormParameters(Attribute $attribute)
    {
        if ($attribute->isContainHtml()) {
            return [
                'type' => 'oro_rich_text'
            ];
        } else {
            return [
                'type' => 'textarea'
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isContainHtml()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedForSearch()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalConstraints()
    {
        return [
            new Letters(),
            new Alphanumeric(),
            new UrlSafe(),
            new Decimal(),
            new IntegerConstraint(),
            new Email(),
            new Url()
        ];
    }
}
