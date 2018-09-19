<?php
/**
 */

class ValidateCodeFieldForObject extends Object
{
    private static $length = 7;

    /**
     * @var Array
     */
    private $replacements = array(
        '/&amp;/u' => '-and-',
        '/&/u' => '-and-',
        '/\s/u' => '-', // remove whitespace
        '/[^A-Za-z0-9.\-_]+/u' => '', // remove non-ASCII chars, only allow alphanumeric, dashes and dots.
        '/[\-]{2,}/u' => '-', // remove duplicate dashes
        '/[\_]{2,}/u' => '_', // remove duplicate underscores
        '/^[\.\-_]/u' => '', // Remove all leading dots, dashes or underscores
    );

    /**
     * makes sure that code is unique and gets rid of special characters
     * should be run in onBeforeWrite
     *
     * @param DataObject | String $obj
     * @param Boolean $createCode
     * @param String $field
     */

    public function checkCode($obj, $createCode = false, $field = "Code")
    {
        //exception dealing with Strings
        $isObject = true;
        if (! is_object($obj)) {
            $str = $obj;
            $obj = new DataObject();
            $obj->$field = strval($str);
            $isObject = false;
        }
        if ($createCode) {
            if (!$obj->$field || strlen($obj->$field) != $this->Config()->get("length")) {
                $obj->$field = $this->CreateCode();
            }
        } else {
            $obj->$field = trim($obj->$field);
            foreach ($this->replacements as $regex => $replace) {
                $obj->$field = preg_replace($regex, $replace, $obj->$field);
            }
        }
        if (!$obj->$field) {
            $obj->$field = strtoupper($field)."-NOT-SET";
        }
        //make upper-case
        $obj->$field = trim(strtoupper($obj->$field));
        //check for other ones.
        $count = 0;
        $code = $obj->$field;
        while (
            $isObject && 
            $obj::get()
                ->filter([$field => $obj->$field])
                ->exclude(["ID" => intval($obj->ID) - 0])->Count() > 0 &&
            $count < 1000
        ) {
            $obj->$field = $this->CreateCode();
            $count++;
        }
        
        return $obj->$field;
    }

    public function CreateCode()
    {
        $seed = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // and any other characters
        $rand = '';
        foreach (array_rand($seed, $this->Config()->get("length")) as $k) {
            $rand .= $seed[$k];
        }

        return $rand;
    }
}
