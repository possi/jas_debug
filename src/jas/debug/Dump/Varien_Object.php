<?php

namespace jas\debug\Dump;

use jas\debug\Dumper;

class Varien_Object extends Base {
    const TCLASS = 'varienobj';
    
    private $p;
    public function __construct(Dumper $d) {
        parent::__construct($d);
        $this->p = new Primitive($d);
    }
    
    public function getRecursion(&$val) {
        $string = $this->display(Display::REFERENCE, "&") .
        $this->display(Display::TYPE, "Varien_Object") .
        $this->display(Display::TYPE_ATTR_VAL, "(".
            $this->display(Display::TYPE_CONT_VAL, get_class($object))
            . ")")
            . '#' . Dumper::getObjectId($object)
            ." (*rekursion*)";
        return $this->echoType(self::TCLASS, $string);
    }
    
    public function getPrototype(&$val) {
        return $this->echoType(self::TCLASS,
            $this->display(Display::REFERENCE, "&") .
            $this->display(Display::TYPE, "Varien_Object") .
            $this->display(Display::TYPE_ATTR_VAL, "(".
                $this->display(Display::TYPE_CONT_VAL, get_class($object))
                . ")")
            . '#' . Dumper::getObjectId($object)
            ." ".$this->getCollapsedDisplay());
    }
    
    public function get(&$val) {
        $class = get_class($val);
        $string = $this->display(Display::TYPE, "Varien_Object") .
        $this->display(Display::TYPE_ATTR_VAL, "(".
            $this->display(Display::TYPE_CONT_VAL, $class)
            . ")")
            . '#' . Dumper::getObjectId($object);
        $string .= " {".$this->d->nl();
        
        
        $s = "";
        
        $key = 
            $this->display(Display::VAL_QUOTES, '"') .
            $this->display(Display::ATTR_KEY_VAL, '_data') .
            $this->display(Display::VAL_QUOTES, '"');
        $s .= $this->display(Display::ATTR_KEY,
                $this->display(Display::VAL_QUOTES, '[') .
                $key .
                $this->display(Display::KEY_VISIBILITY, ':protected') .
                $this->display(Display::VAL_QUOTES, ']')
            );
        $s .= $this->display(Display::ATTR_ASSIGN, " => ");
        $s .= $this->p->get($val->getData());

        $string .= $this->d->indent($s);
        $string .= "}";
        return $this->echoType(self::TCLASS, $string);
    }
}