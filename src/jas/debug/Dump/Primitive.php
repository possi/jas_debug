<?php

namespace jas\debug\Dump;

use jas\debug\Dumper;
use jas\debug\Dump\Display;

class Primitive extends Base {
    public function get(&$val) {
        if (is_object($val)) {
            $type = 'object';
            $string = $this->getObject($val);
        } elseif (is_array($val)) {
            $type = 'array';
            $string = $this->getArray($val);
        } elseif (is_int($val)) {
            $type = 'int';
            $string = $this->display(Display::TYPE, "int")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_CONT_VAL, $val)
                        . ")");
        } elseif (is_float($val)) {
            $type = 'float';
            $string = $this->display(Display::TYPE, "float")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_CONT_VAL, $val)
                        . ")");
        } elseif (is_bool($val)) {
            $type = 'bool';
            $string = $this->display(Display::TYPE, "bool")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_CONT_VAL, $val ? 'true' : 'false')
                        . ")");
        } elseif (is_string($val)) {
            $type = 'string';
            $string = $this->display(Display::TYPE, "string")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_ATTR_VAL, strlen($val))
                        . ")")." "
                    . $this->display(Display::VAL_QUOTES, '"')
                    . $this->display(Display::TYPE_CONT_VAL, $val)
                    . $this->display(Display::VAL_QUOTES, '"');
        } elseif (is_null($val)) {
            $type = 'null';
            $string = $this->display(Display::TYPE_CONT_VAL, "NULL");
        } elseif (is_resource($val)) {
            $type = 'resource';
            $string = $this->display(Display::TYPE, "resource")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_CONT_VAL, (int)$val)
                        . ")")." of type ("
                    . $this->display(Display::TYPE_ATTR_VAL, get_resource_type($val));
        } else {
            $type = 'unknown_type';
            $string = $this->display(Display::TYPE, "unknown_type")
                    . $this->display(Display::TYPE_ATTR_VAL, "(".
                            $this->display(Display::TYPE_ATTR_VAL, gettype($val))
                        . ")")." ".$this->display(Display::TYPE_CONT_VAL, (string)$val);
        }
        return $this->echoType($type, $string);
    }
    
    public function getRecursion(&$val) {
        if (is_object($val)) {
            $type = 'object';
            $string = $this->display(Display::REFERENCE, "&") .
                $this->display(Display::TYPE, "object") .
                $this->display(Display::TYPE_ATTR_VAL, "(".
                    $this->display(Display::TYPE_CONT_VAL, get_class($object))
                    . ")")
                    . '#' . Dumper::getObjectId($object)
                    ." (*rekursion*)";
        } elseif (is_array($val)) {
            $type = 'array';
            $string = $this->display(Display::REFERENCE, "&") .
                $this->display(Display::TYPE, "array") .
                $this->display(Display::TYPE_ATTR_VAL, "(".
                    $this->display(Display::TYPE_CONT_VAL, count($object))
                    . ")")." (*rekursion*)";
        }
        return $this->echoType($type, $string);
    }
    
    public function getPrototype(&$val) {
        if (is_object($val)) {
            $type = 'object';
            $string = $this->display(Display::REFERENCE, "&") .
                $this->display(Display::TYPE, "object") .
                $this->display(Display::TYPE_ATTR_VAL, "(".
                    $this->display(Display::TYPE_CONT_VAL, get_class($object))
                    . ")")
                    . '#' . Dumper::getObjectId($object)
                    ." ".$this->getCollapsedDisplay();
        } elseif (is_array($val)) {
            $type = 'array';
            $string = $this->display(Display::TYPE, "array") .
                $this->display(Display::TYPE_ATTR_VAL, "(".
                    $this->display(Display::TYPE_CONT_VAL, count($array))
                    . ")")
                    ." ".$this->getCollapsedDisplay();
        } else {
            return $this->get($val);
        }
        return $this->echoType($type, $string);
    }
    protected function getArray(&$array) {
        $string = $this->display(Display::TYPE, "array") .
            $this->display(Display::TYPE_ATTR_VAL, "(".
                $this->display(Display::TYPE_CONT_VAL, count($array))
                . ")")." {".$this->d->nl();

        $s = "";
        foreach ($array as $key => &$prop) {
            $key = is_string($key) ? 
                        $this->display(Display::VAL_QUOTES, '"') . 
                        $this->display(Display::ATTR_KEY_VAL, $key) . 
                        $this->display(Display::VAL_QUOTES, '"') :
                    $this->display(Display::ATTR_KEY_VAL, $key);
            
            $s .= $this->display(Display::ATTR_KEY, 
                    $this->display(Display::VAL_QUOTES, '[') . $key . $this->display(Display::VAL_QUOTES, ']')
                );
            $s .= $this->display(Display::ATTR_ASSIGN, " => ");
            $s .= $this->d->d($prop);
        }
        if (!empty($s))
            $string .= $this->d->indent($s);
        $string .= "}";
        return $string;
    }
    
    protected function getObject(&$object) {
        $class = get_class($object);
        $string = $this->display(Display::TYPE, "object") .
            $this->display(Display::TYPE_ATTR_VAL, "(".
                $this->display(Display::TYPE_CONT_VAL, $class)
                . ")")
                . '#' . Dumper::getObjectId($object);
        $rfl = new \ReflectionObject($object);
        $string .= " (".count($props = $rfl->getProperties()).") {".$this->d->nl();

        $s = "";
        foreach ($props as $prop) {
            if ($prop->isStatic())
                continue;
            $prop->setAccessible(true);
            $key = $prop->getName();
            $pclass = $prop->class == $class || !$prop->isPrivate() ? '' : ':'.$prop->class;
            
            $visible = $prop->isProtected() ? ':protected' :
                       ($prop->isPrivate() ? ':private' : '');
            
            $pclass = $this->display(Display::KEY_CLASS, $pclass);
            $visible = $this->display(Display::KEY_VISIBILITY, $visible);

            $key = is_string($key) ?
                $this->display(Display::VAL_QUOTES, '"') .
                $this->display(Display::ATTR_KEY_VAL, $key) .
                $this->display(Display::VAL_QUOTES, '"') :
                $this->display(Display::ATTR_KEY_VAL, $key);
            
            $s .= $this->display(Display::ATTR_KEY, 
                    $this->display(Display::VAL_QUOTES, '[') . $key.$pclass.$visible . $this->display(Display::VAL_QUOTES, ']')
                );
            $s .= $this->display(Display::ATTR_ASSIGN, " => ");
            $s .= $this->d->d($prop->getValue($object));
        }
        if (!empty($s))
            $string .= $this->d->indent($s);
        $string .= "}";
        return $string;
    }
}