<?php

namespace jas\debug\Dump;
use jas\debug\Dumper;

abstract class Base {
    /**
     * @var Dumper
     */
    protected $d;
    public function __construct(Dumper $d) {
        $this->d = $d;
    }
    
    public function display($displayClass, $value) {
        return $this->d->et($displayClass, $value);
    }
    public function echoType($typeClass, $value) {
        return $this->d->er($typeClass, $value);
    }
    
    public function getCollapsedDisplay() {
        return $this->display(Display::COLLAPSED, 
            '{' . $this->display(Display::PLACEHOLDER) . '}');
    }
    
    abstract public function get(&$value);
    abstract public function getPrototype(&$value);
    abstract public function getRecursion(&$value);
}