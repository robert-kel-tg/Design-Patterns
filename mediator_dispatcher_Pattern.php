<?php


///////////////////////////

class InsufficientMoneyAmountException extends Exception {}

class Money
{
    private $amount = 0.0;

    private function __construct($amount)
    {
        $this->setAmount($amount);
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public static function create($newAmount)
    {
        return new static($newAmount);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function plus($amount)
    {
        $this->amount += $amount;
    }

    public function minus($amount)
    {
        $this->amount -= $amount;
    }
}

class Rate
{
    private $rate;

    private function __construct($rate)
    {
        $this->setRate($rate);
    }

    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    public static function create($newRate)
    {
        return new static($newRate);
    }

    public function getRate()
    {
        return $this->rate;
    }
}

class Transfer
{
    private $id;
    private $sender;
    private $receiver;
    private $money;

    public function __construct(Sender $sender, Receiver $receiver, Money $money)
    {
        $this->id = uniqid();
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->money = $money;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getReceiver()
    {
        return $this->receiver;
    }

    public function getMoney()
    {
        return $this->money;
    }

    public function withdraw()
    {
        if($this->getSender()->getDisposableMoney()->getAmount() < $this->money->getAmount()) {
            throw new InsufficientMoneyAmountException();
        } else {
            $this->sender->minus($this->money->getAmount());
            $this->receiver->plus($this->money->getAmount());
        }
    }
}

class Sender
{
    private $user;
    private $money;

    public function __construct(User $user, Money $money)
    {
        $this->user = $user;
        $this->money = $money;
    }

    public function plus($amount)
    {
        $this->money->plus($amount);
    }

    public function minus($amount)
    {
        $this->money->minus($amount);
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getDisposableMoney()
    {
        return $this->money;
    }
}

class Receiver
{
    private $user;
    private $money;

    public function __construct(User $user, Money $money)
    {
        $this->user = $user;
        $this->money = $money;
    }

    public function plus($amount)
    {
        $this->money->plus($amount);
    }

    public function minus($amount)
    {
        $this->money->minus($amount);
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getDisposableMoney()
    {
        return $this->money;
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

class ExchangeRateChangedEvent
{
    private $rateBefore;
    private $rateAfter;

    public function __construct(Rate $rateBefore, Rate $rateAfter)
    {
        $this->rateBefore = $rateBefore;
        $this->rateAfter = $rateAfter;
    }

    public function getRateBefore()
    {
        return $this->rateBefore->getRate();
    }

    public function getRateAfter()
    {
        return $this->rateAfter->getRate();
    }
}

/**
 * Mediator/Observer Pattern
 */

class EventDispatcher
{
    private $subscribers = array();
    private $events = array();

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    public function __construct() {}

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     */
    private function __clone() {}

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     */
    private function __wakeup() {}

    public static function getInstance()
    {
        static $instance = null;
        if(null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

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

    public function notify($eventName, $event = null)
    {
        if(null !== $event) {
            $this->addSubscriber($eventName, $event);
            $event = $this->getSubscriber($eventName);
        } else {
            $event = $this->getSubscriber($eventName);
        }

        foreach ($this->events[$eventName] as $item) {
            call_user_func($item, $event);
        }
    }
}

final class BankEvents
{
    const MONEY_TRANSFER = 'bank.money_transfer';
    const NEW_EXCHANGE_RATE = 'bank.new_exchange_rate';
}

class Bank
{
    private $dispatcher;
    private $rate;

    public function __construct(EventDispatcher $dispatcher, Rate $rate)
    {
        $this->dispatcher = $dispatcher;
        $this->rate = $rate;
    }

    public function transferMoney(Transfer $transfer)
    {
        $transfer->withdraw();
        $this->dispatcher->notify(BankEvents::MONEY_TRANSFER);
    }

    public function changeRateTo(Rate $rateTo)
    {
        $oldRate = $this->rate;
        $this->rate = $rateTo;

        $this->dispatcher->notify(BankEvents::NEW_EXCHANGE_RATE, new ExchangeRateChangedEvent($oldRate, $rateTo));
    }
}
//
//// We will create another way of using listener
//class AnotherDepartmentListener
//{
//    public function onMoneyTransfer(MoneySendEvent $moneySendEvent)
//    {
//        printf("AnotherDepartmentListener was trigered <br>", $moneySendEvent->getTransfer()->getMoney()->getAmount());
//    }
//}

// Stock market wants to know about the money transfers
class StockMarket
{
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->dispatcher->addListener(BankEvents::MONEY_TRANSFER, array($this, 'onMoneyTransfer'));
    }

    public function onMoneyTransfer(MoneySendEvent $event)
    {
        printf("Stockmarket have been notified about the Transfer (Sender: %s (<strong>%s</strong>) and Receiver: %s (<strong>%s</strong>)) <br>",
            $event->getTransfer()->getSender()->getUser()->getName(),
            $event->getTransfer()->getSender()->getDisposableMoney()->getAmount(), $event->getTransfer()->getReceiver()->getUser()->getName(),
            $event->getTransfer()->getReceiver()->getDisposableMoney()->getAmount());
    }
}
// AnyInterestedIn wants to know about the money transfers
class HumanResources
{
    private $dispatcher;
    private $userRepository;
    private $userSpecification;

    public function __construct(EventDispatcher $dispatcher, UsersRepository $usersRepository, UserSpecification $userSpecification)
    {
        $this->dispatcher = $dispatcher;
        $this->userRepository = $usersRepository;
        $this->userSpecification = $userSpecification;

        $this->dispatcher->addListener(BankEvents::MONEY_TRANSFER, array($this, 'onMoneyTransfer'));
        $this->dispatcher->addListener(BankEvents::NEW_EXCHANGE_RATE, array($this, 'onExchangeRateChanged'));
    }

    public function onMoneyTransfer(MoneySendEvent $event)
    {
        printf("HumanResources have been notified about the Transfer (Sender: %s and Receiver: %s) <br>",
            $event->getTransfer()->getSender()->getUser()->getName(),
            $event->getTransfer()->getReceiver()->getUser()->getName());
    }

    public function onExchangeRateChanged(ExchangeRateChangedEvent $event)
    {
        $this->recalculate($event->getRateBefore(), $event->getRateAfter());
        printf("HumanResources have been notified about the Changed Rate from (%s) to (%s) <br>",
            $event->getRateBefore(), $event->getRateAfter());
    }

    private function recalculate($rateBefore, $rateAfter)
    {
        $iterator = $this->userRepository->getIterator();

        $iterator->rewind();

        while($iterator->valid()) {

            if($this->userSpecification->isSatisfiedBy($iterator->current())) {
                $iterator->current()->multiplyByRate(Rate::create($rateAfter));

                printf("<strong>Recalculating salaries by isSatisfiedBy condition for (User: %s with Money: %s) from %s to %s</strong>. <br>",
                    $iterator->current()->getName(), $iterator->current()->getMoney()->getAmount(), $rateBefore, $rateAfter);
            };

            $iterator->next();
        }
    }
}

/**
 * Specification pattern
 */

interface UserSpecification
{
    public function isSatisfiedBy(User $user);
}

class UsernameIsUnique implements UserSpecification
{
    private $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    public function isSatisfiedBy(User $user)
    {
        if($this->usersRepository->findByUsername($user->getName())) {
            return false;
        }

        return true;
    }
}

class UsernameIsOnly5Letters implements UserSpecification
{
    public function isSatisfiedBy(User $user)
    {
        if(strlen(trim($user->getName())) > 5) {
            return false;
        }

        return true;
    }
}

class User
{
    private $name;
    private $money;

    public function __construct($name, Money $money)
    {
        $this->name = $name;
        $this->money = $money;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMoney()
    {
        return $this->money;
    }

    public function multiplyByRate(Rate $rate)
    {
        $this->money->setAmount($this->money->getAmount() * $rate->getRate());
    }
}

/**
 * Repository Pattern
 */

class UsersRepository implements IteratorAggregate
{
    private $users = array();

    public function add(User $user)
    {
        $id = spl_object_hash($user);
        $this->users[$id] = $user;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->users);
    }

    public function findByUsername($name)
    {
        $iterator = $this->getIterator();

        $iterator->rewind();

        while($iterator->valid()) {

            if($iterator->current()->getName() === $name) {
                return $iterator->current();
            }

            $iterator->next();
        }
    }
}
//////////////////////////////

$repository = new UsersRepository();
$repository->add(new User('Kazlas', Money::create(55.20)));
$repository->add(new User('Kazlas', Money::create(55.20)));
$repository->add(new User('Zigma', Money::create(120)));
$repository->add(new User('Vaida', Money::create(25)));
$repository->add(new User('Kazlaitis', Money::create(70.80)));
#################################################################################################################

//$userSpecification = new UsernameIsUnique($repository);
$userSpecification = new UsernameIsOnly5Letters();

$dispatcher = EventDispatcher::getInstance();
$stockMarket = new StockMarket($dispatcher);
$humanResources = new HumanResources($dispatcher, $repository, $userSpecification);

$transfer = new Transfer(
                new Sender(
                        new User('Robertas', Money::create(3000)),
                        Money::create(2000)
                ), new Receiver(
                        new User('Povilas', Money::create(1500)),
                        Money::create(1000)
                ), Money::create(555.55)
            );
$dispatcher->addSubscriber('bank.money_transfer', new MoneySendEvent($transfer));

try {
    $bank = new Bank($dispatcher, Rate::create(2.5));
    $bank->transferMoney($transfer);
    $bank->transferMoney($transfer);
    $bank->changeRateTo(Rate::create(5));
    $bank->changeRateTo(Rate::create(9));
    $bank->changeRateTo(Rate::create(10));
}
catch(InsufficientMoneyAmountException $e) {
    printf("Insufficient Money Amount", $e->getMessage());
}
catch(Exception $e) {
    printf("General", $e->getMessage());
}
