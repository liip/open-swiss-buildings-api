<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$doctrine = $kernel->getContainer()->get('doctrine');

$metadataFactory = new class ($doctrine) implements ClassMetadataFactory {
    private Registry $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getAllMetadata()
    {
        $all = [];

        foreach ($this->doctrine->getManagers() as $manager) {
            $all = array_merge($all, $manager->getMetadataFactory()->getAllMetadata());
        }

        return $all;
    }

    public function getMetadataFor($className)
    {
        return $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
    }

    public function isTransient($className)
    {
        $isTransient = true;

        foreach ($this->doctrine->getManagers() as $manager) {
            $isTransient = $isTransient && $manager->getMetadataFactory()->isTransient($className);
        }

        return $isTransient;
    }

    public function hasMetadataFor($className)
    {
        $hasMetadata = false;

        foreach ($this->doctrine->getManagers() as $manager) {
            $hasMetadata = $hasMetadata || $manager->getMetadataFactory()->hasMetadataFor($className);
        }

        return $hasMetadata;
    }

    public function setMetadataFor($className, $class): void
    {
        throw new Exception(__FILE__);
    }
};

return new class ($doctrine, $metadataFactory) implements ObjectManager {
    private Registry $doctrine;
    private ClassMetadataFactory $metadataFactory;

    public function __construct(Registry $doctrine, ClassMetadataFactory $metadataFactory)
    {
        $this->doctrine = $doctrine;
        $this->metadataFactory = $metadataFactory;
    }

    public function getRepository($className)
    {
        return $this->doctrine->getRepository($className);
    }

    public function getClassMetadata($className)
    {
        return $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
    }

    /**
     * @return ClassMetadataFactory<T>
     */
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    public function find($className, $id): void
    {
        throw new Exception(__FILE__);
    }

    public function persist($object): void
    {
        throw new Exception(__FILE__);
    }

    public function remove($object): void
    {
        throw new Exception(__FILE__);
    }

    public function merge($object): never
    {
        throw new Exception(__FILE__);
    }

    public function clear($objectName = null): void
    {
        throw new Exception(__FILE__);
    }

    public function detach($object): void
    {
        throw new Exception(__FILE__);
    }

    public function refresh($object): void
    {
        throw new Exception(__FILE__);
    }

    public function flush(): void
    {
        throw new Exception(__FILE__);
    }

    public function initializeObject($obj): void
    {
        throw new Exception(__FILE__);
    }

    public function contains($object): void
    {
        throw new Exception(__FILE__);
    }
};
