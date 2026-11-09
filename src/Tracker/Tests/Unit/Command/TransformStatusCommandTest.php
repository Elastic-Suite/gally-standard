<?php

/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Tracker\Tests\Unit\Command;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Transform;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\SessionTransformProvisioner;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TransformStatusCommandTest extends AbstractTestCase
{
    private static LocalizedCatalog $localizedCatalog;

    private static string $transformId;

    private static TransformRepositoryInterface $transformRepository;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en');
        self::$transformId = SessionTransformProvisioner::TRANSFORM_ID_PREFIX . self::$localizedCatalog->getCode();
        self::$transformRepository = static::getContainer()->get(TransformRepositoryInterface::class);

        self::createEntityElasticsearchDataStream('tracking_event', self::$localizedCatalog->getId());
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        // --recreate acts on every localized catalog, so clean up the session indices and transforms of all of them.
        self::deleteEntityElasticsearchTransforms('tracking_session');
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        $indexSettings = static::getContainer()->get(IndexSettingsInterface::class);
        $indexOperation = static::getContainer()->get(IndexOperation::class);
        foreach (static::getContainer()->get(LocalizedCatalogRepository::class)->findAll() as $localizedCatalog) {
            $indexOperation->deleteIndicesByAlias($indexSettings->getIndexAliasFromIdentifier('tracking_session', $localizedCatalog));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        static::getContainer()->get(SessionTransformProvisioner::class)->createOrUpdate(self::$localizedCatalog);
    }

    public function testListingShowsAllTransforms(): void
    {
        $commandTester = $this->executeCommand([]);

        $this->assertStringContainsString(self::$transformId, $commandTester->getDisplay());
    }

    // A transform has no metadata until its first scheduled run, which the command reports as unhealthy.
    public function testListingFailsWhenATransformIsUnhealthy(): void
    {
        self::$transformRepository->stop(self::$transformId);

        $commandTester = $this->executeCommand([]);

        $this->assertStringContainsString(self::$transformId, $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testStartRestartsAStoppedTransform(): void
    {
        self::$transformRepository->stop(self::$transformId);

        $commandTester = $this->executeCommand(['--start' => 'tracking_session']);

        $this->assertStringContainsString('Started "' . self::$transformId . '"', $commandTester->getDisplay());
    }

    public function testRecreateRecreatesAnUnhealthyTransform(): void
    {
        self::$transformRepository->stop(self::$transformId);
        $scheduleStartTimeBefore = $this->getTransform()->getSchedule()['interval']['start_time'];
        sleep(1);

        $commandTester = $this->executeCommand(['--recreate' => 'tracking_session']);

        $this->assertStringContainsString('Recreated "' . self::$transformId . '"', $commandTester->getDisplay());
        $this->assertGreaterThan($scheduleStartTimeBefore, $this->getTransform()->getSchedule()['interval']['start_time']);
    }

    private function executeCommand(array $input): CommandTester
    {
        $command = (new Application(static::bootKernel()))->find('gally:index:transform-status');
        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester;
    }

    private function getTransform(): Transform
    {
        foreach (self::$transformRepository->findAll() as $transform) {
            if (self::$transformId === $transform->getId()) {
                return $transform;
            }
        }

        throw new \LogicException('Transform not found.');
    }
}
