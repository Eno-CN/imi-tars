<?php

namespace Imi\Tars\Registry;


use Imi\Cache\Annotation\Cacheable;
use Imi\Lock\Annotation\Lockable;
use Imi\Tars\Client\Connector\Connector;
use Imi\Tars\Client\Connector\ConnectorConfig;
use Imi\Tars\Client\Connector\RequestPacket;
use Imi\Tars\Client\Connector\TUPAPIWrapper;
use Imi\Tars\Utils;

/**
 * 服务发现（主控寻址）
 */
class Registry
{
    private int $_iVersion = 3;
    private int $_iTimeout = 2;
    private string $_servantName;

    /**
     * Registry 使用单独的 Connector
     *
     * @var Connector
     */
    private Connector $_connector;

    /**
     * @var RequestPacket
     */
    private RequestPacket $_requestPacket;

    /**
     * @throws \Exception
     */
    public function __construct(ConnectorConfig $connectorConfig)
    {
        $result = Utils::getLocatorInfo($connectorConfig->getLocator());
        if (empty($result) || !isset($result['locatorName']) || empty($result['routeInfo'])) {
            throw new \Exception('Route Fail', -100);
        }

        $this->_servantName = $result['locatorName'];

        $connectorConfig->setRouteInfo($result['routeInfo']);
        $this->_connector = new Connector($connectorConfig);
        $this->_requestPacket = new RequestPacket();
        $this->_requestPacket->_iVersion = $this->_iVersion;
        $this->_requestPacket->_servantName = $this->_servantName;
    }

    /**
     * 通过ServantName寻址
     *
     * @Cacheable(
     *   name="tarsCache",
     *   key="Registry:Locator:{servant}",
     *   ttl=300,
     *   lockable=@Lockable(
     *     id="Registry:Locator:{servant}",
     *     waitTimeout=0,
     *   ),
     *   preventBreakdown=true,
     * )
     *
     * @throws \Exception
     */
    public function findObjectById($servant)
    {
        $this->_requestPacket->_funcName = __FUNCTION__;
        $encodeBufs = [];

        $buffer = TUPAPIWrapper::putString('id', 1, $servant, $this->_iVersion);
        $encodeBufs['id'] = $buffer;
        $this->_requestPacket->_encodeBufs = $encodeBufs;

        $sBuffer = $this->_connector->invoke($this->_requestPacket, $this->_iTimeout);

        return TUPAPIWrapper::getVector('', 0, new \TARS_Vector(new EndpointF()), $sBuffer, $this->_iVersion);
    }
}
