<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

use Bitrix\Main;
use Bitrix\Main\Entity\Query;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Sharing;

class CBPRokotCrmAddDisk extends CBPActivity
{
  public function __construct($name)
  {
    parent::__construct($name);
    $this->arProperties = array("Users" => "", "FolderID" => "");

    $this->SetPropertiesTypes(array());
  }

  public function Execute()
  {
    $Users = $this->Users;
    $FolderID = $this->FolderID;

    if (!empty($FolderID)) {
      $FolderResultID = $FolderID;
    } else {
      // return \CBPActivityExecutionStatus::Closed;
    }

    CBPRokotCrmAddDisk::_printBP_("Second BP is started!");

    CBPRokotCrmAddDisk::_printBP_(var_export($Users, true));

    $pattern = '/\[(.+?)\]/';
    $success = preg_match_all($pattern, $Users, $departments);
    if (!$success) {
      CBPRokotCrmAddDisk::_printBP_("Error in preg_match_all!");
      return \CBPActivityExecutionStatus::Closed;
    }

    CBPRokotCrmAddDisk::_printBP_(var_export($departments[1], true));
    CBPRokotCrmAddDisk::_printBP_(var_export($FolderID, true));
    CBPRokotCrmAddDisk::_printBP_(var_export($FolderResultID, true));

    $folder = \Bitrix\Disk\Folder::loadById($FolderID);
    if (!$folder) {
      CBPRokotCrmAddDisk::_printBP_("Folder not found!");
      return \CBPActivityExecutionStatus::Closed;
    }

    // $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();

    //---------------
    // Получаем права на папку
    //--------------
    /*
    $query = new Query(RightTable::getEntity());
    $rights = $query
      ->setSelect(array('*', 'DEPTH_LEVEL' => 'PATH_PARENT.DEPTH_LEVEL', ))
      ->setFilter(array(
        'PATH_PARENT.OBJECT_ID' => $FolderResultID,
      ))
      ->exec()
      ->fetchAll()
    ;
    */
    //--------------
    // Подучаем текущие права, и дописываем старые
    //--------------
    /*
    $newRights = [];
    foreach ($rights as $right) {
      $newRights[$right["ACCESS_CODE"]] = array(
        'ACCESS_CODE' => $right["ACCESS_CODE"],
        'TASK_ID' => $right["TASK_ID"],
        'NEGATIVE' => $right["NEGATIVE"],
        'OBJECT_ID' => $FolderResultID,
        'DOMAIN' => $right["DOMAIN"],
        'DEPTH_LEVEL' => $right["DEPTH_LEVEL"]
      );
      //Удаляем текущие права
      RightTable::delete($right["ID"]);
    }
    */
    //-------------
    // Получаем список сотрудников
    //--------------
    /*
    foreach ($departments[1] as $department) {

      if ((strpos($department, "G") !== false) || (strpos($department, "D") !== false)) {

      } else {
        $department = "U" . $department;
      }

      $newRights[$department] = array(
        'ACCESS_CODE' => $department,
        'TASK_ID' => $rightsManager::TASK_READ,
        'NEGATIVE' => 0,
        'OBJECT_ID' => $FolderResultID,
        'DOMAIN' => "",
        'DEPTH_LEVEL' => ""
      );
    }
    CBPRokotCrmAddDisk::_printBP_(var_export($newRights, true));
    */
    //-------------
    // Добавляем новые права
    //--------------
    /*
    foreach ($newRights as $resultRights) {
      $result = RightTable::add($resultRights);
    }
    */
    //-------------


    foreach ($departments[1] as $userId) {
      $result = CBPRokotCrmAddDisk::processActionConnectToUserStorage($FolderID, $userId);
      if (!$result) {
        CBPRokotCrmAddDisk::_printBP_("Error in processActionConnectToUserStorage!");
        continue;
      }
      CBPRokotCrmAddDisk::_printBP_("processActionConnectToUserStorage is success!");
    }

    

    CBPRokotCrmAddDisk::_printBP_("Second BP is finished!");


    return \CBPActivityExecutionStatus::Closed;
  }

  public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
  {
    $arErrors = array();
    return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
  }

