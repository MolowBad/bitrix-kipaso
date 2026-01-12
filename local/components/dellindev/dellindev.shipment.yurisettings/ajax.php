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


    $appkey = (isset($_REQUEST['appkey']) && !empty($_REQUEST['appkey']))?
        trim($_REQUEST['appkey']) :
        false;
    $password = (isset($_REQUEST['password']) && !empty($_REQUEST['password']))?
        trim($_REQUEST['password']):
        false;
    $login = (isset($_REQUEST['login']) && !empty($_REQUEST['login']))?
        trim($_REQUEST['login']):
        false;
        //Заглушка для механизма сброса сессии.
//  $flag = (isset($_REQUEST['flag']) && !empty($_REQUEST['flag']))?
//      trim($_REQUEST['flag']):
//      $ajaxService::LoggerInfo($action, 'flag is null or empty');


        if(mb_strtolower(SITE_CHARSET) != 'utf-8') {

        $appkey = \Bitrix\Main\Text\Encoding::convertEncoding($appkey, SITE_CHARSET, 'utf-8');
        $password = \Bitrix\Main\Text\Encoding::convertEncoding($password, SITE_CHARSET, 'utf-8');
        $login = \Bitrix\Main\Text\Encoding::convertEncoding($login, SITE_CHARSET, 'utf-8');

    }

        switch ($action)
        {
            case "get_counteragents":

                /** @var Sale\Handlers\Delivery\AjaxService $ajaxService*/

                $result = $ajaxService::getCounterAgentForAjax($appkey, $login, $password/*, $flag*/);

                break;
            case "get_opf":
                $result = $ajaxService::getOpfDataForAjax($appkey);

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