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

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array("dealId", "newName");
    }

    public function Execute()
    {
        $documentId = $this->GetDocumentId();
        if (strpos($documentId[2], 'DEAL_') != 0) {
            return CBPActivityExecutionStatus::Closed;
        }

        $dealId = substr($documentId[2], 5);

        
        if (!Loader::includeModule('disk')) {
           return CBPActivityExecutionStatus::Closed;
        }

        if (!Loader::includeModule('crm')) {
            return CBPActivityExecutionStatus::Closed;
        }

        $dealInfo = CBPRockotCrmDiskRename::getGroupIdByDeal($dealId); 
        if (!$dealInfo) {
            return CBPActivityExecutionStatus::Closed;
        }

        $groupId = $dealInfo["groupId"];
        $dealTitle = $dealInfo["title"];


        $driver = \Bitrix\Disk\Driver::getInstance(); 
	    $storage = $driver->getStorageByGroupId($groupId);

        if(!$storage->rename($dealTitle)){
            return CBPActivityExecutionStatus::Closed;
        }

        _printBP_($storage->getId());

        // $folder = $storage->getFolderById($this->dealId);
        
        // if ($folder) {
            //     $folder->rename($this->newName, $this->getWorkflow()->getDocumentId()[2]);
            // }
            
            // return CBPActivityExecutionStatus::Closed;

        _printBP_(">> END >>");
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

    public static function getGroupIdByDeal($dealId) {
        CModule::IncludeModule('crm');
        $dbRes = CCrmDeal::GetListEx([], ["ID" => $dealId], false, false, ["TITLE", "UF_CRM_1679410842"]);
        while ($deal = $dbRes->Fetch()) {
            $currentUrl = $deal["UF_CRM_1679410842"];
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
}

function _printBP_($mes) {
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/deb.log", $mes."\n", FILE_APPEND);
}