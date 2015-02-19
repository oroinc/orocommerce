<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AttributeBundle\AttributeType\Select;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Model\SharingType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadSelectAttributeWithOptions extends AbstractFixture
{
    const ATTRIBUTE_CODE = 'test_attribute_with_options';

    public static $options = [
        null => [
            10 => 'first_default',
            30 => 'third_default',
            20 => 'second_default',
        ],
        'en_US' => [
            10 => 'first_en_US',
            30 => 'third_en_US',
            20 => 'second_en_US',
        ],
        'en_CA' => [
            10 => 'first_en_CA',
            30 => 'third_en_CA',
            20 => 'second_en_CA',
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $attribute = new Attribute();
        $attribute->setCode(self::ATTRIBUTE_CODE)
            ->setType(Select::NAME)
            ->setSharingType(SharingType::GENERAL);

        foreach (self::$options as $localeCode => $options) {
            $locale = $localeCode ? $this->getLocaleByCode($manager, $localeCode) : null;
            foreach ($options as $order => $value) {
                $option = new AttributeOption();
                $option->setLocale($locale)
                    ->setValue($value)
                    ->setOrder($order);
                $attribute->addOption($option);
            }
        }

        $manager->persist($attribute);
        $manager->flush($attribute);
        $manager->clear();
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return Locale
     */
    protected function getLocaleByCode(EntityManager $manager, $code)
    {
        $locale = $manager->getRepository('OroB2BWebsiteBundle:Locale')->findOneBy(['code' => $code]);

        if (!$locale) {
            throw new \LogicException(sprintf('There is no locale with code "%s" .', $code));
        }

        return $locale;
    }
}
