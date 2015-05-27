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

class UserAddedEvent
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}

class UserUpdatedEvent
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
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

    private function addSubscriber($eventName, $event)
    {
        $this->subscribers[$eventName] = $event;
    }

    private function getSubscriber($eventName)
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

final class UserEvents
{
    const NEW_USER = 'user.new_user';
    const UPDATE_USER = 'user.update_user';
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

        $this->dispatcher->notify(BankEvents::MONEY_TRANSFER, new MoneySendEvent($transfer));
    }

    public function changeRateTo(Rate $rateTo)
    {
        $oldRate = $this->rate;
        $this->rate = $rateTo;

        $this->dispatcher->notify(BankEvents::NEW_EXCHANGE_RATE, new ExchangeRateChangedEvent($oldRate, $rateTo));
    }
}

// We will create another way of using listener
class AnotherDepartmentListener
{
    public function onMoneyTransfer(MoneySendEvent $moneySendEvent)
    {
        printf("AnotherDepartmentListener was trigered <br>", $moneySendEvent->getTransfer()->getMoney()->getAmount());
    }
}

class StockMarket
{

}

// Stock market wants to know about the money transfers
class StockMarketListener
{
    private $stockMarket;

    public function __construct($stockMarket)
    {
        $this->stockMarket = $stockMarket;
    }

    public function onMoneyTransfer(MoneySendEvent $event)
    {
        printf("Stockmarket have been notified about the Transfer (Sender: %s (<strong>%s</strong>) and Receiver: %s (<strong>%s</strong>)) <br>",
            $event->getTransfer()->getSender()->getUser()->getName(),
            $event->getTransfer()->getSender()->getDisposableMoney()->getAmount(), $event->getTransfer()->getReceiver()->getUser()->getName(),
            $event->getTransfer()->getReceiver()->getDisposableMoney()->getAmount());
    }

    public function onUserAdded(UserAddedEvent $userAddedEvent)
    {
        printf("Stockmarket Notified about new user: %s <br>", $userAddedEvent->getUser()->getName());
    }
}
// AnyInterestedIn wants to know about the money transfers
class HumanResources
{
    private $userRepository;
    private $userSpecification;

    public function __construct(UsersRepository $usersRepository, UserSpecification $userSpecification)
    {
        $this->userRepository = $usersRepository;
        $this->userSpecification = $userSpecification;
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

    public function onUserAdded(UserAddedEvent $userAddedEvent)
    {
        printf("HumanResources Notified about new user: %s <br>", $userAddedEvent->getUser()->getName());
    }

    public function onUserUpdated(UserUpdatedEvent $userUpdatedEvent)
    {
        printf("HumanResources Notified about updated user: %s <br>", $userUpdatedEvent->getUser()->getName());
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

class AccountManager
{
    private $dispatcher;
    private $usersRepository;

    public function __construct(EventDispatcher $dispatcher, UsersRepository $usersRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->usersRepository = $usersRepository;
    }

    public function addNewAccount(User $user)
    {
        $this->usersRepository->add($user);

        $this->dispatcher->notify(UserEvents::NEW_USER, new UserAddedEvent($user));
    }

    public function updateAccount(User $nUser)
    {
        $this->usersRepository->update($nUser);

        $this->dispatcher->notify(UserEvents::UPDATE_USER, new UserUpdatedEvent($nUser));
    }
}

class Mailer
{
    private $to;
    private $from;
    private $body;

    public function __construct($to = null, $from = null, $body = null)
    {
        $this->to = $to;
        $this->from = $from;
        $this->body = $body;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setTo($to)
    {
        $this->to = $to;
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function send()
    {
        printf("Send email. %s. <br>", $this->body);
    }

    public function onUserUpdated(UserUpdatedEvent $userUpdatedEvent)
    {
        $this->to = $userUpdatedEvent->getUser()->getEmail();
        $this->from = 'System <sys@mail.com>';
        $this->body = sprintf("System message: User <strong>%s</strong> update notification.", $userUpdatedEvent->getUser()->getname());
        $this->send();
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
    private $email;

    public function __construct($name, Money $money, $email)
    {
        $this->name = $name;
        $this->money = $money;
        $this->email = $email;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMoney()
    {
        return $this->money;
    }

    public function getEmail()
    {
        return $this->email;
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

    public function getUserIdentity(User $user)
    {
        return spl_object_hash($user);
    }

    public function add(User $user)
    {
        $id = $this->getUserIdentity($user);
        $this->users[$id] = $user;
    }

    public function update(User $uUser)
    {
        $user = $this->findByUsername($uUser->getName());
        $this->users[$this->getUserIdentity($user)] = $uUser;
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
$repository->add(new User('Kazlas', Money::create(55.20), 'kazlas@mail.com'));
$repository->add(new User('Kazlas', Money::create(55.20), 'kaz@gmail.com'));
$repository->add(new User('Zigma', Money::create(120), 'zigma@inbox.lt'));
$repository->add(new User('Vaida', Money::create(25), 'vaida@one.lt'));
$repository->add(new User('Kazlaitis', Money::create(70.80), 'kazlaitis@gmail.com'));
#################################################################################################################

//$userSpecification = new UsernameIsUnique($repository);
$userSpecification = new UsernameIsOnly5Letters();

$mailer = new Mailer();
$humanResources = new HumanResources($repository, $userSpecification);
$stockMarketListener = new StockMarketListener(new StockMarket());
$anotherDepartmentListener = new AnotherDepartmentListener();

$dispatcher = EventDispatcher::getInstance();
$dispatcher->addListener(BankEvents::MONEY_TRANSFER, array($stockMarketListener, 'onMoneyTransfer'));
$dispatcher->addListener(BankEvents::MONEY_TRANSFER, array($humanResources, 'onMoneyTransfer'));
$dispatcher->addListener(BankEvents::MONEY_TRANSFER, array($anotherDepartmentListener, 'onMoneyTransfer'));
$dispatcher->addListener(BankEvents::NEW_EXCHANGE_RATE, array($humanResources, 'onExchangeRateChanged'));
$dispatcher->addListener(UserEvents::NEW_USER, array($humanResources, 'onUserAdded'));
$dispatcher->addListener(UserEvents::UPDATE_USER, array($humanResources, 'onUserUpdated'));
$dispatcher->addListener(UserEvents::UPDATE_USER, array($mailer, 'onUserUpdated'));

$transfer = new Transfer(
                new Sender(
                        new User('Robertas', Money::create(3000), 'rob@mail.com'),
                        Money::create(2000)
                ), new Receiver(
                        new User('Povilas', Money::create(1500), 'pov@inbox.lt'),
                        Money::create(1000)
                ), Money::create(555.55)
            );

try {
    $bank = new Bank($dispatcher, Rate::create(2.5), $repository);
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


try {
    $accountManager = new AccountManager($dispatcher, $repository);
    $accountManager->addNewAccount(new User('John', Money::create(5000), 'blank@mail.lt'));
    $accountManager->updateAccount(new User('John', Money::create(4500), 'test@inbox.lt'));
    $accountManager->addNewAccount(new User('Paul', Money::create(3300), 'paul@mail.lt'));
}
catch (Exception $e) {
    printf("User Exception", $e->getMessage());
}
