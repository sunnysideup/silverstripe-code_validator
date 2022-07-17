<?php
/**
 */

namespace Sunnysideup\CodeValidator\Api;

use SilverStripe\ORM\DataObject;

use SilverStripe\Core\Config\Configurable;

class ValidateCodeFieldForObject
{
    use Configurable;

    private static $length = 7;

    private static $strict_length_checking = false;

    private static $php_format_function = 'trim';

    /**
     * @var Array
     */
    private static $replacements = array(
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
     * you can pass an object or a proposed code.
     *
     * @param DataObject|string $obj
     * @param bool $createCode
     * @param string $field
     */

    public function checkCode(string $field, $obj = '', ?bool $createCode = false)
    {
        //exception dealing with Strings
        $config =  $this->Config();
        $formatFunction = $config->get("php_format_function");
        $strictLenghtChecking = $config->get("strict_length_checking");
        $replacements = $config->get("replacements");
        $supposedLength = $config->get("length");
        $isObject = true;
        if (! is_object($obj)) {
            $str = $obj;
            $obj = new DataObject();
            $obj->$field = strval($str);
            $isObject = false;
        }
        if ($createCode) {
            // empty
            if (!$obj->$field) {
                $obj->$field = $this->CreateCode();
            }
            // strict length
            if ($strictLenghtChecking && strlen($obj->$field) !== $supposedLength) {
                $obj->$field = $this->CreateCode();
            }
        } else {
            $obj->$field = trim($obj->$field);
            foreach ($replacements as $regex => $replace) {
                $obj->$field = preg_replace($regex, $replace, $obj->$field);
            }
        }
        if (!$obj->$field) {
            $obj->$field = trim($formatFunction($field)."-NOT-SET");
        }
        //make upper-case
        $obj->$field = trim($formatFunction($obj->$field));
        //check for other ones.
        $count = 0;
        $code = $obj->$field;
        while (
            $isObject &&
            $obj::get()
                ->filter([$field => $obj->$field])
                ->exclude(["ID" => intval($obj->ID) - 0])->exists() &&
            $count < 1000
        ) {
            $obj->$field = $this->CreateCode();
            $count++;
        }

        return $obj->$field;
    }

    public function CreateCode(?int $length = 10)
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // and any other characters
        $rand = '';
        foreach (array_rand($seed, $length) as $k) {
            $rand .= $seed[$k];
        }

        return $rand;
    }
}
