<?
/**
 * Слой backend обработчика.
 * Bitrix Framework
 * @global CMain $APPLICATION
 */

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$result = array("ERROR" => "");

if (!\Bitrix\Main\Loader::includeModule('sale'))
	$result["ERROR"] = "Error! Can't include module \"Sale\"";
if (!\Bitrix\Main\Loader::includeModule('dellindev.shipment'))
    $result["ERROR"] = "Error! Can't include module \"dellindev.shipment\"";

\Bitrix\Sale\Delivery\Services\Manager::getHandlersList();
$saleModulePermissions = $APPLICATION->GetGroupRight("sale");

if($result["ERROR"] == '' && $saleModulePermissions >= "W" && check_bitrix_sessid())
{
	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']): '';
    if($class == '')
    {
        $result["ERROR"] = "Error! Wrong location class!";
    }

    if (\Bitrix\Main\Loader::includeModule('dellindev.shipment')){
        $ajaxService = new $class;

        switch ($action)
        {
            case "get_termianl_derival":

                /** @var Sale\Handlers\Delivery\AjaxService $ajaxService*/
                $appkey = (isset($_REQUEST['appkey']) && !empty($_REQUEST['appkey']))?
                                        $_REQUEST['appkey'] :
                                        $ajaxService::LoggerInfo($action, 'appkey is null or empty');
                $kladr = (isset($_REQUEST['kladr']) && !empty($_REQUEST['kladr']))?
                                        $_REQUEST['kladr']:
                                        $ajaxService::LoggerInfo($action, 'kladr is null or empty');

                if(mb_strtolower(SITE_CHARSET) != 'utf-8') {
                    $appkey = \Bitrix\Main\Text\Encoding::convertEncoding($appkey, SITE_CHARSET, 'utf-8');
                    $kladr = \Bitrix\Main\Text\Encoding::convertEncoding($kladr, SITE_CHARSET, 'utf-8');
                }


                $result = $ajaxService::getTerminalsForAjax($kladr,$appkey);

                break;
            case "get_city_kladr":           
                $q = isset($_REQUEST['query'])?$_REQUEST['query']: null;
                $appkey = (isset($_REQUEST['appkey']) && !empty($_REQUEST['appkey']))?
                                             $_REQUEST['appkey'] :
                                             $ajaxService::LoggerInfo($action, 'appkey is null or empty');
                if(mb_strtolower(SITE_CHARSET) != 'utf-8')
                    $q = \Bitrix\Main\Text\Encoding::convertEncoding($q, SITE_CHARSET, 'utf-8');
                $result['LIST'] = $ajaxService::searchCityForAjax($q, $appkey);
                break;
            default:
                $result["ERROR"] = "Error! Wrong action!";
                break;
        }
    } else {
        $result["ERROR"] = "Error! Can't include module \"dellindev.shipment\"";
    }
}
else
{
	if($result["ERROR"] == '')
		$result["ERROR"] = "Error! Access denied";
}

if($result["ERROR"] <> '')
	$result["RESULT"] = "ERROR";
else
	$result["RESULT"] = "OK";

if(mb_strtolower(SITE_CHARSET) != 'utf-8')
	$result = \Bitrix\Main\Text\Encoding::convertEncoding($result, SITE_CHARSET, 'utf-8');

header('Content-Type: application/json');
echo  json_encode($result);
\CMain::FinalActions();
die;