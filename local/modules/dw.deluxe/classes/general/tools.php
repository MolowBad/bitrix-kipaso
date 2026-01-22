<?php

namespace DigitalWeb;

IncludeModuleLangFile(__FILE__);

class Tools
{
    private static $instance = false;

	function __construct(){}

    public static function getInstance(){

        if (!self::$instance){
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getComponentHTML($name, $template = ".default", $arParams = array()){

        global $APPLICATION;

        $componentResult = false;

        if(!empty($name)){

            ob_start();

			$APPLICATION->IncludeComponent($name, $template, $arParams);
            $componentResult = ob_get_contents();

			ob_end_clean();

        }

        return $componentResult;

    }


    public static function convertEncoding($data){

        if(is_array($data)){
            return array_map(function($value){
                return \Bitrix\Main\Text\Encoding::convertEncoding($value, "UTF-8", LANG_CHARSET);
            }, $data);
        }

        else{
            return !defined("BX_UTF") ? \Bitrix\Main\Text\Encoding::convertEncoding($data, "UTF-8", LANG_CHARSET) : $data;
        }

    }

}
