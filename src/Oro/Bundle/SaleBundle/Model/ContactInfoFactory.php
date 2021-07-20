<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

class ContactInfoFactory implements ContactInfoFactoryInterface
{
    /**
     * @var NameFormatter
     */
    private $nameFormatter;

    public function __construct(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * {@inheritDoc}
     */
    public function createContactInfoByUser(User $user)
    {
        return $this->createContactInfo()
            ->setEmail($user->getEmail())
            ->setName($this->nameFormatter->format($user))
            ->setPhone($user->getPhone());
    }

    /**
     * {@inheritDoc}
     */
    public function createContactInfoWithText($text)
    {
        $contactInfo = $this->createContactInfo();
        if ($text) {
            $contactInfo->setManualText($text);
        }

        return $contactInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function createContactInfo()
    {
        return new ContactInfo();
    }
}
