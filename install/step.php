<?
/**
 * Copyright (c) 2017. Dmitry Kozlov. https://github.com/DimMount
 */

if(!check_bitrix_sessid()) {
    return;
}

echo CAdminMessage::ShowNote(GetMessage('MOD_INST_OK'));

echo CAdminMessage::ShowNote(GetMessage('DIMMOUNT_REDISCACHE_SETTINGS_COMPLETE'));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage('MOD_BACK')?>">
<form>
