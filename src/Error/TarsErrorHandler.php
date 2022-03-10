<?php

declare(strict_types=1);

namespace Imi\Tars\Error;

use Imi\App;
use Imi\RequestContext;
use Imi\Server\DataParser\DataParser;
use Imi\Server\TcpServer\Contract\ITcpServer;
use Imi\Server\TcpServer\Message\Proxy\ReceiveDataProxy;
use Imi\Tars\Protocol\TARSProtocol;

class TarsErrorHandler
{
    /**
     * 取消继续抛出异常.
     */
    protected bool $cancelThrow = false;

    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handle(\Throwable $throwable): bool
    {
        /** @var ITcpServer $server */
        $server = RequestContext::getServer();

        $receiveDataProxy = new ReceiveDataProxy();
        $clientId =  $receiveDataProxy->getClientId();
        $data =  $receiveDataProxy->getFormatData();

        /** @var TARSProtocol $protocol */
        $protocol = App::getBean('TARSProtocol');
        $responseData = $protocol->packErrRsp($data, $throwable->getCode(), $throwable->getMessage());
        $server->send($clientId, $server->getBean(DataParser::class)->encode($responseData));

        return $this->cancelThrow;
    }
}
