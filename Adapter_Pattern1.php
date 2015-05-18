<?php

interface PaperBookInterface
{
    public function turnPage();
    public function open();
}

class Book implements PaperBookInterface
{
    public function turnPage()
    {
        print("Book turnPage. \n");
    }

    public function open()
    {
        print("Book open. \n");
    }
}


interface EBookInterface
{
    public function pressStart();
    public function pressNext();
}

class Kindle implements EBookInterface
{
    public function pressStart()
    {
        print("Kindle pressStart. \n");
    }

    public function pressNext()
    {
        print("Kindle pressNext. \n");
    }
}


class EBookToPaperBookAdapter implements PaperBookInterface
{
    private $eBook;

    public function __construct(EBookInterface $eBook)
    {
        $this->eBook = $eBook;
    }

    public function turnPage()
    {
        return $this->eBook->pressNext();
    }

    public function open()
    {
        return $this->eBook->pressStart();
    }
}

#####################################################################################################################
$book = new Book();

$adapter = new EBookToPaperBookAdapter(new Kindle());

$adapter->open();
$adapter->turnPage();
