<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span class="adm-required-field">Пользователи:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'users', $arCurrentValues['users'], Array('size'=> 50))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%">ID папки диска (полученное в процессе выполнения бизнес-процесса):</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'folder_id', $arCurrentValues['folder_id'], Array('size'=> 50))?>
	</td>
</tr>