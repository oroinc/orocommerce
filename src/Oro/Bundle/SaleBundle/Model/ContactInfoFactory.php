<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Factory for creating {@see ContactInfo} instances from various sources.
 *
 * Creates {@see ContactInfo} objects populated with data from user entities or manual text input,
 * providing a convenient way to instantiate and initialize contact information objects.
 */
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

    #[\Override]
    public function createContactInfoByUser(User $user)
    {
        return $this->createContactInfo()
            ->setEmail($user->getEmail())
            ->setName($this->nameFormatter->format($user))
            ->setPhone($user->getPhone());
    }

    #[\Override]
    public function createContactInfoWithText($text)
    {
        $contactInfo = $this->createContactInfo();
        if ($text) {
            $contactInfo->setManualText($text);
        }

        return $contactInfo;
    }

    #[\Override]
    public function createContactInfo()
    {
        return new ContactInfo();
    }
}
