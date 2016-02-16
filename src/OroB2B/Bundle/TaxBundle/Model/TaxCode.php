<?php

namespace OroB2B\Bundle\TaxBundle\Model;

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
        $this->code = (string)$code;
        $this->type = (string)$type;
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
