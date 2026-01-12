<?


use  \Sale\Handlers\Delivery\Dellin\AjaxService;
/**
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

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");

if($result["ERROR"] == '' && $saleModulePermissions >= "W" && check_bitrix_sessid())
{
	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']): '';

    if (\Bitrix\Main\Loader::includeModule('dellindev.shipment')) {
        switch ($action) {
            case "terminal_data":
                $delivery_id = (isset($_REQUEST['delivery_id']) && !empty($_REQUEST['delivery_id']))?
                    $_REQUEST['delivery_id'] :
                    AjaxService::LoggerInfo($action, 'delivery_id is null or empty');
                $person_type_id = (isset($_REQUEST['person_type_id']) && !empty($_REQUEST['person_type_id']))?
                    $_REQUEST['person_type_id'] :
                    AjaxService::LoggerInfo($action, 'persom_type_id is null or empty');
                $result = (!empty($delivery_id))?
                                            AjaxService::getTerminalsForAjaxOfSession($delivery_id, $person_type_id):
                                            ['ERROR' => 'delivery_id is null or empty'];

                break;
            default:
                $result["ERROR"] = "Error! Wrong action!";
                break;
        }
    } else {
        $result["ERROR"] = "Error! Can't include module \"dellindev.shipping\"";
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
echo json_encode($result);
\CMain::FinalActions();
die;