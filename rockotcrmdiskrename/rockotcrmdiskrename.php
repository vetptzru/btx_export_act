<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Driver;
use Bitrix\Bizproc\Activity\PropertiesDialog;

class CBPRockotCrmDiskRename extends CBPActivity
{
    public $dealId;
    public $newName;

    private static $UF_DEAL = "UF_CRM_1679410842";
    private static $IS_DEBUG = true;

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array("dealId", "newName");
    }

    public function Execute()
    {
        CBPRockotCrmDiskRename::debugInLog("\n\n> Start BP");

        // // Check modules
        // if (!CBPRockotCrmDiskRename::checkModules()) {
        //     return CBPActivityExecutionStatus::Closed;
        // }

        // CBPRockotCrmDiskRename::debugInLog("> All modules is included");
        
        // // Get Deal info
        // $deal = CBPRockotCrmDiskRename::getDealInfo($this->GetDocumentId());
        // if (!$deal) {
        //     return CBPActivityExecutionStatus::Closed;
        // }

        // CBPRockotCrmDiskRename::debugInLog("> Has deal info");
        // CBPRockotCrmDiskRename::debugInLog(var_export($deal));
        
        // // Rename folder in group
        // $renameStatus = CBPRockotCrmDiskRename::renameFolder($deal["groupId"], $deal["title"]);
        // if ($renameStatus) {
        //     return CBPActivityExecutionStatus::Closed;
        // }

        // CBPRockotCrmDiskRename::debugInLog("> Folder has been renamed");
        // CBPRockotCrmDiskRename::debugInLog(var_export($renameStatus));

        

        return \CBPActivityExecutionStatus::Closed;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            "properties_dialog.php",
            array(
                "formName" => $formName,
                "dialog" => new PropertiesDialog(
                    $activityName,
                    $workflowTemplate,
                    $workflowParameters,
                    $workflowVariables,
                    $currentValues
                ),
            )
        );
    }

    

    /**
     * Check modules
     */
    private static function checkModules() {
        if (!Loader::includeModule('disk') || !Loader::includeModule('crm')) {
            return false;
        }
        return true;
    }

    /**
     * Get current deal
     */
    private function getDealInfo($documentId) {
        $result = ["groupId" => null, "title" => null];
        
        if (strpos($documentId[2], 'DEAL_') != 0) {
            return false;
        }

        $dealId = substr($documentId[2], 5);
        $groupInfo = CBPRockotCrmDiskRename::getGroupIdByDeal($dealId); 
        if (!$groupInfo) {
            return false;
        }

        $result["groupId"] = $groupInfo["groupId"];
        $result["title"] = $groupInfo["title"];

        return $result;
    }

    /**
     * Get group for deal
     */
    public static function getGroupIdByDeal($dealId) {
        $dbRes = CCrmDeal::GetListEx([], ["ID" => $dealId], false, false, ["TITLE", "UF_CRM_1679410842"]);
        while ($deal = $dbRes->Fetch()) {
            $currentUrl = $deal[CBPRockotCrmDiskRename::UF_DEAL];
            if (!$currentUrl) {
                return null;
            }

            $parsed = parse_url($currentUrl);
            $groupId = explode("/", $parsed["path"])[3];
            
            if (!$groupId) {
                return null;
            }
            return ["groupId" => $groupId, "title" => $deal["TITLE"]];
        }
        return null;
    }

    /**
     * Rename folter
     */
    public static function renameFolder($groupId, $dealTitle) {
        $driver = \Bitrix\Disk\Driver::getInstance(); 
	    $storage = $driver->getStorageByGroupId($groupId);

        if(!$storage->rename($dealTitle)){
            return false;
        }

        return true;
    }

    private static function debugInLog($message) {
        if (CBPRockotCrmDiskRename::IS_DEBUG) {
            _printBP_($message);
        }
    }
}

function _printBP_($mes) {
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/deb.log", $mes."\n", FILE_APPEND);
}