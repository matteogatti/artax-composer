<?php

use PHPUnit\Framework\TestCase;
use ArtaxComposer\Service\ArtaxService;
use ArtaxComposer\Exception\NotProvidedException;

class ArtaxServiceTest extends TestCase
{
    const X_CLIENT_AUTH = '12345678abcd';
    const X_CLIENT_API = 'jsonplaceholder.typicode.com';
    const X_CLIENT_API_HOST = 'typicode.com';

    private function getconfig()
    {
        return [
            'adapter' => 'ArtaxComposer\Adapter\Samples\SampleAdapter',
            'cache' => null,
            'seeds' => [
                'enabled'   => false,
                'directory' => 'data/seeds/',
            ],
            'default_headers' => [
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json; charset=utf-8',
                'X-Client-Auth'   => self::X_CLIENT_AUTH,
                'Host'            => self::X_CLIENT_API,
                'X-Forwarded-For' => '127.0.0.1',
                'User-Agent'      => sprintf(
                    'Frontend %s %s',
                    '0.0.0-test',
                    self::X_CLIENT_API_HOST
                )
            ],
            'newrelic' => true,
        ];
    }

    /**
     * @throws NotProvidedException
     */
    public function testDoSampleRequest()
    {
        try {
            $artaxService = new ArtaxService($this->getconfig());
        } catch (NotProvidedException $e) {
            throw new NotProvidedException($e->getMessage());
        }

        $response = $artaxService
            ->setUri(sprintf('https://%s/%s', self::X_CLIENT_API, 'posts/42'))
            ->get();


        $this->assertIsArray($response);
    }
}