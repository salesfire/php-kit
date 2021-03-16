<?php
declare(strict_types=1);

namespace Prismic\Exception;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class RequestFailureException extends RuntimeException
{

    /**
     * @var GuzzleException|null
     */
    protected $guzzleException;

    /**
     * Factory to return a Prismic Exception wrapping a Guzzle Exception
     * @param GuzzleException $e
     * @return RequestFailureException
     */
    public static function fromGuzzleException(GuzzleException $e) : self
    {
        if ($e instanceof RequestException || $e instanceof ConnectException) {
            return static::fromGuzzleTransferException($e);
        }
        $exception = new static('Api Request Failed', 500, $e);
        $exception->guzzleException = $e;
        return $exception;
    }

    /**
     * Factory to wrap a Guzzle Request Exception when we should have access to a request and a response
     * @param GuzzleException $e
     * @return RequestFailureException
     */
    protected static function fromGuzzleTransferException(GuzzleException $e) : self
    {
        $response = $e instanceof RequestException ? $e->getResponse() : null;
        $code     = $response ? $response->getStatusCode() : 0;
        $reason   = $response ? $response->getReasonPhrase() : 'No Response';
        $request  = $e->getRequest();
        $url      = $request->getUri();

        $message = sprintf(
            'The %s request to the repository %s resulted in a %d %s error. Complete URL: %s',
            $request->getMethod(),
            $url->getHost(),
            $code,
            $reason,
            (string) $url
        );

        $exception = new static($message, $code, $e);
        $exception->guzzleException = $e;
        return $exception;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse() :? ResponseInterface
    {
        if (! $this->guzzleException instanceof RequestException) {
            return null;
        }
        return $this->guzzleException->getResponse();
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequest() :? RequestInterface
    {
        if (! $this->guzzleException instanceof RequestException &&
            ! $this->guzzleException instanceof ConnectException) {
            return null;
        }
        return $this->guzzleException->getRequest();
    }
}
