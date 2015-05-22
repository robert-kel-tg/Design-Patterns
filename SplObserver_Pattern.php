<?php

class Login implements SplSubject {

    const UNKNOWN_USER = 1;
    const INCORRECT_PWD = 2;
    const ALREADY_LOGGED_IN = 3;
    const ALLOW = 4;

    private $status = array();
    private $storage;

    function __construct() {
        $this->storage = new SplObjectStorage();
    }

    function init( $username, $password, $ip ) {

        // Let's simulate different login procedures
        $this->setStatus( rand( 1, 4 ), $username, $ip);

        // Notify all the observers of a change
        $this->notify();

        if ( $this->status[0] == self::ALLOW ) {
            return true;
        }

        return false;

    }

    private function setStatus( $status, $username, $ip ) {
        $this->status = array( $status, $username, $ip );
    }

    function getStatus() {
        return $this->status;
    }

    function attach( SplObserver $observer ) {
        $this->storage->attach( $observer );
    }

    function detach( SplObserver $observer ) {
        $this->storage->detach( $observer );
    }

    function notify() {

        foreach ( $this->storage as $observer ) {
            $observer->update( $this );
        }

    }

}


class Security implements SplObserver {

    function update( SplSubject $SplSubject ) {

        $status = $SplSubject->getStatus();

        switch ( $status[0] ) {

            case Login::INCORRECT_PWD:
                echo __CLASS__ . ": Incorrect password. Storing attempt, and emailing admin on third attempt.";
                break;

            case Login::UNKNOWN_USER:
                echo __CLASS__ . ": Unknown user. Storing attempt, and block IP on tenth try.";
                break;

            case Login::ALREADY_LOGGED_IN:
                echo __CLASS__ . ": User is already logged in, check to see if IP addresses are the same.";
                break;

        }

    }

}



class Logging implements SplObserver {

    function update( SplSubject $SplSubject ) {

        $status = $SplSubject->getStatus();

        switch ( $status[0] ) {

            case Login::INCORRECT_PWD:
                echo __CLASS__ . ": Logging incorrect password attempt to error file.";
                break;

            case Login::UNKNOWN_USER:
                echo __CLASS__ . ": Logging unknown user attempt to error file.";
                break;

            case Login::ALREADY_LOGGED_IN:
                echo __CLASS__ . ": Logging already logged in to error file.";
                break;

            case Login::ALLOW:
                echo __CLASS__ . ": Logging to access file.";

        }

    }

}


#################################################################################################################

$login = new Login();
$login->attach( new Security() );
$login->attach( new Logging() );

if ( $login->init( "craigsefton", "password", "127.0.0.1" ) ) {
    echo "User logged in!";
} else {
    echo "<pre>";
    print_r( $login->getStatus() );
    echo "</pre>";
}