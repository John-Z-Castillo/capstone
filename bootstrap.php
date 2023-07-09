<?php

/**
 * Boostrap php represent the dependency container for Donctrine
 * It is injecto into the routes so that entity manager can be access
 * globally.
 */

use App\service\Service;
use App\service\TransactionService;
use App\service\UserService;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use UMA\DIC\Container;

// setup container
$container = new Container(require __DIR__ . '/settings.php');

// setup entity
$container->set(EntityManager::class, static function (Container $c): EntityManager {
    /** @var array $settings */
    $settings = $c->get('settings');

    // Use the ArrayAdapter or the FilesystemAdapter depending on the value of the 'dev_mode' setting
    // You can substitute the FilesystemAdapter for any other cache you prefer from the symfony/cache library
    $cache = $settings['doctrine']['dev_mode'] ?
        DoctrineProvider::wrap(new ArrayAdapter()) :
        DoctrineProvider::wrap(new FilesystemAdapter(directory: $settings['doctrine']['cache_dir']));

    $config = Setup::createAttributeMetadataConfiguration(
        $settings['doctrine']['metadata_dirs'],
        $settings['doctrine']['dev_mode'],
        null,
        $cache
    );

    return EntityManager::create($settings['doctrine']['connection'], $config);
});

// Add the services to the container. 
$container->set(UserService::class, static function (Container $c) {
    return new UserService($c->get(EntityManager::class));
});

// Add the services to the container. 
$container->set(TransactionService::class, static function (Container $c) {
    return new TransactionService($c->get(EntityManager::class));
});

$container->set(Service::class, static function (Container $c) {
    return new Service($c->get(EntityManager::class));
});

return $container;