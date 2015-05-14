<?php

interface LinkInterface
{
    public function display();
}

class Link implements LinkInterface
{
    public function display()
    {
        return "<p>Link</p>";
    }
}

class StrongLinkDecorator implements LinkInterface
{
    private $link;

    public function __construct(LinkInterface $link)
    {
        $this->link = $link;
    }

    public function display()
    {
        return sprintf("<strong>%s</strong>", $this->link->display());
    }
}

class ItalicLinkDecorator implements LinkInterface
{
    private $link;

    public function __construct(LinkInterface $link)
    {
        $this->link = $link;
    }

    public function display()
    {
        return sprintf("<i>%s</i>", $this->link->display());
    }
}

class ColoredDivDecorator implements LinkInterface
{
    private $link;
    private $color;

    public function __construct(LinkInterface $link, ColorInterface $color)
    {
        $this->link = $link;
        $this->color = $color;
    }

    public function display()
    {
        return sprintf("<div style=\"color:%s\">%s</div>", $this->color->color(), $this->link->display());
    }
}

interface ColorInterface
{
    public function color();
}

class RgbColors implements ColorInterface
{
    private $red;
    private $green;
    private $blue;

    public function __construct($red, $green, $blue)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    public function getRed()
    {
        return $this->red;
    }

    public function getGreen()
    {
        return $this->green;
    }

    public function getBlue()
    {
        return $this->blue;
    }

    public function color()
    {
        return sprintf("rgb(%s,%s,%s)",$this->getRed(), $this->getGreen(), $this->getBlue());
    }
}

class WebColors implements ColorInterface
{
    public function color()
    {
        return sprintf("#%s", 868686);
    }
}

#####################################################################################################################
# Decorator pattern + Strategy f.g RgbColors or WebColors
#####################################################################################################################
$html = new Link();
$html = new StrongLinkDecorator($html);
$html = new ItalicLinkDecorator($html);
$html = new ColoredDivDecorator($html, new RgbColors(150,150,150));
echo $html->display();
