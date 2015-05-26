<?php


///////////////////////////

class InsufficientMoneyAmountException extends Exception {}

class Transfer
{
    private $id;
    private $sender;
    private $receiver;
    private $amount;

    public function __construct(Sender $sender, Receiver $receiver, $amount)
    {
        $this->id = uniqid();
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amount = (float)$amount;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getReceiver()
    {
        return $this->receiver;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function withdraw()
    {
        if($this->getSender()->getDisposableMoney() < $this->amount) {
            throw new InsufficientMoneyAmountException();
        } else {
            $this->getSender()->minus($this->amount);
            $this->getReceiver()->plus($this->amount);
        }
    }
}

class Sender
{
    private $name;
    private $amount = 0.0;

    public function __construct($name, $amount = null)
    {
        $this->name = $name;
        $this->amount = $amount;
    }

    public function plus($amount)
    {
        $this->amount += $amount;
    }

    public function minus($amount)
    {
        $this->amount -= $amount;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisposableMoney()
    {
        return $this->amount;
    }
}

class Receiver
{
    private $name;
    private $amount;

    public function __construct($name, $amount = null)
    {
        $this->name = $name;
        $this->amount = $amount;
    }

    public function plus($amount)
    {
        $this->amount += $amount;
    }

    public function minus($amount)
    {
        $this->amount -= $amount;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisposableMoney()
    {
        return $this->amount;
    }
}

class MoneySendEvent
{
    private $transfer;

    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function getTransfer()
    {
        return $this->transfer;
    }
}

class EventDispatcher
{
    private $subscribers = array();
    private $events = array();

    public function addSubscriber($eventName, $event)
    {
        $this->subscribers[$eventName] = $event;
    }

    public function getSubscriber($eventName)
    {
        return $this->subscribers[$eventName];
    }

    public function addListener($eventName, $array)
    {
        $this->events[$eventName][] = $array;
    }

    public function notify($eventName)
    {
        foreach ($this->events[$eventName] as $item) {
            call_user_func($item, $this->getSubscriber($eventName));
        }
    }
}

class Bank
{
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function transferMoney(Transfer $transfer)
    {
        $transfer->withdraw();
        $this->dispatcher->notify('bank.money_transfer');
    }
}


// Stock market wants to know about the money transfers
class StockMarket
{
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->dispatcher->addListener('bank.money_transfer', array($this, 'onMoneyTransfer'));
    }

    public function onMoneyTransfer(MoneySendEvent $event)
    {
        printf("Stockmarket have been notified about the Transfer (Sender: %s (<strong>%s</strong>) and Receiver: %s (<strong>%s</strong>))", $event->getTransfer()->getSender()->getName(), $event->getTransfer()->getSender()->getDisposableMoney(), $event->getTransfer()->getReceiver()->getName(), $event->getTransfer()->getReceiver()->getDisposableMoney());
    }
}
// AnyInterestedIn wants to know about the money transfers
class AnyInterestedIn
{
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->dispatcher->addListener('bank.money_transfer', array($this, 'onMoneyTransfer'));
    }

    public function onMoneyTransfer(MoneySendEvent $event)
    {
        printf("AnyInterestedIn have been notified about the Transfer (Sender: %s and Receiver: %s)", $event->getTransfer()->getSender()->getName(), $event->getTransfer()->getReceiver()->getName());
    }
}
//////////////////////////////


#################################################################################################################

$dispatcher = new EventDispatcher();
$stockMarket = new StockMarket($dispatcher);
$anyInterestedIn = new AnyInterestedIn($dispatcher);


$transfer = new Transfer(new Sender('Jonas', 2000), new Receiver('Petras', 1000), 895.50);
$dispatcher->addSubscriber('bank.money_transfer', new MoneySendEvent($transfer));

try {
    $bank = new Bank($dispatcher);
    $bank->transferMoney($transfer);
    $bank->transferMoney($transfer);
//    $bank->transferMoney($transfer);
}
catch(InsufficientMoneyAmountException $e) {
    printf("Insufficient Money", $e->getMessage());
}
catch(Exception $e) {
    printf("General", $e->getMessage());
}
