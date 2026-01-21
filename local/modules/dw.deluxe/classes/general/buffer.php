<?php
final class DwBuffer
{
	public static function modifyBuffer(&$buffer){

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		if(defined("SITE_TEMPLATE_PATH") && !$request->isAdminSection()){
			$buffer = str_replace("favicon.ico", "favicon.ico?v=".filemtime($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/images/favicon.ico"), $buffer);
			$buffer = str_replace("logo.png", "logo.png?v=".filemtime($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/images/logo.png"), $buffer);
			$buffer = str_replace("<script type=\"text/javascript\"", "<script", $buffer);
		}

		return $buffer;
	}

}
