<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\WYSIWYG;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class WYSIWYGEncodingValidatorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOrganizationAndBusinessUnitData::class, LoadChannelData::class]);
    }

    /**
     * @dataProvider contentDataProvider
     */
    public function testValidateUTF8Output(string $content): void
    {
        $page = new Page();
        $page->setContent($content);
        $page->setOrganization(
            $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)
        );

        /** @var ConstraintViolation[] $errors */
        $errors = $this->getContainer()->get('validator')->validate($page);
        $this->assertNotEmpty($errors);

        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        foreach ($errors as $error) {
            $this->assertStringContainsString(
                'Please remove not permitted HTML-tags in the content field',
                $error->getMessage()
            );

            $status = new Status();
            $status->setCode(Status::STATUS_FAILED);
            $status->setMessage($error->getMessage());
            $status->setConnector('connector1');
            $status->setChannel($integration);

            // Check invalid byte sequence encoding during flush.
            $doctrineHelper->getEntityManager($status)->persist($status);
            $doctrineHelper->getEntityManager($status)->flush();
            // Get 'id' if the data is stored without encoding errors.
            $this->assertNotNull($status->getId());
        }
    }

    /**
     * @dataProvider contentDataProvider
     */
    public function testDetectEncoding(string $content): void
    {
        $page = new Page();
        $page->setContent($content);
        $page->setOrganization(
            $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)
        );

        /** @var ConstraintViolation[] $errors */
        $errors = $this->getContainer()->get('validator')->validate($page);
        $this->assertNotEmpty($errors);

        foreach ($errors as $error) {
            $this->assertStringContainsString(
                'Please remove not permitted HTML-tags in the content field',
                $error->getMessage()
            );

            $this->assertEquals('UTF-8', mb_detect_encoding($error->getMessage(), mb_detect_order(), true));
        }
    }

    public function contentDataProvider(): \Generator
    {
        yield ['<ul><li>Scherenbühne</li><br></ul>'];
        yield ['<ul><li>Zur Standardausrüstung</li><br></ul>'];
    }
}
