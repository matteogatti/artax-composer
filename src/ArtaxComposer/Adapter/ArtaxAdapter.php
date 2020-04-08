<?php
namespace ArtaxComposer\Adapter;

use Amp;
use Amp\Http\Client\HttpClientBuilder as ArtaxClient;
use Amp\Http\Client\Request as ArtaxRequest;
use Amp\Http\Client\Response as ArtaxResponse;
use Amp\Http\Client\SocketException as AmpSocketException;
use Amp\Dns\DnsException as AmpResolutionException;
use Amp\Socket\SocketException as NbsockSocketException;
use ArtaxComposer\Exception\FlowException;
use ArtaxComposer\Exception\NotProvidedException;

class ArtaxAdapter extends BaseAdapter implements AdapterInterface
{
    // max connection timeout
    const OP_MS_CONNECT_TIMEOUT = 15000;

    // max attempts for artax requests
    const REQUEST_MAX_ATTEMPTS = 2;

    /**
     * @var ArtaxResponse
     */
    private $response;

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

        $artaxClient = (new ArtaxClient())->build();

        $request = new ArtaxRequest($this->uri);
        $request->setTcpConnectTimeout(self::OP_MS_CONNECT_TIMEOUT);

        $request->setMethod($this->method);
        $request->setHeaders($this->headers);

        if ($this->body != null) {
            $request->setBody($this->body);
        }

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
     * Body of the response
     *
     * @return string
     * @throws FlowException
     */
    public function getResponseBody()
    {
        if (!$this->response) {
            throw new FlowException('You have to call the request in order to obtain the body of the response');
        }

        return json_decode($this->response->getBody(), true);
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
}