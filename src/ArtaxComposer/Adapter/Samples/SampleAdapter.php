<?php
namespace ArtaxComposer\Adapter\Samples;

use Amp;
use Amp\Http\Client\HttpClientBuilder as ArtaxClient;
use Amp\Http\Client\Request as ArtaxRequest;
use Amp\Http\Client\Response as ArtaxResponse;
use Amp\Http\Client\SocketException as AmpSocketException;
use Amp\Dns\DnsException as AmpResolutionException;
use Amp\Socket\SocketException as NbsockSocketException;
use ArtaxComposer\Adapter\AdapterInterface;
use ArtaxComposer\Adapter\BaseAdapter;
use ArtaxComposer\Exception\FlowException;
use ArtaxComposer\Exception\NotProvidedException;

class SampleAdapter extends BaseAdapter implements AdapterInterface
{
    const RESET_PARAMETERS = 'none';

    // max connection timeout
    const OP_MS_CONNECT_TIMEOUT = 15000;

    // max body sizes
    const OP_MAX_BODY_BYTES = 20971520;

    // max attempts for artax requests
    const REQUEST_MAX_ATTEMPTS = 3;

    /**
     * @var ArtaxResponse
     */
    private $response;

    /** @var int */
    protected $executionTime;

    /**
     * Do the request (enabled multiple attempts)
     *
     * @param Amp\Promise $request
     * @param int $attempt
     *
     * @throws AmpResolutionException
     * @throws AmpSocketException
     * @throws NbsockSocketException
     * @throws \Throwable
     */
    private function doArtaxRequest(Amp\Promise $request, $attempt = 1)
    {
        try {
            $this->executionTime = microtime(true);

            /** @var ArtaxResponse $ampResponse */
            $this->response = Amp\Promise\wait($request);
        }
        catch (\Exception $exception) {

            if (
                $exception instanceof AmpSocketException
                || $exception instanceof AmpResolutionException
                || $exception instanceof NbsockSocketException
            ) {
                // try a second attempt
                if ($attempt < self::REQUEST_MAX_ATTEMPTS) {
                    $this->doArtaxRequest($request, $attempt + 1);
                    return;
                }
            }

            throw $exception;
        }
    }

    /**
     * Execute the request
     *
     * @throws AmpResolutionException
     * @throws AmpSocketException
     * @throws NbsockSocketException
     * @throws NotProvidedException
     * @throws \Throwable
     */
    public function doRequest()
    {
        if (!$this->uri) {
            throw new NotProvidedException('URI must be provided in order to execute the request');
        }

        if (!$this->method) {
            throw new NotProvidedException('URI must be provided in order to execute the request');
        }

        // console command get wrong language parameter in import task
        if (php_sapi_name() != "cli") {
            $this->setAcceptLanguageParameter();
        }

        $artaxClient = (new ArtaxClient())->build();

        $request = new ArtaxRequest($this->uri);
        $request->setTcpConnectTimeout(self::OP_MS_CONNECT_TIMEOUT);
        $request->setBodySizeLimit(self::OP_MAX_BODY_BYTES);

        $request->setMethod($this->method);
        $request->setHeaders($this->headers);

        /** force reset parameters */
        if ($this->body == '["none"]') {
            $this->body = null;
        }

        if (!empty($this->body)) {
            /** dont send unnecessary informations for the BE */
            if (isset($this->body['api_auth'])) {
                unset($this->body['api_auth']);
            }
        }

        $request->setBody($this->body);

        // make the request (first attempt)
        $this->doArtaxRequest($artaxClient->request($request));
    }

    /**
     * Status code of the response
     *
     * @return int
     * @throws FlowException
     */
    public function getResponseStatusCode()
    {
        if (!$this->response) {
            throw new FlowException('You have to call the request in order to obtain a status code of the response');
        }

        return (int) $this->response->getStatus();
    }

    /**
     * @return Amp\Promise|string
     * @throws FlowException
     */
    public function getResponseBody()
    {
        if (!$this->response) {
            throw new FlowException('You have to call the request in order to obtain the body of the response');
        }

        $response = -1;

        $this->response->getBody()->read()->onResolve(function (\Throwable $error = null, $result = null) use (& $response) {
            if ($error) {
                throw new \Exception($error->getMessage());
            }

            $response = $result;
        });

        while ($response < 0) {
            sleep(1);
        }

        return json_decode($response, true);
    }

    /**
     * Check if there is an header in the response
     *
     * @param string $header
     *
     * @return bool
     * @throws FlowException
     */
    public function hasResponseHeader($header)
    {
        if (!$this->response) {
            throw new FlowException('You have to call the request in order to check the headers of the response');
        }

        return $this->response->hasHeader($header);
    }

    /**
     * Get the value of a specific header
     *
     * @param string $header
     *
     * @return string
     * @throws FlowException
     */
    public function getResponseHeader($header)
    {
        if (!$this->response) {
            throw new FlowException('You have to call the request in order to obtain a status code of the response');
        }

        if (!$this->hasResponseHeader($header)) {
            return null;
        }

        return $this->response->getHeader($header)[0];
    }

    private function setAcceptLanguageParameter()
    {
        if (empty($this->headers['Accept-Language'])) {
            $this->headers['Accept-Language'] = 'it';
        }

    }
}