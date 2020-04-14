<?php
namespace ArtaxComposer\ServiceFactory;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter as AbstractCacheAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ArtaxComposer\Service\ArtaxService;

class ArtaxServiceFactory implements FactoryInterface
{
    /**
     * Load the cache via the Cache config
     *
     * @param $config
     *
     * @return null|AbstractCacheAdapter
     */
    private function loadCache($config, $serviceLocator)
    {
        if (!isset($config['cache'])) {
            return null;
        }

        if ($config['cache'] == null) {
            return null;
        }

        // cache is an instance of the AbstractCacheAdapter
        if ($config['cache'] instanceof AbstractCacheAdapter) {
            return $config['cache'];
        }

        // cache is a string, find the cache in the service locator
        $cache = $serviceLocator->get($config['cache']);

        // check if the cache is instance of the AbstractCacheAdapter
        if (!$cache instanceof AbstractCacheAdapter) {
            throw new \UnexpectedValueException('Cache must be an instance of \Laminas\Cache\Storage\Adapter\AbstractAdapter');
        }

        return $cache;
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \ArtaxComposer\Exception\NotProvidedException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var array $config */
        $config = $container->get('config');
        $config = isset($config['artax_composer']) ? $config['artax_composer'] : [];

        /** @var AbstractCacheAdapter|null $cache */
        $cache = $this->loadCache($config, $container);

        return new ArtaxService($config, $cache);
    }
}