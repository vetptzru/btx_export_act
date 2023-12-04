<?
if (!CModule::IncludeModule("bizproc")) return;

$dealIdValue = isset($arCurrentValues["dealId"]) ? $arCurrentValues["dealId"] : '';
$newNameValue = isset($arCurrentValues["newName"]) ? $arCurrentValues["newName"] : '';

?>
<tr>
    <td align="right" width="40%"><span class="adm-required-field">ID сделки:</span></td>
    <td width="60%">
        <input type="text" name="dealId" id="id_deal_id" value="<?= htmlspecialcharsbx($dealIdValue) ?>" size="50">
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span class="adm-required-field">Новое имя папки:</span></td>
    <td width="60%">
        <input type="text" name="newName" id="id_new_name" value="<?= htmlspecialcharsbx($newNameValue) ?>" size="50">
    </td>
</tr>