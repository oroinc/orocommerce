<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Options;

class CodeGenerationOptions
{
    const NUMERIC_CODE_TYPE = 'numeric';
    const ALPHANUMERIC_CODE_TYPE = 'alphanumeric';
    const ALPHABETIC_CODE_TYPE = 'alphabetic';

    /**
     * @var int
     */
    protected $codeLength = 12;

    /**
     * @var string
     */
    protected $codeType = self::ALPHANUMERIC_CODE_TYPE;

    /**
     * @var string
     */
    protected $codePrefix;

    /**
     * @var string
     */
    protected $codeSuffix;

    /**
     * @var int
     */
    protected $dashesSequence;

    /**
     * @return int
     */
    public function getCodeLength()
    {
        return $this->codeLength;
    }

    /**
     * @param int $codeLength
     * @return CodeGenerationOptions
     */
    public function setCodeLength($codeLength)
    {
        $this->codeLength = (int)$codeLength;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeType()
    {
        return $this->codeType;
    }

    /**
     * @param string $codeType
     * @return CodeGenerationOptions
     */
    public function setCodeType($codeType)
    {
        $this->codeType = $codeType;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodePrefix()
    {
        return $this->codePrefix;
    }

    /**
     * @param string $codePrefix
     * @return CodeGenerationOptions
     */
    public function setCodePrefix($codePrefix)
    {
        $this->codePrefix = $codePrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeSuffix()
    {
        return $this->codeSuffix;
    }

    /**
     * @param string $codeSuffix
     * @return CodeGenerationOptions
     */
    public function setCodeSuffix($codeSuffix)
    {
        $this->codeSuffix = $codeSuffix;

        return $this;
    }

    /**
     * @return int
     */
    public function getDashesSequence()
    {
        return $this->dashesSequence;
    }

    /**
     * @param int $dashesSequence
     * @return CodeGenerationOptions
     */
    public function setDashesSequence($dashesSequence)
    {
        $this->dashesSequence = (int)$dashesSequence;

        return $this;
    }
}
