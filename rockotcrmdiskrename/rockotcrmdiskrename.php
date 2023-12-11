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
        _printBP_(">> RUN >>");

        $debug = "NOT";
        $documentId = $this->GetDocumentId();
        if (strpos($documentId[2], 'DEAL_') != 0) {
            _printBP_("NNNNN");
        }

        $dealId = substr($documentId[2], 5); // Извлекаем ID сделки
        _printBP_($dealId);

        

        _printBP_("D1");
        
        if (!Loader::includeModule('disk')) {
           return CBPActivityExecutionStatus::Closed;
        }

        _printBP_("D2");

        if (!Loader::includeModule('crm')) {
            return CBPActivityExecutionStatus::Closed;
        }

        _printBP_("D3");

        $groupId = CBPRockotCrmDiskRename::getGroupIdByDeal($dealId);


        _printBP_(var_export($groupId));

        _printBP_("D4");

        if (!$groupId) {
            return CBPActivityExecutionStatus::Closed;;
        }

        _printBP_("D5");

        _printBP_(var_export($groupId));

        _printBP_(">> END >>");


        //[---]
        // $storage = Driver::getInstance()->getStorageByGroupId(Целевая_Группа);
        // $folder = $storage->getFolderById($this->dealId);

        // if ($folder) {
        //     $folder->rename($this->newName, $this->getWorkflow()->getDocumentId()[2]);
        // }

        // return CBPActivityExecutionStatus::Closed;
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
        $dbRes = CCrmDeal::GetListEx([], ["ID" => $dealId], false, false, ["UF_CRM_1679410842"]);
        while ($deal = $dbRes->Fetch()) {
            $currentUrl = $deal["UF_CRM_1679410842"];
            if (!$currentUrl) {
                return null;
            }
            $urlParts = explode('/', $currentUrl);
            $groupId = $urlParts[3];
            if (!$groupId) {
                return null;
            }
            return $groupId;
        }
        return $result;
    }
}

function _printBP_($mes) {
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/deb.log", $mes."\n", FILE_APPEND);
}