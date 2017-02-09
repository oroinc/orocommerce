<?php

namespace Oro\Bundle\FrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

class PageTemplateEntityFieldFallbackValueTransformer implements DataTransformerInterface
{
    /** @var string */
    private $routeName;

    /**
     * @param string $routeName
     */
    public function __construct($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value instanceof EntityFieldFallbackValue) {
            $value->setScalarValue([$this->routeName => $value->getScalarValue()]);
        }

        return $value;
    }
}
