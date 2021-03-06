<?php
$id = isset($_['id']) ? $_['id'] : '';
?>
<div id="card">
	<div id="actionbar">
	<a title="<?php echo $l->t('Add field'); ?>" class="svg action" id="contacts_propertymenu_button"></a>
	<div id="contacts_propertymenu" style="display: none;">
	<ul>
		<li><a data-type="PHOTO"><?php echo $l->t('Profile picture'); ?></a></li>
		<li><a data-type="ORG"><?php echo $l->t('Organization'); ?></a></li>
		<li><a data-type="NICKNAME"><?php echo $l->t('Nickname'); ?></a></li>
		<li><a data-type="BDAY"><?php echo $l->t('Birthday'); ?></a></li>
		<li><a data-type="TEL"><?php echo $l->t('Phone'); ?></a></li>
		<li><a data-type="EMAIL"><?php echo $l->t('Email'); ?></a></li>
		<li><a data-type="ADR"><?php echo $l->t('Address'); ?></a></li>
		<li><a data-type="NOTE"><?php echo $l->t('Note'); ?></a></li>
		<li><a data-type="CATEGORIES"><?php echo $l->t('Categories'); ?></a></li>
	</ul>
	</div>
	<img  onclick="Contacts.UI.Card.export();" class="svg action" id="contacts_downloadcard" src="<?php echo image_path('', 'actions/download.svg'); ?>" title="<?php echo $l->t('Download contact');?>" />
	<img class="svg action" id="contacts_deletecard" src="<?php echo image_path('', 'actions/delete.svg'); ?>" title="<?php echo $l->t('Delete contact');?>" />
	</div>

	<div class="contactsection">

	<form style="display:none;" id="file_upload_form" action="ajax/uploadphoto.php" method="post" enctype="multipart/form-data" target="file_upload_target" class="propertycontainer" data-element="PHOTO">
	<fieldset id="photo" class="formfloat">
	<a class="action delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a>
		<div id="contacts_details_photo_wrapper" title="<?php echo $l->t('Click or drop to upload picture'); ?> (max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
		<!-- img style="padding: 1em;" id="contacts_details_photo" alt="Profile picture"  src="photo.php?id=<?php echo $_['id']; ?>" / -->
		<progress id="contacts_details_photo_progress" style="display:none;" value="0" max="100">0 %</progress>
		</div>
		<input type="hidden" name="id" value="<?php echo $_['id'] ?>">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
		<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
		<input id="file_upload_start" type="file" accept="image/*" name="imagefile" />
		<iframe name="file_upload_target" id='file_upload_target' src=""></iframe>
	</fieldset>
	</form>
	<form id="contact_identity" method="post" <?php echo ($_['id']==''||!isset($_['id'])?'style="display:none;"':''); ?>>
	<input type="hidden" name="id" value="<?php echo $_['id'] ?>">
	<fieldset class="propertycontainer" data-element="N"><input type="hidden" id="n" class="contacts_property" name="value" value="" /></fieldset>
	<fieldset id="ident" class="formfloat">
	<!-- legend>Name</legend -->
	<dl class="form">
		<!-- dt><label for="n"><?php echo $l->t('Name'); ?></label></dt>
		<dd style="padding-top: 0.8em;vertical-align: text-bottom;"><span id="n" type="text"></span></dd -->
		<dt><label for="fn"><?php echo $l->t('Display name'); ?></label></dt>
		<dd class="propertycontainer" data-element="FN">
		<select id="fn_select" title="<?php echo $l->t('Format custom, Short name, Full name, Reverse or Reverse with comma'); ?>" style="width:16em;">
		</select><a id="edit_name" class="action edit" title="<?php echo $l->t('Edit name details'); ?>"></a>
		</dd>
		<dt style="display:none;" id="org_label" data-element="ORG"><label for="org"><?php echo $l->t('Organization'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="org_value" data-element="ORG"><input id="org"  required="required" name="value[ORG]" type="text" class="contacts_property" style="width:16em;" name="value" value="" placeholder="<?php echo $l->t('Organization'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
		<dt style="display:none;" id="nickname_label" data-element="NICKNAME"><label for="nickname"><?php echo $l->t('Nickname'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="nickname_value" data-element="NICKNAME"><input id="nickname" required="required" name="value[NICKNAME]" type="text" class="contacts_property" style="width:16em;" name="value" value="" placeholder="<?php echo $l->t('Enter nickname'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
		<dt style="display:none;" id="bday_label" data-element="BDAY"><label for="bday"><?php echo $l->t('Birthday'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="bday_value" data-element="BDAY"><input id="bday"  required="required" name="value" type="text" class="contacts_property" value="" placeholder="<?php echo $l->t('dd-mm-yyyy'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
		<!-- dt id="categories_label" data-element="CATEGORIES"><label for="categories"><?php echo $l->t('Categories'); ?></label></dt>
		<dd class="propertycontainer" id="categories_value" data-element="CATEGORIES">
			<select class="contacts_property" multiple="multiple" id="categories" name="value[]">
				<?php echo html_select_options($_['categories'], array(), array('combine'=>true)) ?>
			</select>
			<a class="action edit" onclick="$(this).tipsy('hide');OCCategories.edit();" title="<?php echo $l->t('Edit categories'); ?>"></a>
		</dd -->
		<dt style="display:none;" id="categories_label" data-element="CATEGORIES"><label for="categories"><?php echo $l->t('Categories'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="categories_value" data-element="CATEGORIES"><input id="categories"  required="required" name="value[CATEGORIES]" type="text" class="contacts_property" style="width:16em;" name="value" value="" placeholder="<?php echo $l->t('Categories'); ?>" /><a class="action delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a><a class="action edit" onclick="$(this).tipsy('hide');OCCategories.edit();" title="<?php echo $l->t('Edit categories'); ?>"></a></dd>
	</dl>
	</fieldset>
	<fieldset id="note" class="formfloat propertycontainer contactpart" style="display:none;" data-element="NOTE">
	<legend><?php echo $l->t('Note'); ?><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></legend>
	<textarea class="contacts_property note" name="value" cols="60" rows="10"></textarea>
	</fieldset>
	</form>
	</div>

	<!-- div class="delimiter"></div -->
	<form id="contact_communication" method="post" style="display: none;">
	<div class="contactsection">
		<!-- email addresses -->
		<div id="emails" style="display:none;">
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Email'); ?></legend>
			<ul id="emaillist" class="propertylist">
			<li class="template" style="white-space: nowrap; display: none;" data-element="EMAIL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" />
				<input type="email" required="required" class="nonempty contacts_property" style="width:15em;" name="value" value="" x-moz-errormessage="<?php echo $l->t('Please specify a valid email address.'); ?>" placeholder="<?php echo $l->t('Enter email address'); ?>" /><span class="listactions"><a onclick="Contacts.UI.mailTo(this)" class="mail" title="<?php echo $l->t('Mail to address'); ?>"></a>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete email address'); ?>"></a></span></li>
			</ul><!-- a id="add_email" class="add" title="<?php echo $l->t('Add email address'); ?>"></a -->
		</div> <!-- email addresses-->

		<!-- Phone numbers -->
		<div id="phones" style="display:none;">
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Phone'); ?></legend>
			<ul id="phonelist" class="propertylist">
				<li class="template" style="white-space: nowrap; display: none;" data-element="TEL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" /> 
				<input type="text" required="required" class="nonempty contacts_property" style="width:10em; border: 0px;" name="value" value="" placeholder="<?php echo $l->t('Enter phone number'); ?>" />
				<select multiple="multiple" name="parameters[TYPE][]">
					<?php echo html_select_options($_['phone_types'], array()) ?>
				</select>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete phone number'); ?>"></a></li>
			</ul><!-- a id="add_phone" class="add" title="<?php echo $l->t('Add phone number'); ?>"></a -->
		</div> <!-- Phone numbers -->

		<!-- Addresses -->
		<div id="addresses" style="display:none;">
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Address'); ?></legend>
		<div id="addressdisplay">
			<dl class="addresscard template" style="display: none;" data-element="ADR"><dt>
			<input class="adr contacts_property" name="value" type="hidden" value="" />
			<input type="hidden" class="adr_type contacts_property" name="parameters[TYPE][]" value="" />
			<span class="adr_type_label"></span><a class="globe" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.searchOSM(this);" title="<?php echo $l->t('View on map'); ?>"></a><a class="edit" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.Card.editAddress(this, false);" title="<?php echo $l->t('Edit address details'); ?>"></a><a class="delete" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="Delete address"></a>
			</dt><dd><ul class="addresslist"></ul></dd></dl>

		</fieldset>
		</div>
		</div> <!-- Addresses -->
	</div>
	<!-- a id="add_address" onclick="Contacts.UI.Card.editAddress('new', true)" class="add" title="<?php echo $l->t('Add address'); ?>"></a -->
	</div> 
	</form>
</div>
<div id="edit_photo_dialog" title="Edit photo">
		<div id="edit_photo_dialog_img"></div>
</div>
<script language="Javascript">
$(document).ready(function(){
	if('<?php echo $id; ?>'!='') {
		$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':'<?php echo $id; ?>'},function(jsondata){
			if(jsondata.status == 'success'){
				Contacts.UI.Card.loadContact(jsondata.data);
			}
			else{
				Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
			}
		});
	}
});
</script>
