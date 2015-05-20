<?php

class Volt
{
    private $volts;

    public function __construct($volts)
    {
        $this->volts = $volts;
    }

    public function getVolts()
    {
        return $this->volts;
    }

    public function setVolts($volts)
    {
        $this->volts = $volts;
    }

}

class Socket
{
    public function getVolt()
    {
        return new Volt(12);
    }
}

interface SocketAdapter
{
    public function get120Volt();

    public function get12Volt();

    public function get3Volt();
}

class SocketObjectAdapterImpl implements SocketAdapter
{
    private $sock;

    function __construct()
    {
        $this->sock = new Socket();
    }

    private function convertVolt(Volt $v, $i)
    {
        return new Volt($v->getVolts()/$i);
    }

    public function get120Volt()
    {
        return $this->sock->getVolt();
    }

    public function get12Volt()
    {
        $v = $this->sock->getVolt();
        return $this->convertVolt($v, 10);
    }

    public function get3Volt()
    {
        $v = $this->sock->getVolt();
        return $this->convertVolt($v, 40);
    }

}