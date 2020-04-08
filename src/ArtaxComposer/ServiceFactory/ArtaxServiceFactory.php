<?php
namespace ArtaxComposer\ServiceFactory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Cache\Storage\Adapter\AbstractAdapter as AbstractCacheAdapter;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
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
            throw new \UnexpectedValueException('Cache must be an instance of \Zend\Cache\Storage\Adapter\AbstractAdapter');
        }

        return $cache;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return ArtaxService
     * @throws \ArtaxComposer\Exception\NotProvidedException
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var array $config */
        $config = $serviceLocator->get('config');
        $config = isset($config['artax_composer']) ? $config['artax_composer'] : [];

        /** @var AbstractCacheAdapter|null $cache */
        $cache = $this->loadCache($config, $serviceLocator);

        return new ArtaxService($config, $cache);
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // TODO: Implement __invoke() method.
    }
}