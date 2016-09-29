<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class BankData
{
    /**
     * @var string
     */
    protected $BNK_ACC_ID;

    /**
     * @var string
     */
    protected $BNK_ACC_OWNER;

    /**
     * @var string
     */
    protected $BNK_ACC_SWIFT;

    /**
     * @param string $BNK_ACC_ID
     * @param string $BNK_ACC_OWNER
     * @param string $BNK_ACC_SWIFT
     */
    public function __construct($BNK_ACC_ID = null, $BNK_ACC_OWNER = null, $BNK_ACC_SWIFT = null)
    {
        $this->BNK_ACC_ID = $BNK_ACC_ID;
        $this->BNK_ACC_OWNER = $BNK_ACC_OWNER;
        $this->BNK_ACC_SWIFT = $BNK_ACC_SWIFT;
    }

    /**
     * @return string
     */
    public function getBnkAccId()
    {
        return $this->BNK_ACC_ID;
    }

    /**
     * @param string $BNK_ACC_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\BankData
     */
    public function setBnkAccId($BNK_ACC_ID)
    {
        $this->BNK_ACC_ID = $BNK_ACC_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getBnkAccOwner()
    {
        return $this->BNK_ACC_OWNER;
    }

    /**
     * @param string $BNK_ACC_OWNER
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\BankData
     */
    public function setBnkAccOwner($BNK_ACC_OWNER)
    {
        $this->BNK_ACC_OWNER = $BNK_ACC_OWNER;

        return $this;
    }

    /**
     * @return string
     */
    public function getBnkAccSwift()
    {
        return $this->BNK_ACC_SWIFT;
    }

    /**
     * @param string $BNK_ACC_SWIFT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\BankData
     */
    public function setBnkAccSwift($BNK_ACC_SWIFT)
    {
        $this->BNK_ACC_SWIFT = $BNK_ACC_SWIFT;

        return $this;
    }
}
