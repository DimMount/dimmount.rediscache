<?if(!check_bitrix_sessid()) return;?>
<?
/**
 * Copyright (c) 2017. Dmitry Kozlov. https://github.com/DimMount
 */

echo CAdminMessage::ShowNote(GetMessage('MOD_UNINST_OK'));
echo CAdminMessage::ShowNote(GetMessage('DIMMOUNT_REDISCACHE_SETTINGS_RESTORE'));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage('MOD_BACK')?>">
<form>
