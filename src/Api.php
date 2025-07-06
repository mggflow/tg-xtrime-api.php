<?php

namespace MGGFLOW\Telegram\Xtrime;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use MGGFLOW\Telegram\Xtrime\Exceptions\ApiError;
use MGGFLOW\Telegram\Xtrime\Exceptions\FailedRequest;

class Api
{
    /**
     * Options of request. Check GuzzleHTTP Client->request method.
     * @var array
     */
    public array $requestOptions = [];

    /**
     * Received response will be here.
     * @var Response|null
     */
    public ?Response $response = null;

    protected string $paramsWrapKey = 'data';
    protected Client $client;
    protected array $params = [];
    protected string $tgXtrimeApiUrl;

    protected string $sessionName;
    protected ?string $className;
    protected string $methodName;

    public function __construct(string $apiUrl)
    {
        $this->tgXtrimeApiUrl = $apiUrl;

        $this->client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    /**
     * Set session name.
     * @param string $sessionName
     * @return $this
     */
    public function sessionName(string $sessionName): self
    {
        $this->sessionName = $sessionName;

        return $this;
    }

    /**
     * Wrapper around API object name setter.
     * @param $name
     * @return $this
     */
    public function __get($name)
    {
        return $this->setClassName($name);
    }

    /**
     * Set API class name.
     * @param $name
     * @return $this
     */
    public function setClassName($name): self
    {
        $this->className = $name;

        return $this;
    }

    /**
     * Wrapper about preparing to send request.
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->resetCallData();
        $this->setMethodName($name);

        if (isset($arguments[0])) {
            $this->setParams($arguments[0]);
        } else {
            $this->setParams([]);
        }

        return $this;
    }

    /**
     * Set API method name.
     * @param $name
     * @return $this
     */
    public function setMethodName($name): self
    {
        $this->methodName = $name;

        return $this;
    }

    /**
     * Send request to API.
     * @return false|mixed
     * @throws ApiError
     * @throws FailedRequest
     * @throws GuzzleException
     */
    public function send(): mixed
    {
        $this->prepareRequestOptions();

        try{
            $this->response = $this->client->request('POST', $this->genRequestUrl(), $this->requestOptions);
        }catch(RequestException $e){
            $this->handleRequestException($e);
        }


        return $this->getContent();
    }

    /**
     * Set params of request to API.
     * @param $params
     * @return $this
     */
    public function setParams($params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @throws ApiError
     * @throws FailedRequest
     */
    protected function handleRequestException(RequestException $e){
        if($e->hasResponse()){
            $decodedResp = json_decode($e->getResponse()->getBody());
            if (json_last_error() === JSON_ERROR_NONE) {
                throw new ApiError()->fillMessageFromResponseErrors($decodedResp);
            }
        }

        throw new FailedRequest($e->getMessage());
    }

    protected function resetCallData(): void
    {
        $this->response = null;
    }

    protected function genRequestUrl(): string
    {
        $action = $this->genAction();

        if (empty($this->sessionName)) {
            return $this->tgXtrimeApiUrl . '/' . $action;
        } else {
            return $this->tgXtrimeApiUrl . '/' . $this->sessionName . '/' . $action;
        }
    }

    protected function genAction(): string
    {
        if (empty($this->className)) {
            return $this->methodName;
        } else {
            return $this->className . '.' . $this->methodName;
        }
    }

    protected function prepareRequestOptions(): void
    {
        $this->requestOptions['body'] = json_encode([
            $this->paramsWrapKey => $this->params
        ]);
    }

    protected function getContent()
    {
        if ($this->response->getStatusCode() > 400) return false;

        $content = json_decode($this->response->getBody()->getContents());

        return $content ?? false;
    }
}