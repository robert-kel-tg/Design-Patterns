<?php

require_once "vendor/autoload.php";

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class Door
{
    const OPENED = 'opened';
    const CLOSED = 'closed';

    private $status;

    private function __construct($anStatus)
    {
        $this->status = $anStatus;
    }

    public static function open()
    {
        return new self(Door::OPENED);
    }

    public static function close()
    {
        return new self(Door::CLOSED);
    }

    public function equalsTo(self $anStatus)
    {
        return $this->status === $anStatus->status;
    }

    public function getStatus()
    {
        return $this->status;
    }
}

class Button
{
    const ACTIVE = 'activated';
    const INACTIVE = 'inactive';

    private $status;

    private function __construct($anStatus)
    {
        $this->status = $anStatus;
    }

    public static function active()
    {
        return new self(Button::ACTIVE);
    }

    public static function inactive()
    {
        return new self(Button::INACTIVE);
    }

    public function equalsTo(self $anStatus)
    {
        return $this->status === $anStatus->status;
    }
}

class Elevator
{
    const BUSY = 'busy';
    const FREE = 'free';

    private $status;
    private $door;
    private $button;

    public function __construct(Door $door, Button $button)
    {
        $this->status = Elevator::FREE;
        $this->door = $door;
        $this->button = $button;
    }

    private function setStatus($anStatus)
    {
        $this->status = $anStatus;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function open()
    {
        $this->door = $this->door->open();
        $this->button = $this->button->active();
    }

    public function close()
    {
        $this->door = $this->door->close();
        $this->button = $this->button->inactive();
        $this->status = Elevator::BUSY;
    }
}

class ElevatorController
{
    private $elevator;
    private $dispatcher;

    function __construct(Elevator $elevator, EventDispatcherInterface $dispatcher)
    {
        $this->elevator = $elevator;
        $this->dispatcher = $dispatcher;
    }

    public function getElevator()
    {
        return $this->elevator;
    }

    public function activate()
    {
        $this->dispatcher->dispatch(ElevatorEvents::DOOR_BUTTON_PUSHED, new DoorButtonEvent());
    }

    public function move()
    {
        $this->dispatcher->dispatch(ElevatorEvents::FLOOR_BUTTON_PUSHED, new FloorButtonEvent());
    }
}

final class ElevatorEvents
{
    const DOOR_BUTTON_PUSHED = 'door.button_pushed';
    const FLOOR_BUTTON_PUSHED = 'floor.button_pushed';
}

class DoorButtonEvent extends Event
{
    public function occurredOn()
    {
        return (new DateTimeImmutable("now", new DateTimeZone('Europe/Vilnius')))->format("H:i:s");
    }
}

class FloorButtonEvent extends Event
{
    public function occurredOn()
    {
        return (new DateTimeImmutable("now", new DateTimeZone('Europe/Vilnius')))->format("H:i:s");
    }
}


class ElevatorListener
{
    private $eventSource;
    private $elevator;
    private $door;

    public function __construct(EventSource $eventSource, Elevator $elevator, Door $door)
    {
        $this->eventSource = $eventSource;
        $this->elevator = $elevator;
        $this->door = $door;
    }

    public function onDoorButtonPushed(DoorButtonEvent $elevatorButtonEvent)
    {
        printf("Door button triggered. %s <br>", $elevatorButtonEvent->occurredOn());

        if($this->elevator->getStatus() === Elevator::FREE) {
            $this->elevator->open();
        } else {
            //TODO push to event to queue
            //TODO it makes sense to push that events to RabbitMQ queue to iterate events while condition will be met
            $this->eventSource->enqueue($elevatorButtonEvent);
        }
    }

    public function onFloorButtonPushed(FloorButtonEvent $floorButtonEvent)
    {
        printf("Floor button triggered. %s <br>", $floorButtonEvent->occurredOn());

        $this->elevator->close();
    }

    public function getDoorStatus()
    {
        return $this->door->getStatus();
    }
}

class EventSource extends SplQueue
{

}

$eventSource = new EventSource();


$door = Door::close();
$button = Button::inactive();
$elevator = new Elevator($door, $button);
$elevatorListener = new ElevatorListener($eventSource, $elevator, $door);

$eventDispatcher = new EventDispatcher();
$eventDispatcher->addListener(ElevatorEvents::DOOR_BUTTON_PUSHED, array($elevatorListener, 'onDoorButtonPushed'));
$eventDispatcher->addListener(ElevatorEvents::FLOOR_BUTTON_PUSHED, array($elevatorListener, 'onFloorButtonPushed'));


$elevatorController = new ElevatorController($elevator, $eventDispatcher);

$elevatorController->activate();
echo '<pre>';print_r($elevator);echo '</pre>';

sleep(2);
$elevatorController->move();
echo '<pre>';print_r($elevator);echo '</pre>';