  public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
  {
    $runtime = \CBPRuntime::GetRuntime();
    $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();

    $arMap = array(
      "Users" => "users",
      "FolderID" => "folder_id"
    );

    if (!is_array($arWorkflowParameters)) {
      $arWorkflowParameters = array();
    }
    if (!is_array($arWorkflowVariables)) {
      $arWorkflowVariables = array();
    }

    if (!is_array($arCurrentValues)) {
      $arCurrentActivity = &\CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
      if (is_array($arCurrentActivity["Properties"])) {
        foreach ($arMap as $k => $v) {
          if (array_key_exists($k, $arCurrentActivity["Properties"])) {
            $arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
          } elseif ($k == "TaskPriority") {
            $arCurrentValues[$arMap[$k]] = "1";
          } else {
            $arCurrentValues[$arMap[$k]] = "";
          }
        }
      } else {
        foreach ($arMap as $k => $v) {
          $arCurrentValues[$arMap[$k]] = "";
        }
      }
    }

    /*
    if (empty($currentValues['entity_type']))
      $currentValues['entity_type'] = 'user';
    if (!empty($currentValues['entity_id_' . $currentValues['entity_type']]))
      $currentValues['entity_id'] = $currentValues['entity_id_' . $currentValues['entity_type']];

    if (
      empty($currentValues['entity_id'])
      && isset($currentValues['entity_id_' . $currentValues['entity_type'] . '_x'])
      && CBPDocument::IsExpression($currentValues['entity_id_' . $currentValues['entity_type'] . '_x'])
    )
      $currentValues['entity_id'] = $currentValues['entity_id_' . $currentValues['entity_type'] . '_x'];

    if ($currentValues['entity_type'] == 'user' && !CBPDocument::IsExpression($currentValues['entity_id']))
      $currentValues['entity_id'] = CBPHelper::UsersArrayToString($currentValues['entity_id'], $arWorkflowTemplate, $documentType);


    //Список прав на папки и файлы
    $arPermsType = array(
      "close" => GetMessage("STUDIOBIT_USERS_PERMS_5"),
        $rightsManager::TASK_READ => GetMessage("STUDIOBIT_USERS_PERMS_1"),
        $rightsManager::TASK_EDIT => GetMessage("STUDIOBIT_USERS_PERMS_2"),
        $rightsManager::TASK_ADD => GetMessage("STUDIOBIT_USERS_PERMS_3"),
        $rightsManager::TASK_FULL => GetMessage("STUDIOBIT_USERS_PERMS_4")
    );
    */

    return $runtime->ExecuteResourceFile(
      __FILE__,
      "properties_dialog.php",
      array(
        "arCurrentValues" => $arCurrentValues,
        "formName" => $formName,
        // "arPermsType" => $arPermsType,
      )
    );
  }

  public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
  {
    $arErrors = array();

    $runtime = \CBPRuntime::GetRuntime();

    $arMap = array(
      "users" => "Users",
      "folder_id" => "FolderID",
    );

    $arProperties = array();
    foreach ($arMap as $key => $value) {
      $arProperties[$value] = $arCurrentValues[$key];
    }

    // if (in_array($arProperties['EntityType'], array('user')))
    //   $arProperties['EntityId'] = $arCurrentValues['entity_id_' . $arProperties['EntityType']];

    // if (
    //   empty($arProperties['EntityId'])
    //   && isset($arCurrentValues['entity_id_' . $arProperties['EntityType'] . '_x'])
    //   && CBPDocument::IsExpression($arCurrentValues['entity_id_' . $arProperties['EntityType'] . '_x'])
    // )
    //   $arProperties['EntityId'] = $arCurrentValues['entity_id_' . $arProperties['EntityType'] . '_x'];

    // if ($arProperties['EntityType'] == 'user' && !CBPDocument::IsExpression($arProperties['EntityId']))
    //   $arProperties['EntityId'] = CBPHelper::UsersStringToArray($arProperties['EntityId'], $documentType, $arErrors);


    $arErrors = self::ValidateProperties($arProperties, new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser));
    if (count($arErrors) > 0) {
      return false;
    }

    $arCurrentActivity = &\CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
    $arCurrentActivity["Properties"] = $arProperties;

    return true;
  }


  protected function processActionConnectToUserStorage($objectId, $userId)
  {
    $object = BaseObject::loadById((int) $objectId, array('STORAGE'));
    if (!$object) {
      return false;
    }

    $storage = $object->getStorage();
    $securityContext = $storage->getCurrentUserSecurityContext();
    if (!$object->canRead($securityContext)) {
      return false;
    }

    $errorCollection = new Bitrix\Disk\Internals\Error\ErrorCollection;

    if ($storage->getRootObjectId() == $object->getId()) {
      $existingSharing = Sharing::getExisting($userId, $object);
      if ($existingSharing && $existingSharing->isUnreplied()) {
        if ($existingSharing->approve()) {
          $sharingModel = $existingSharing;
        }
      } else {
        $sharingModel = Sharing::connectGroupToSelfUserStorage(
          $userId,
          $storage,
          $errorCollection
        );
      }

      if ($sharingModel) {
        return true;
      }
    } else {
      $sharingModel = Sharing::connectObjectToSelfUserStorage(
        $userId,
        $object,
        $errorCollection
      );
    }

    if ($sharingModel === null) {
      return false;
    }
    return true;
  }


  private static function _printBP_($mes)
  {
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/deb.log", $mes . "\n", FILE_APPEND);
  }
}

