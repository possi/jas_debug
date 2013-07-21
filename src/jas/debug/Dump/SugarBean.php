<?php

namespace jas\debug\Dump;

class SugarBean extends Base {
    const TCLASS = 'sugarbean';
    
    public static $valid_attr = array(
        'id',
        //'new_schema',
        'new_with_id',
        //'processed_dates_times', 'process_save_dates', 'number_formatting_done',
        //'save_from_post', 'duplicates_found',
        'table_name', 'object_name', 'module_dir',
        'update_date_modified', 'update_modified_by', 'update_date_entered', 'set_created_by',
        'deleted',
    );
    
    public function getRecursion(&$val) {
        $string = $this->display(Display::REFERENCE, "&") .
            $this->display(Display::TYPE, "SugarBean") .
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
            $this->display(Display::TYPE, "SugarBean") .
            $this->display(Display::TYPE_ATTR_VAL, "(".
                $this->display(Display::TYPE_CONT_VAL, get_class($object))
                . ")")
                . '#' . Dumper::getObjectId($object)
                ." ".$this->getCollapsedDisplay(true));
    }
    public function get(&$val) {
        $class = get_class($val);
        $string = $this->display(Display::TYPE, "SugarBean") .
            $this->display(Display::TYPE_ATTR_VAL, "(".
                $this->display(Display::TYPE_CONT_VAL, $class)
                . ")")
                . '#' . Dumper::getObjectId($object);
        $string .= $this->d->nl();
        
        $valid_attr = self::$valid_attr;
        foreach (array_keys($bean->field_defs) as $key) {
            if (!in_array($key, $valid_attr)) {
                $valid_attr[] = $key;
            }
        }
        if (isset($bean->custom_fields) && isset($bean->custom_fields->avail_fields)) {
            foreach (array_keys($bean->custom_fields->avail_fields) as $key) {
                if (!in_array($key, $valid_attr)) {
                    $valid_attr[] = $key;
                }
            }
        }
        
        $s = "";
        foreach ($valid_attr as $key) {
            if (property_exists($bean, $key) && !isset($bean->$key)) {
                $val = null;
            } elseif (property_exists($bean, $key)) {
                $val =& $bean->$key;
            } else {
                continue;
            }
            $key = is_string($key) ?
            $this->display(Display::VAL_QUOTES, '"') .
            $this->display(Display::ATTR_KEY_VAL, $key) .
            $this->display(Display::VAL_QUOTES, '"') :
            $this->display(Display::ATTR_KEY_VAL, $key);
            
            $s .= $this->display(Display::ATTR_KEY, 
                    $this->display(Display::VAL_QUOTES, '[') . $key . $this->display(Display::VAL_QUOTES, ']')
                );
            $s .= $this->display(Display::ATTR_ASSIGN, " => ");
            $s .= $this->d->d($val);
        }
        if (!empty($s))
            $string .= $this->d->indent($s);
        $string .= "}";
        return $this->echoType(self::TCLASS, $string);
    }
}