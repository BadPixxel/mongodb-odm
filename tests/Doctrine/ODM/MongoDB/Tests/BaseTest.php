<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\UnitOfWork;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /** @var DocumentManager */
    protected $dm;
    /** @var UnitOfWork */
    protected $uow;

    public function setUp()
    {
        $this->dm = $this->createTestDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
    }

    public function tearDown()
    {
        if ( ! $this->dm) {
            return;
        }

        $collections = $this->dm->getConnection()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->listCollections();

        foreach ($collections as $collection) {
            $collection->drop();
        }
    }

    protected function getConfiguration()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setMetadataDriverImpl($this->createMetadataDriverImpl());

        $config->addFilter('testFilter', 'Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter');
        $config->addFilter('testFilter2', 'Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter');

        return $config;
    }

    protected function createMetadataDriverImpl()
    {
        return AnnotationDriver::create(__DIR__ . '/../../../../Documents');
    }

    protected function createTestDocumentManager()
    {
        $config = $this->getConfiguration();
        $conn = new Connection(
            getenv("DOCTRINE_MONGODB_SERVER") ?: DOCTRINE_MONGODB_SERVER,
            array(),
            $config
        );

        return DocumentManager::create($conn, $config);
    }

    protected function getServerVersion()
    {
        $result = $this->dm->getConnection()->selectDatabase(DOCTRINE_MONGODB_DATABASE)->command(array('buildInfo' => 1));

        return $result['version'];
    }

    protected function skipTestIfNotSharded($className)
    {
        $result = $this->dm->getDocumentDatabase($className)->command(['listCommands' => true]);
        if (!$result['ok']) {
            $this->markTestSkipped('Could not check whether server supports sharding');
        }

        if (!array_key_exists('shardCollection', $result['commands'])) {
            $this->markTestSkipped('Test skipped because server does not support sharding');
        }
    }
}
