<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/4/29
 * Time: 下午12:55.
 */

namespace Imi\Tars\Client\Connector;

class ConnectorConfig
{
    protected $locator = null;
    // 包含一组ip,非空说明不需要进行主控寻址了
    protected $routeInfo;

    protected $syncInvokeTimeout = 3000;
    protected $asyncInvokeTimeout = 3000;
    protected $refreshEndpointInterval = 60;
    protected $reportInterval = 60000;
    protected $stat = 'tars.tarsstat.StatObj';
    protected $property = 'tars.tarsproperty.PropertyObj';
    protected $sampleRate = 0;
    protected $maxSampleCount = 0;
    protected $moduleName = 'tarsproxy';
    protected $servantName;
    protected $enableSet = false;
    protected $setDivision = null;
    protected $connectTimeout = 3;
    protected $keepAliveTime;
    protected $charsetName;
    protected $logPath;
    protected $logLevel;
    protected $dataPath;
    protected $localip;

    protected $iVersion = 1;

    public function __construct(array $config)
    {
        $this->keepAliveTime = 120;
        $this->charsetName = 'UTF-8';
        $this->logLevel = 'INFO';
        $this->init($config);
    }

    protected function init(array $config)
    {
        $this->localip = $config['tars']['application']['server']['localip'];
        $this->locator = $config['tars']['application']['client']['locator'];
        $this->logPath = $config['tars']['application']['server']['logpath'];
        $this->logLevel = $config['tars']['application']['server']['loglevel'];
        $this->dataPath = $config['tars']['application']['server']['datapath'];
        $this->syncInvokeTimeout = $config['tars']['application']['client']['sync-invoke-timeout'];
        $this->asyncInvokeTimeout = $config['tars']['application']['client']['async-invoke-timeout'];
        $this->refreshEndpointInterval = $config['tars']['application']['client']['refresh-endpoint-interval'];
        $this->stat = $config['tars']['application']['client']['stat'];
        $this->property = $config['tars']['application']['client']['property'];
        $this->reportInterval = $config['tars']['application']['client']['report-interval'];
        $this->moduleName = $config['tars']['application']['client']['modulename'];
        $enableSetStr = $config['tars']['application']['enableset'];
        $this->setDivision = $config['tars']['application']['setdivision'];

        if ($enableSetStr === 'Y') {
            $this->enableSet = true;
        } else {
            $this->enableSet = false;
            $this->setDivision = null;
        }

//        $this->connectTimeout = $config['tars']['application']['client']['connectTimeout'];
//        $this->keepAliveTime = $config['tars']['application']['client']['keepAliveTime'];
//        $this->charsetName = $config['tars']['application']['client']['charsetName'];
    }

    public function getLocator()
    {
        return $this->locator;
    }

    public function setLocator($locator)
    {
        $this->locator = $locator;
    }

    public function getRouteInfo()
    {
        return $this->routeInfo;
    }
    public function setRouteInfo($routeInfo)
    {
        $this->routeInfo = $routeInfo;
    }

    public function getSyncInvokeTimeout()
    {
        return $this->syncInvokeTimeout;
    }

    public function setSyncInvokeTimeout($syncInvokeTimeout)
    {
        $this->syncInvokeTimeout = $syncInvokeTimeout;
    }

    public function getAsyncInvokeTimeout()
    {
        return $this->asyncInvokeTimeout;
    }

    public function setAsyncInvokeTimeout($asyncInvokeTimeout)
    {
        $this->asyncInvokeTimeout = $asyncInvokeTimeout;
    }

    public function getRefreshEndpointInterval()
    {
        return $this->refreshEndpointInterval;
    }

    public function setRefreshEndpointInterval($refreshEndpointInterval)
    {
        $this->refreshEndpointInterval = $refreshEndpointInterval;
    }

    public function getStat()
    {
        return $this->stat;
    }

    public function setStat($stat)
    {
        $this->stat = $stat;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getReportInterval()
    {
        return $this->reportInterval;
    }

    public function setReportInterval($reportInterval)
    {
        $this->reportInterval = $reportInterval;
    }

    public function getSampleRate()
    {
        return $this->sampleRate;
    }

    public function setSampleRate($sampleRate)
    {
        $this->sampleRate = $sampleRate;
    }

    public function getMaxSampleCount()
    {
        return $this->maxSampleCount;
    }

    public function setMaxSampleCount($maxSampleCount)
    {
        $this->maxSampleCount = $maxSampleCount;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    public function isEnableSet()
    {
        return $this->enableSet;
    }

    public function setEnableSet($enableSet)
    {
        $this->enableSet = $enableSet;
    }

    public function getSetDivision()
    {
        return $this->setDivision;
    }

    public function setSetDivision($setDivision)
    {
        $this->setDivision = $setDivision;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getKeepAliveTime()
    {
        return $this->keepAliveTime;
    }

    public function setKeepAliveTime($keepAliveTime)
    {
        $this->keepAliveTime = $keepAliveTime;
    }

    public function getLogPath()
    {
        return $this->logPath;
    }

    public function setLogPath($logPath)
    {
        $this->logPath = $logPath;
    }

    public function getLogLevel()
    {
        return $this->logLevel;
    }

    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    public function getDataPath()
    {
        return $this->dataPath;
    }

    public function setDataPath($dataPath)
    {
        $this->dataPath = $dataPath;
    }

    public function getCharsetName()
    {
        return $this->charsetName;
    }

    public function setCharsetName($charsetName)
    {
        $this->charsetName = $charsetName;
    }

    public function getLocalip()
    {
        return $this->localip;
    }

    public function setLocalip($localip)
    {
        $this->localip = $localip;
    }

    public function getIVersion()
    {
        return $this->iVersion;
    }

    public function setIVersion($iVersion)
    {
        $this->iVersion = $iVersion;
    }

    public function getServantName()
    {
        return $this->servantName;
    }

    public function setServantName($servantName)
    {
        $this->servantName = $servantName;
    }

}
