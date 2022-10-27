<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class LoadContentTemplateData extends AbstractFixture implements DependentFixtureInterface
{
    public const CONTENT_TEMPLATE_1 = 'content_template.1';
    public const CONTENT_TEMPLATE_2 = 'content_template.2';
    public const CONTENT_TEMPLATE_3 = 'content_template.3';
    public const CONTENT_TEMPLATE_4 = 'content_template.4';
    public const CONTENT_TEMPLATE_TAG_1 = 'content_template_tag.1';
    public const CONTENT_TEMPLATE_TAG_2 = 'content_template_tag.2';
    public const CONTENT_TEMPLATE_TAG_3 = 'content_template_tag.3';
    public const CONTENT_TEMPLATE_TAG_4 = 'content_template_tag.4';

    private const CONTENT_TEMPLATES = [
        self::CONTENT_TEMPLATE_1 => [
            'enabled' => true,
            'createdAt' => '2022-01-01',
            'tags' => [
                self::CONTENT_TEMPLATE_TAG_1,
                self::CONTENT_TEMPLATE_TAG_2,
            ],
            'content' => '<div class="one-column"><h3>Marguerite Fox</h3><p class="extra-text">Position</p>'
                . '<img src="{{ wysiwyg_image(%DIGITAL_ASSET_1_CHILD_1%) }}" alt=""></div>',
            'contentStyle' => '.one-column .extra-text {padding: 20px 0px; background-image: '
                . 'url({{ wysiwyg_image(%DIGITAL_ASSET_1_CHILD_2%) }});}',
            'contentProperties' => ['propFoo' => 'valueFoo', 'propBar' => 'valueBar'],
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_2 => [
            'enabled' => true,
            'createdAt' => '2022-01-02',
            'tags' => [],
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_3 => [
            'enabled' => false,
            'createdAt' => '2022-01-03',
            'tags' => [],
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_4 => [
            'enabled' => true,
            'createdAt' => '2022-01-04',
            'tags' => [
                self::CONTENT_TEMPLATE_TAG_1,
                self::CONTENT_TEMPLATE_TAG_4,
            ],
            'content' => 'Owned by simple user',
            'owner' => LoadUserData::SIMPLE_USER,
        ],
    ];

    private const CONTENT_TEMPLATES_TAGS = [
        self::CONTENT_TEMPLATE_TAG_1 => [
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_TAG_2 => [
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_TAG_3 => [
            'owner' => LoadUser::USER,
        ],
        self::CONTENT_TEMPLATE_TAG_4 => [
            'owner' => LoadUserData::SIMPLE_USER,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $this->loadContentTemplates($manager);
        $this->loadContentTemplatesTags($manager);
        $this->loadTags($manager);
    }

    private function loadContentTemplates(ObjectManager $manager): void
    {
        $digitalAsset = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);
        /** @var File $file1 */
        $file1 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_1);
        /** @var File $file2 */
        $file2 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_2);

        $digitalAssetId = $digitalAsset->getId();
        $replacements = [
            '%DIGITAL_ASSET_1_CHILD_1%' => $digitalAssetId . ',"' . $file1->getUuid() . '","wysiwyg_original"',
            '%DIGITAL_ASSET_1_CHILD_2%' => $digitalAssetId . ',"' . $file2->getUuid() . '","wysiwyg_original"',
        ];
        foreach (self::CONTENT_TEMPLATES as $contentTemplateName => $contentTemplateData) {
            $contentTemplate = (new ContentTemplate())
                ->setName($contentTemplateName)
                ->setEnabled($contentTemplateData['enabled'])
                ->setCreatedAt(new \DateTime($contentTemplateData['createdAt'], new \DateTimeZone('UTC')))
                ->setOwner($this->getReference($contentTemplateData['owner']))
                ->setOrganization($this->getReference('organization'))
                ->setContent(strtr($contentTemplateData['content'] ?? '', $replacements))
                ->setContentStyle(strtr($contentTemplateData['contentStyle'] ?? '', $replacements))
                ->setContentProperties($contentTemplateData['contentProperties'] ?? null);

            $this->setReference($contentTemplateName, $contentTemplate);
            $manager->persist($contentTemplate);
        }

        $manager->flush();
    }

    private function loadContentTemplatesTags(ObjectManager $manager): void
    {
        foreach (self::CONTENT_TEMPLATES_TAGS as $name => $data) {
            $tag = (new Tag())
                ->setName($name)
                ->setOwner($this->getReference($data['owner']))
                ->setOrganization($this->getReference('organization'));

            $this->setReference($name, $tag);
            $manager->persist($tag);
        }

        $manager->flush();
    }

    private function loadTags(ObjectManager $manager): void
    {
        foreach (self::CONTENT_TEMPLATES as $contentTemplateName => $contentTemplateData) {
            foreach ($contentTemplateData['tags'] as $tag) {
                $tagging = new Tagging();
                $tagging->setTag($this->getReference($tag));
                $tagging->setEntity($this->getReference($contentTemplateName));

                $manager->persist($tagging);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadUser::class,
            LoadUserData::class,
            LoadDigitalAssetData::class,
        ];
    }
}
