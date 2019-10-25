<?php
use LTO\Account;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Jasny\HttpDigest\HttpDigest;
use Jasny\HttpDigest\ClientMiddleware as HttpDigestMiddleware;


class Identity implements JsonSerializable
{
    const HOST = 'https://app.letsflow.io/';

    protected $account;
    protected $client;

    public $id;
    protected $signkeys;
    protected $role;


    public function __construct(string $id, Account $account, $role = 'participant')
    {
        $this->id = $id;
        $this->account = $account;

        $this->signkeys['default'] = $this->account->getPublicSignKey();
        $this->role = $role;

        $stack = HandlerStack::create();
        $stack->push((new HttpDigestMiddleware(new HttpDigest('SHA-256')))->forGuzzle());
        $stack->push(new HttpSignatureMiddleware());
        $stack->push(new JsonMiddleware());

        $this->client = new Client(['handler' => $stack, 'base_uri' => self::HOST]);
    }

    public function createNewIdentity(Identity $identity)
    {
        $response = $this->client->request(
            'POST',
            'identities',
            ['account' => $this->account, 'json' => $identity]
        );

        return $identity;
    }

    public function createScenario($name, $path)
    {
        $scenario = ScenarioLoader::getInstance()->load($name, $path);
        $scenario->id = hash('sha256', json_encode($scenario));
        if (!$this->scenarioExists($scenario->id)) {
            $response = $this->client->request(
                'POST',
                'scenarios',
                ['account' => $this->account, 'json' => $scenario, 'headers' => ['Accept' => 'application/json']]
            );
            if ($response->getStatusCode() === 204) {
                return $scenario;
            }
            return json_decode($response->getBody());
        }
        return $scenario;
    }

    protected function scenarioExists($id)
    {
        $response = $this->client->request(
            'GET',
            "scenarios/$id",
            ['account' => $this->account, 'http_errors' => false]
        );
        if ($response->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    public function initiateProcess($id, $scenario, $actors)
    {
        $data = [
            'id' => $id,
            'scenario' => $scenario,
            'actors' => $actors
        ];
        $response = $this->client->request(
            'POST',
            'processes',
            ['account' => $this->account, 'json' => $data]
        );
        if($response->getStatusCode() == 201) {
            return true;
        }

        return false;
    }

    public function sendResponse($id, $action, $key = 'ok', $data = null)
    {
        $response = [
            '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
            'action' => [
                'key' => $action
            ],
            'key' => $key
        ];

        if ($data) {
            $response->data = $data;
        }

        $response = $this->client->request(
            'POST',
            "processes/$id/response",
            ['account' => $this->account, 'json' => $response]
        );
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        return true;
    }

    public function getProcessById($id)
    {
        $response = $this->client->request(
            'GET',
            "processes/$id",
            ['account' => $this->account, 'headers' => ['Accept' => 'application/json'], 'http_errors' => false]
        );
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        return json_decode($response->getBody());
    }

    public function getProcesses()
    {
        return $this->client->request(
            'GET',
            'processes',
            ['account' => $this->account, 'headers' => ['Accept' => 'application/json']]
        );
    }

    public function getPublicSignKey($encoding = 'base58')
    {
        return $this->account->getPublicSignKey($encoding);
    }

    public function jsonSerialize()
    {
        return (object)[
            'id' => $this->id,
            'signkeys' => $this->signkeys,
            'authz' => $this->role
        ];
    }
}