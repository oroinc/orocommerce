<?php

namespace Oro\Bundle\TaxBundle\Model;

class TaxCode implements TaxCodeInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $type;

    /**
     * @param string $code
     * @param string $type
     */
    public function __construct($code, $type)
    {
        if (!is_string($code) || !is_string($type)) {
            throw new \InvalidArgumentException('Strings required');
        }

        $this->code = $code;
        $this->type = $type;
    }

    /**
     * @param string $code
     * @param string $type
     * @return static
     */
    public static function create($code, $type)
    {
        return new static($code, $type);
    }

    /** {@inheritdoc} */
    public function getCode()
    {
        return $this->code;
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return $this->type;
    }
}
