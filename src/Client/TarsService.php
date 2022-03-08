<?php

declare(strict_types=1);

namespace Imi\Tars\Client;

use Imi\Rpc\Client\IRpcClient;
use Imi\Rpc\Client\IService;

class TarsService implements IService
{
    /**
     * 客户端.
     */
    protected IRpcClient $client;

    /**
     * 服务名称.
     */
    protected string $name;

    public function __construct(IRpcClient $client, string $name)
    {
        $this->client = $client;
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $method, array $args = [])
    {
        return $this->client->getInstance()->{$this->name}->$method(...$args);
    }

    /**
     * 魔术方法.
     *
     * @param string $name      方法名
     * @param array $arguments 参数
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->call($name, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function getClient(): IRpcClient
    {
        return $this->client;
    }
}
