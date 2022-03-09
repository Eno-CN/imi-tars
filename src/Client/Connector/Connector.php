<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/4/29
 * Time: 下午12:55.
 */

namespace Imi\Tars\Client\Connector;

use Imi\Tars\Registry\Registry;
//use Imi\Tars\Monitor\Monitor;

class Connector
{
    protected $_moduleName;
    protected $_servantName;

    protected $_localIp;
    protected $_locator;

    protected $_setdivision;
    protected $_enableset;

    protected $_routeInfo;

    protected $_iVersion = 1;
    protected $_iTimeout = 2;

    // monitorHelper?
//    protected $monitor;
//    protected $_statServantName;
    // registrHelper?
    protected ?Registry $_registry = null;
    protected $_refreshEndpointInterval;

    public function __construct(ConnectorConfig $config, ?Registry $registry = null)
    {
        $this->_moduleName = $config->getModuleName();
        $this->_servantName = $config->getServantName();
        $this->_localIp = $config->getLocalip();
        $this->_locator = $config->getLocator();
        $this->_iTimeout = $config->getConnectTimeout();
        $this->_setdivision = $config->getSetDivision();
        $this->_enableset = $config->isEnableSet();
		$this->_routeInfo = $config->getRouteInfo();

        $this->_iVersion = $config->getIVersion();

//        $this->_statServantName = $config->getStat();

        $this->_refreshEndpointInterval = empty($config->getRefreshEndpointInterval())
            ? 60000 : $config->getRefreshEndpointInterval();

        $this->_registry = $registry;


//        $reportInterval = empty($config->getReportInterval()) ? 60000 : $config->getReportInterval();
//        $this->monitor = new Monitor($this->_locator, $this->_moduleName, $reportInterval);
    }

    public function __call($name, $arguments)
    {
        // 服务发现
        $this->_routeInfo = $this->_registry->findObjectById($this->_servantName);
        return $this->invoke(...$arguments);

    }

    public function __get($name){
        $this->_servantName = $name;
		return $this;
    }

    // 同步的socket tcp收发
    public function invoke(RequestPacket $requestPacket, $timeout, $responsePacket = null, $sIp = '', $iPort = 0)
    {
//        $startTime = $this->militime();
        $count = count($this->_routeInfo) - 1;
        if ($count === -1) {
            throw new \Exception('Rout fail', Code::ROUTE_FAIL);
        }
        $index = rand(0, $count);
        $ip = empty($sIp) ? ($this->_routeInfo[$index]['sIp'] ?? $this->_routeInfo[$index]['host']) : $sIp;
        $port = empty($iPort) ? ($this->_routeInfo[$index]['iPort'] ?? $this->_routeInfo[$index]['port']) : $iPort;

        try {
            $requestBuf = $requestPacket->encode();
            $responseBuf = $this->swooleCoroutineTcp($ip, $port,
                $requestBuf, $timeout);
            $responsePacket = $responsePacket ?: new ResponsePacket();
            $responsePacket->_responseBuf = $responseBuf;
            $responsePacket->iVersion = $requestPacket->_iVersion;
            $sBuffer = $responsePacket->decode();
//            $endTime = $this->militime();

//            if(!is_null($this->_locator))
//            {
//                //服务调用成功上报
//                $this->monitor->addStat($requestPacket->_servantName, $requestPacket->_funcName, $ip,
//                    $port, ($endTime - $startTime), 0, 0);
//            }
            return $sBuffer;
        } catch (\Exception $e) {
            //服务调用异常上报
//            $endTime = $this->militime();
//
//            if(!is_null($this->_locator))
//            {
//                $this->monitor->addStat($requestPacket->_servantName, $requestPacket->_funcName, $ip,
//                    $port, ($endTime - $startTime), $e->getCode(), $e->getCode());
//            }
            throw $e;
        }
    }

    private function swooleCoroutineTcp($sIp, $iPort, $requestBuf, $timeout = 2)
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

//        $client->set(array(
//            'open_length_check'     => 1,
//            'package_length_type'   => 'N',
//            'package_length_offset' => 0,       //第N个字节是包长度的值
//            'package_body_offset'   => 0,       //第几个字节开始计算长度
//            'package_max_length'    => 2000000,  //协议最大长度
//        ));

        if (!$client->connect($sIp, $iPort, $timeout)) {
            $code = Code::TARS_SOCKET_CONNECT_FAILED;
            throw new \Exception(Code::getErrMsg($code), $code);
        }

        if (!$client->send($requestBuf)) {
            $client->close();
            $code = Code::TARS_SOCKET_SEND_FAILED;
            throw new \Exception(Code::getErrMsg($code), $code);
        }
        $firstRsp = true;
        $curLen = 0;
        $responseBuf = '';
        $packLen = 0;
        $isConnected = true;
        while ($isConnected) {
            if ($client->errCode) {
                throw new \Exception('socket recv falied', $client->errCode);
            }
            $data = $client ? $client->recv() : '';
            if ($firstRsp) {
                $firstRsp = false;
                $list = unpack('Nlen', substr($data, 0, 4));
                $packLen = $list['len'];
                $responseBuf = $data;
                $curLen += strlen($data);
                if ($curLen == $packLen) {
                    $isConnected = false;
                    $client->close();
                }
            } else {
                if ($curLen < $packLen) {
                    $responseBuf .= $data;
                    $curLen += strlen($data);
                    if ($curLen == $packLen) {
                        $isConnected = false;
                        $client->close();
                    }
                } else {
                    $isConnected = false;
                    $client->close();
                }
            }
        }

        //读取最多32M的数据
        //$responseBuf = $client->recv();

        if (empty($responseBuf)) {
            $client->close();
            // 已经断开连接
            $code = Code::TARS_SOCKET_RECEIVE_FAILED;
            throw new \Exception(Code::getErrMsg($code), $code);
        }

        return $responseBuf;
    }

    private function militime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $miliseconds = (float) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $miliseconds;
    }


}
