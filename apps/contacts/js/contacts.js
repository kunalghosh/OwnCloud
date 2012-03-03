function ucwords (str) {
	return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
		return $1.toUpperCase();
	});
}

String.prototype.strip_tags = function(){
	tags = this;
	stripped = tags.replace(/[\<\>]/gi, "");
	return stripped;
};


Contacts={
	UI:{
		notImplemented:function() {
			Contacts.UI.messageBox(t('contacts', 'Not implemented'), t('contacts', 'Sorry, this functionality has not been implemented yet'));
		},
		searchOSM:function(obj) {
			var adr = Contacts.UI.propertyContainerFor(obj).find('.adr').val();
			console.log('adr 1: ' + adr);
			if(adr == undefined) {
				Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', 'Couldn\'t get a valid address.'));
				return;
			}
			// FIXME: I suck at regexp. /Tanghus
			var adrarr = adr.split(';');
			var adrstr = '';
			if(adrarr[2].trim() != '') {
				adrstr = adrstr + adrarr[2].trim() + ',';
			}
			if(adrarr[3].trim() != '') {
				adrstr = adrstr + adrarr[3].trim() + ',';
			}
			if(adrarr[4].trim() != '') {
				adrstr = adrstr + adrarr[4].trim() + ',';
			}
			if(adrarr[5].trim() != '') {
				adrstr = adrstr + adrarr[5].trim() + ',';
			}
			if(adrarr[6].trim() != '') {
				adrstr = adrstr + adrarr[6].trim();
			}
			console.log('adrstr: "' + adrstr + '"');
			adrstr = encodeURIComponent(adrstr);
			console.log('adrstr 2: ' + adrstr);
			var uri = 'http://open.mapquestapi.com/nominatim/v1/search.php?q=' + adrstr + '&limit=10&addressdetails=1&zoom=';
			console.log('uri: ' + uri);
			var newWindow = window.open(uri,'_blank');
			newWindow.focus();
			//Contacts.UI.notImplemented();
		},
		mailTo:function(obj) {
			var adr = Contacts.UI.propertyContainerFor($(obj)).find('input[type="email"]').val().trim();
			if(adr == '') {
				Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', 'Please enter an email address.'));
				return;
			}
			window.location.href='mailto:' + adr;
		},
		propertyContainerFor:function(obj) {
			return $(obj).parents('.propertycontainer').first();
		},
		checksumFor:function(obj) {
			return $(obj).parents('.propertycontainer').first().data('checksum');
		},
		propertyTypeFor:function(obj) {
			return $(obj).parents('.propertycontainer').first().data('element');
		},
		showHideContactInfo:function() {
			var show = ($('#emaillist li[class*="propertycontainer"]').length > 0 || $('#phonelist li[class*="propertycontainer"]').length > 0 || $('#addressdisplay dl[class*="propertycontainer"]').length > 0);
			console.log('showHideContactInfo: ' + show);
			if(show) {
				$('#contact_communication').show();
			} else {
				$('#contact_communication').hide();
			}
		},
		checkListFor:function(obj) {
			var type = $(obj).parents('.propertycontainer').first().data('element');
			console.log('checkListFor: ' + type);
			switch (type) {
				case 'EMAIL':
					console.log('emails: '+$('#emaillist>li').length);
					if($('#emaillist li[class*="propertycontainer"]').length == 0) {
						$('#emails').hide();
					}
					break;
				case 'TEL':
					console.log('phones: '+$('#phonelist>li').length);
					if($('#phonelist li[class*="propertycontainer"]').length == 0) {
						$('#phones').hide();
					}
					break;
				case 'ADR':
					console.log('addresses: '+$('#addressdisplay>dl').length);
					if($('#addressdisplay dl[class*="propertycontainer"]').length == 0) {
						$('#addresses').hide();
					}
					break;
				case 'NICKNAME':
				case 'ORG':
				case 'BDAY':
					break;
			}
		},
		loading:function(obj, state) {
			if(state) {
				$(obj).addClass('loading');
			} else {
				$(obj).removeClass('loading');
			}
		},
		showCardDAVUrl:function(username, bookname){
			$('#carddav_url').val(totalurl + '/' + username + '/' + bookname);
			$('#carddav_url').show();
			$('#carddav_url_close').show();
		},
		messageBox:function(title, msg) {
			if(msg.toLowerCase().indexOf('auth') != -1) {
				// fugly hack, I know
				alert(msg);
			}
			if($('#messagebox').dialog('isOpen') == true){
				// NOTE: Do we ever get here?
				$('#messagebox').dialog('moveToTop');
			}else{
				$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'messagebox.php'), function(){
					$('#messagebox').dialog(
						{
							autoOpen: true,
							title: title,
							buttons: [{
										text: "Ok",
										click: function() { $(this).dialog("close"); }
									}],
							close: function(event, ui) {
								$(this).dialog('destroy').remove();
							},
							open: function(event, ui) {
								$('#messagebox_msg').html(msg);
							}
					});
				});
			};
		},
		loadListHandlers:function() {
			//$('.add,.delete').hide();
			$('.globe,.mail,.delete,.edit').tipsy();
			$('.addresscard,.propertylist li,.propertycontainer').hover(
				function () {
					$(this).find('.globe,.mail,.delete,.edit').fadeIn(100);
				}, 
				function () {
					$(this).find('.globe,.mail,.delete,.edit').fadeOut(100);
				}
			);
		},
		loadHandlers:function() {
			//console.log('loadHandlers');
			/*
			$('.formfloat').hover(
				function () {
					$(this).find('.add').fadeIn(500);
				}, 
				function () {
					$(this).find('.add').fadeOut(500);
				}
			);*/
			$('.button,.action').tipsy();
			$('#contacts_deletecard').tipsy({gravity: 'ne'});
			$('#contacts_downloadcard').tipsy({gravity: 'ne'});
			//$('#fn').jec();
			$('#fn_select').combobox({
				'id': 'fn',
				'name': 'value',
				'classes': ['contacts_property'],
				'title': t('contacts', 'Format custom, Short name, Full name, Reverse or Reverse with comma')});
			//$('.jecEditableOption').attr('title', t('contacts','Custom'));
			$('#fn').tipsy();
			$('#contacts_details_photo_wrapper').tipsy();
			$('#bday').datepicker({
						dateFormat : 'dd-mm-yy'
			});
			// Style phone types
			$('#phonelist').find('select[class*="contacts_property"]').multiselect({
													noneSelectedText: t('contacts', 'Select type'),
													header: false,
													selectedList: 4,
													classes: 'typelist'
												});
			$('#add_email').click(function(){
				Contacts.UI.Card.addMail();
			});
			$('#add_phone').click(function(){
				Contacts.UI.Card.addPhone();
			});
// 			$('#add_address').click(function(){
// 				Contacts.UI.Card.editAddress();
// 				return false;
// 			});
			$('#n').click(function(){
				Contacts.UI.Card.editName();
				//return false;
			});
			$('#edit_name').click(function(){
				Contacts.UI.Card.editName();
				return false;
			});
			
			/* Initialize the photo edit dialog */
			$('#edit_photo_dialog').dialog({ autoOpen: false, modal: true, height: 'auto', width: 'auto' });
			$('#edit_photo_dialog' ).dialog( 'option', 'buttons', [
				{
					text: "Ok",
					click: function() { 
						Contacts.UI.Card.savePhoto(this);
						$(this).dialog('close');
					}
				},
				{
					text: "Cancel",
					click: function() { $(this).dialog('close'); }
				}
			] );
			Contacts.UI.loadListHandlers();
		},
		Card:{
			id:'',
			fn:'',
			fullname:'',
			shortname:'',
			famname:'',
			givname:'',
			addname:'',
			honpre:'',
			honsuf:'',
			data:undefined,
			update:function() {
				// Make sure proper DOM is loaded.
				console.log('Card.update(), #n: ' + $('#n').length);
				console.log('Card.update(), #contacts: ' + $('#contacts li').length);
				if($('#n').length == 0 && $('#contacts li').length > 0) {
					$.getJSON(OC.filePath('contacts', 'ajax', 'loadcard.php'),{},function(jsondata){
						if(jsondata.status == 'success'){
							$('#rightcontent').html(jsondata.data.page);
							Contacts.UI.loadHandlers();
							if($('#contacts li').length > 0) {
								var firstid = $('#contacts li:first-child').data('id');
								console.log('trying to load: ' + firstid);
								$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':firstid},function(jsondata){
									if(jsondata.status == 'success'){
										Contacts.UI.Card.loadContact(jsondata.data);
									} else{
										Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
									}
								});
							}
						} else{
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						}
					});
				}
				if($('#contacts li').length == 0) {
					// load intro page
					$.getJSON(OC.filePath('contacts', 'ajax', 'loadintro.php'),{},function(jsondata){
						if(jsondata.status == 'success'){
							id = '';
							$('#rightcontent').data('id','');
							$('#rightcontent').html(jsondata.data.page);
						} else {
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						}
					});
				}
			},
			export:function() {
				document.location.href = OC.linkTo('contacts', 'export.php') + '?contactid=' + this.id;
				//$.get(OC.linkTo('contacts', 'export.php'),{'contactid':this.id},function(jsondata){
				//});
			},
			import:function(){
				Contacts.UI.notImplemented();
			},
			add:function(n, fn, aid){ // add a new contact
				console.log('Add contact: ' + n + ', ' + fn + ' ' + aid);
				$.post(OC.filePath('contacts', 'ajax', 'addcontact.php'), { n: n, fn: fn, aid: aid },
				  function(jsondata) {
					if (jsondata.status == 'success'){
						$('#rightcontent').data('id',jsondata.data.id);
						var id = jsondata.data.id;
						$.getJSON('ajax/contactdetails.php',{'id':id},function(jsondata){
							if(jsondata.status == 'success'){
								Contacts.UI.loadHandlers();
								Contacts.UI.Card.loadContact(jsondata.data);
								$('#leftcontent .active').removeClass('active');
								var item = '<li data-id="'+jsondata.data.id+'" class="active"><a href="index.php?id='+jsondata.data.id+'"  style="background: url(thumbnail.php?id='+jsondata.data.id+') no-repeat scroll 0% 0% transparent;">'+Contacts.UI.Card.fn+'</a></li>';
								var added = false;
								$('#leftcontent ul li').each(function(){
									if ($(this).text().toLowerCase() > Contacts.UI.Card.fn.toLowerCase()) {
										$(this).before(item).fadeIn('fast');
										added = true;
										return false;
									}
								});
								if(!added) {
									$('#leftcontent ul').append(item);
								}
								
							}
							else{
								Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
								//alert(jsondata.data.message);
							}
						});
						$('#contact_identity').show();
						$('#actionbar').show();
						// TODO: Add to contacts list.
					}
					else{
						Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						//alert(jsondata.data.message);
					}
				});
			},
			delete: function() {
				$('#contacts_deletecard').tipsy('hide');
				$.getJSON('ajax/deletecard.php',{'id':this.id},function(jsondata){
					if(jsondata.status == 'success'){
						$('#leftcontent [data-id="'+jsondata.data.id+'"]').remove();
						$('#rightcontent').data('id','');
						//$('#rightcontent').empty();
						this.id = this.fn = this.fullname = this.shortname = this.famname = this.givname = this.addname = this.honpre = this.honsuf = '';
						this.data = undefined;
						// Load first in list.
						if($('#contacts li').length > 0) {
							Contacts.UI.Card.update();
						} else {
							// load intro page
							$.getJSON('ajax/loadintro.php',{},function(jsondata){
								if(jsondata.status == 'success'){
									id = '';
									$('#rightcontent').data('id','');
									$('#rightcontent').html(jsondata.data.page);
								}
								else{
									Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
								}
							});
						}
					}
					else{
						Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						//alert(jsondata.data.message);
					}
				});
				return false;
			},
			loadContact:function(jsondata){
				$('#contact_communication').hide();
				this.data = jsondata;
				this.id = this.data.id;
				$('#rightcontent').data('id',this.id);
				//console.log('loaded: ' + this.data.FN[0]['value']);
				this.populateNameFields();
				this.loadCategories();
				this.loadPhoto();
				this.loadMails();
				this.loadPhones();
				this.loadAddresses();
				this.loadSingleProperties();
			},
			loadSingleProperties:function() {
				var props = ['BDAY', 'NICKNAME', 'ORG'];
				// Clear all elements
				$('#ident .propertycontainer[class*="propertycontainer"]').each(function(){
					if(props.indexOf($(this).data('element')) > -1) {
						$(this).data('checksum', '');
						$(this).find('input').val('');
						$(this).hide();
						$(this).prev().hide();
					}
				});
				for(var prop in props) {
					if(this.data[props[prop]] != undefined) {
						$('#contacts_propertymenu a[data-type="'+props[prop]+'"]').parent().hide();
						var property = this.data[props[prop]][0];
						var value = property['value'], checksum = property['checksum'];
						switch(props[prop]) {
							case 'BDAY':
								var val = $.datepicker.parseDate('yy-mm-dd', value.substring(0, 10));
								value = $.datepicker.formatDate('dd-mm-yy', val);
								$('#contact_identity').find('#bday').val(value);
								$('#contact_identity').find('#bday_value').data('checksum', checksum);
								$('#contact_identity').find('#bday_label').show();
								$('#contact_identity').find('#bday_value').show();
								break;
							case 'NICKNAME':
								$('#contact_identity').find('#nickname').val(value);
								$('#contact_identity').find('#nickname_value').data('checksum', checksum);
								$('#contact_identity').find('#nickname_label').show();
								$('#contact_identity').find('#nickname_value').show();
								break;
							case 'ORG':
								$('#contact_identity').find('#org').val(value);
								$('#contact_identity').find('#org_value').data('checksum', checksum);
								$('#contact_identity').find('#org_label').show();
								$('#contact_identity').find('#org_value').show();
								break;
						}
					} else {
						$('#contacts_propertymenu a[data-type="'+props[prop]+'"]').parent().show();
					}
				}
			},
			populateNameFields:function() {
				this.fn = ''; this.fullname = ''; this.givname = ''; this.famname = ''; this.addname = ''; this.honpre = ''; this.honsuf = '';
				var full = '';
				var narray = undefined;
				this.fn = this.data.FN[0]['value'];
				if(this.data.N == undefined) {
					narray = [this.fn,'','','','']; // Checking for non-existing 'N' property :-P
					full = this.fn;
				} else {
					narray = this.data.N[0]['value'];
				}
				this.famname = narray[0];
				this.givname = narray[1];
				this.addname = narray[2];
				this.honpre = narray[3];
				this.honsuf = narray[4];
				if(this.honpre.length > 0) {
					this.fullname += this.honpre + ' ';
				}
				if(this.givname.length > 0) {
					this.fullname += ' ' + this.givname;
				}
				if(this.addname.length > 0) {
					this.fullname += ' ' + this.addname;
				}
				if(this.famname.length > 0) {
					this.fullname += ' ' + this.famname;
				}
				if(this.honsuf.length > 0) {
					this.fullname += ', ' + this.honsuf;
				}
				$('#n').html(this.fullname);
				$('#fn_select option').remove();
				$('#fn_select').combobox('value', this.fn);
				var names = [this.fullname, this.givname + ' ' + this.famname, this.famname + ' ' + this.givname, this.famname + ', ' + this.givname];
				$.each(names, function(key, value) {
					$('#fn_select')
						.append($('<option></option>')
						.text(value)); 
				});
				$('#contact_identity').find('*[data-element="N"]').data('checksum', this.data.N[0]['checksum']);
				$('#contact_identity').find('*[data-element="FN"]').data('checksum', this.data.FN[0]['checksum']);
				$('#contact_identity').show();
			},
			loadCategories:function(){
				if(this.data.CATEGORIES) {
					//
				}
			},
			editNew:function(){ // add a new contact
				this.id = ''; this.fn = ''; this.fullname = ''; this.givname = ''; this.famname = ''; this.addname = ''; this.honpre = ''; this.honsuf = '';
				$.getJSON('ajax/newcontact.php',{},function(jsondata){
					if(jsondata.status == 'success'){
						id = '';
						$('#rightcontent').data('id','');
						$('#rightcontent').html(jsondata.data.page);
						Contacts.UI.Card.editName();
					}
					else{
						Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						//alert(jsondata.data.message);
					}
				});
			},
			savePropertyInternal:function(name, fields, oldchecksum, checksum){
				// TODO: Add functionality for new fields.
				//console.log('savePropertyInternal: ' + name + ', checksum: ' + checksum);
				//console.log('savePropertyInternal: ' + this.data[name]);
				var params = {};
				var value = undefined;
				jQuery.each(fields, function(i, field){
					//.substring(11,'parameters[TYPE][]'.indexOf(']'))
					if(field.name.substring(0, 5) === 'value') {
						value = field.value;
					} else if(field.name.substring(0, 10) === 'parameters') {
						var p = field.name.substring(11,'parameters[TYPE][]'.indexOf(']'));
						if(!(p in params)) {
							params[p] = [];
						}
						params[p].push(field.value);
					}
				});
				for(var i in this.data[name]) {
					if(this.data[name][i]['checksum'] == oldchecksum) {
						this.data[name][i]['checksum'] = checksum;
						this.data[name][i]['value'] = value;
						this.data[name][i]['parameters'] = params;
					}
				}
			},
			saveProperty:function(obj){
				// I couldn't get the selector to filter on 'contacts_property' so I filter by hand here :-/
				if(!$(obj).hasClass('contacts_property')) {
					//console.log('Filtering out object.' + obj);
					return false;
				}
				if($(obj).hasClass('nonempty') && $(obj).val().trim() == '') {
					Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', 'This property has to be non-empty.'));
					return false;
				}
				container = $(obj).parents('.propertycontainer').first(); // get the parent holding the metadata.
				Contacts.UI.loading(container, true);
				var checksum = container.data('checksum');
				var name = container.data('element');
				console.log('saveProperty: ' + name);
				var fields = container.find('input[class*="contacts_property"],select[class*="contacts_property"]').serializeArray();
				var q = container.find('input[class*="contacts_property"],select[class*="contacts_property"]').serialize();
				if(q == '' || q == undefined) {
					console.log('Couldn\'t serialize elements.');
					Contacts.UI.loading(container, false);
					return false;
				}
				q = q + '&id=' + this.id + '&name=' + name;
				if(checksum != undefined && checksum != '') { // save
					q = q + '&checksum=' + checksum;
					console.log('Saving: ' + q);
					$.post('ajax/saveproperty.php',q,function(jsondata){
						if(jsondata.status == 'success'){
							container.data('checksum', jsondata.data.checksum);
							Contacts.UI.Card.savePropertyInternal(name, fields, checksum, jsondata.data.checksum);
							Contacts.UI.loading(container, false);
							return true;
						}
						else{
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
							Contacts.UI.loading(container, false);
							return false;
						}
					},'json');
				} else { // add
					console.log('Adding: ' + q);
					$.post('ajax/addproperty.php',q,function(jsondata){
						if(jsondata.status == 'success'){
							container.data('checksum', jsondata.data.checksum);
							// TODO: savePropertyInternal doesn't know about new fields
							//Contacts.UI.Card.savePropertyInternal(name, fields, checksum, jsondata.data.checksum);
							Contacts.UI.loading(container, false);
							return true;
						}
						else{
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
							Contacts.UI.loading(container, false);
							return false;
						}
					},'json');
				}
			},
			addProperty:function(obj){
				var type = $(obj).data('type');
				console.log('addProperty:' + type);
				switch (type) {
					case 'PHOTO':
						this.loadPhoto();
						$('#file_upload_form').show();
						$('#contacts_propertymenu a[data-type="'+type+'"]').parent().hide();
						break;
					case 'EMAIL':
						if($('#emaillist>li').length == 1) {
							$('#emails').show();
						}
						Contacts.UI.Card.addMail();
						Contacts.UI.showHideContactInfo();
						break;
					case 'TEL':
						if($('#phonelist>li').length == 1) {
							$('#phones').show();
						}
						Contacts.UI.Card.addPhone();
						Contacts.UI.showHideContactInfo();
						break;
					case 'ADR':
						if($('#addressdisplay>dl').length == 1) {
							$('#addresses').show();
						}
						Contacts.UI.Card.editAddress('new', true);
						Contacts.UI.showHideContactInfo();
						break;
					case 'NICKNAME':
					case 'ORG':
					case 'BDAY':
						$('dl dt[data-element="'+type+'"],dd[data-element="'+type+'"]').show();
						$('#contacts_propertymenu a[data-type="'+type+'"]').parent().hide();
						break;
				}
			},
			deleteProperty:function(obj, type){
				//console.log('deleteProperty, id: ' + this.id);
				Contacts.UI.loading(obj, true);
				var checksum = Contacts.UI.checksumFor(obj);
				if(checksum != undefined) {
					$.getJSON('ajax/deleteproperty.php',{'id': this.id, 'checksum': checksum },function(jsondata){
						if(jsondata.status == 'success'){
							if(type == 'list') {
								Contacts.UI.propertyContainerFor(obj).remove();
								Contacts.UI.showHideContactInfo();
								Contacts.UI.checkListFor(obj);
							} else if(type == 'single') {
								var proptype = Contacts.UI.propertyTypeFor(obj);
								console.log('deleteProperty, hiding: ' + proptype);
								$('dl dt[data-element="'+proptype+'"],dd[data-element="'+proptype+'"]').hide();
								$('#contacts_propertymenu a[data-type="'+proptype+'"]').parent().show();
								Contacts.UI.loading(obj, false);
							} else {
								Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', '\'deleteProperty\' called without type argument. Please report at bugs.owncloud.org'));
								Contacts.UI.loading(obj, false);
							}
						}
						else{
							Contacts.UI.loading(obj, false);
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						}
					});
				} else { // Property hasn't been saved so there's nothing to delete.
					if(type == 'list') {
						Contacts.UI.propertyContainerFor(obj).remove();
						Contacts.UI.showHideContactInfo();
						Contacts.UI.checkListFor(obj);
					} else if(type == 'single') {
						var proptype = Contacts.UI.propertyTypeFor(obj);
						console.log('deleteProperty, hiding: ' + proptype);
						$('dl dt[data-element="'+proptype+'"],dd[data-element="'+proptype+'"]').hide();
						$('#contacts_propertymenu a[data-type="'+proptype+'"]').parent().show();
						Contacts.UI.loading(obj, false);
					} else {
						Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', '\'deleteProperty\' called without type argument. Please report at bugs.owncloud.org'));
					}
				}
			},
			editName:function(){
				var isnew = (this.id == '');
				/* Initialize the name edit dialog */
				if($('#edit_name_dialog').dialog('isOpen') == true){
					$('#edit_name_dialog').dialog('moveToTop');
				}else{ // TODO: If id=='' call addcontact.php (or whatever name) instead and reload view with id.
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'editname.php')+'?id='+this.id, function(){
						$('#edit_name_dialog' ).dialog({
								modal: (isnew && true || false),
								closeOnEscape: (isnew == '' && false || true),
								title:  (isnew && t('contacts', 'Add contact') || t('contacts', 'Edit name')),
								height: 'auto', width: 'auto',
								buttons: {
									'Ok':function() { 
										Contacts.UI.Card.saveName(this);
										$(this).dialog('destroy').remove();
									},
									'Cancel':function() { $(this).dialog('destroy').remove(); }
								},
								close : function(event, ui) {
									$(this).dialog('destroy').remove();
									//return event;
								}/*,
								open : function(event, ui) {
									// load 'N' property - maybe :-P
								}*/
						});
					});
				}
			},
			saveName:function(dlg){
				console.log('saveName, id: ' + this.id);
				var n = new Array($(dlg).find('#fam').val().strip_tags(),$(dlg).find('#giv').val().strip_tags(),$(dlg).find('#add').val().strip_tags(),$(dlg).find('#pre').val().strip_tags(),$(dlg).find('#suf').val().strip_tags());
				this.famname = n[0];
				this.givname = n[1];
				this.addname = n[2];
				this.honpre = n[3];
				this.honsuf = n[4];
				this.fullname = '';

				$('#n').val(n.join(';'));
				if(n[3].length > 0) {
					this.fullname = n[3] + ' ';
				}
				this.fullname += n[1] + ' ' + n[2] + ' ' + n[0];
				if(n[4].length > 0) {
					this.fullname += ', ' + n[4];
				}

				$('#fn_select option').remove();
				//$('#fn_select').combobox('value', this.fn);
				var names = [this.fullname, this.givname + ' ' + this.famname, this.famname + ' ' + this.givname, this.famname + ', ' + this.givname];
				$.each(names, function(key, value) {
					$('#fn_select')
						.append($('<option></option>')
						.text(value)); 
				});
				
				if(this.id == '') {
					var aid = $(dlg).find('#aid').val();
					Contacts.UI.Card.add(n.join(';'), $('#short').text(), aid);
				} else {
					Contacts.UI.Card.saveProperty($('#n'));
				}
			},
			loadAddresses:function(){
				$('#addresses').hide();
				$('#addressdisplay dl[class*="propertycontainer"]').remove();
				for(var adr in this.data.ADR) {
					$('#addressdisplay dl').first().clone().insertAfter($('#addressdisplay dl').last()).show();
					$('#addressdisplay dl').last().removeClass('template').addClass('propertycontainer');
					$('#addressdisplay dl').last().data('checksum', this.data.ADR[adr]['checksum']);
					var adrarray = this.data.ADR[adr]['value'];
					var adrtxt = '';
					if(adrarray[0].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[0].strip_tags() + '</li>';
					}
					if(adrarray[1].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[1].strip_tags() + '</li>';
					}
					if(adrarray[2].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[2].strip_tags() + '</li>';
					}
					if(adrarray[3].length > 0 || adrarray[5].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[5].strip_tags() + ' ' + adrarray[3].strip_tags() + '</li>';
					}
					if(adrarray[4].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[4].strip_tags() + '</li>';
					}
					if(adrarray[6].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[6].strip_tags() + '</li>';
					}
					$('#addressdisplay dl').last().find('.addresslist').html(adrtxt);
					var types = new Array();
					var ttypes = new Array();
					for(var param in this.data.ADR[adr]['parameters']) {
						if(param.toUpperCase() == 'TYPE') {
							types.push(t('contacts', ucwords(this.data.ADR[adr]['parameters'][param].toLowerCase())));
							ttypes.push(this.data.ADR[adr]['parameters'][param]);
						}
					}
					$('#addressdisplay dl').last().find('.adr_type_label').text(types.join('/'));
					$('#addressdisplay dl').last().find('.adr_type').val(ttypes.join(','));
					$('#addressdisplay dl').last().find('.adr').val(adrarray.join(';'));
					$('#addressdisplay dl').last().data('checksum', this.data.ADR[adr]['checksum']);
				}
				if($('#addressdisplay dl').length > 1) {
					$('#addresses').show();
					$('#contact_communication').show();
				}
				Contacts.UI.loadListHandlers();
				return false;
			},
			editAddress:function(obj, isnew){
				console.log('editAddress');
				var container = undefined;
				var q = q = '?id=' + this.id;
				if(obj === 'new') {
					isnew = true;
					$('#addressdisplay dl').first().clone().insertAfter($('#addressdisplay dl').last()).show();
					container = $('#addressdisplay dl').last();
					container.removeClass('template').addClass('propertycontainer');
					Contacts.UI.loadListHandlers();
				} else {
					q = q + '&checksum='+Contacts.UI.checksumFor(obj);
				}
				/* Initialize the address edit dialog */
				if($('#edit_address_dialog').dialog('isOpen') == true){
					$('#edit_address_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'editaddress.php')+q, function(){
						$('#edit_address_dialog' ).dialog({
								/*modal: true,*/
								height: 'auto', width: 'auto',
								buttons: {
									'Ok':function() {
										if(isnew) {
											Contacts.UI.Card.saveAddress(this, $('#addressdisplay dl:last-child').find('input').first(), isnew);
										} else {
											Contacts.UI.Card.saveAddress(this, obj, isnew);
										}
										$(this).dialog('destroy').remove();
									},
									'Cancel':function() {
										$(this).dialog('destroy').remove();
										if(isnew) {
											container.remove();
										}
										Contacts.UI.showHideContactInfo();
									}
								},
								close : function(event, ui) {
									//alert('close');
									$(this).dialog('destroy').remove();
									if(isnew) {
										container.remove();
									}
									Contacts.UI.showHideContactInfo();
								}/*,
								open : function(event, ui) {
									// load 'ADR' property - maybe :-P
								}*/
						});
					});
				}
			},
			saveAddress:function(dlg, obj, isnew){
				if(isnew) {
					container = $('#addressdisplay dl').last();
					obj = $('#addressdisplay dl:last-child').find('input').first();
				} else {
					checksum = Contacts.UI.checksumFor(obj);
					container = Contacts.UI.propertyContainerFor(obj);
				}
				var adr = new Array($(dlg).find('#adr_pobox').val().strip_tags(),$(dlg).find('#adr_extended').val().strip_tags(),$(dlg).find('#adr_street').val().strip_tags(),$(dlg).find('#adr_city').val().strip_tags(),$(dlg).find('#adr_region').val().strip_tags(),$(dlg).find('#adr_zipcode').val().strip_tags(),$(dlg).find('#adr_country').val().strip_tags());
				$(container).find('.adr').val(adr.join(';'));
				$(container).find('.adr_type').val($(dlg).find('#adr_type').val());
				$(container).find('.adr_type_label').html(t('contacts',ucwords($(dlg).find('#adr_type').val().toLowerCase())));
				Contacts.UI.Card.saveProperty($(container).find('input').first());
				var adrtxt = '';
				if(adr[0].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[0] + '</li>';
				}
				if(adr[1].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[1] + '</li>';
				}
				if(adr[2].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[2] + '</li>';
				}
				if(adr[3].length > 0 || adr[5].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[5] + ' ' + adr[3] + '</li>';
				}
				if(adr[4].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[4] + '</li>';
				}
				if(adr[6].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[6] + '</li>';
				}
				$(container).find('.addresslist').html(adrtxt);
			},
			uploadPhoto:function(filelist) {
				if(!filelist) {
					Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts','No files selected for upload.'));
					return;
				}
				//var file = filelist.item(0);
				var file = filelist[0];
				var target = $('#file_upload_target');
				var form = $('#file_upload_form');
				var totalSize=0;
				if(file.size > $('#max_upload').val()){
					Contacts.UI.messageBox(t('Upload too large'), t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'));
					return;
				} else {
					target.load(function(){
						var response=jQuery.parseJSON(target.contents().text());
						if(response != undefined && response.status == 'success'){
							Contacts.UI.Card.editPhoto(response.data.id, response.data.tmp);
							//alert('File: ' + file.tmp + ' ' + file.name + ' ' + file.mime);
						}else{
							Contacts.UI.messageBox(t('contacts', 'Error'), response.data.message);
						}
					});
					form.submit();
				}
			},
			loadPhoto:function(){
				if(this.data.PHOTO) {
					$.getJSON('ajax/loadphoto.php',{'id':this.id},function(jsondata){
						if(jsondata.status == 'success'){
							//alert(jsondata.data.page);
							$('#contacts_details_photo_wrapper').html(jsondata.data.page);
						}
						else{
							Contacts.UI.messageBox(jsondata.data.message);
						}
					});
					$('#file_upload_form').show();
					$('#contacts_propertymenu a[data-type="PHOTO"]').parent().hide();
				} else {
					$('#contacts_details_photo_wrapper').empty();
					$('#file_upload_form').hide();
					$('#contacts_propertymenu a[data-type="PHOTO"]').parent().show();
				}
			},
			editPhoto:function(id, tmp_path){
				//alert('editPhoto: ' + tmp_path);
				$.getJSON('ajax/cropphoto.php',{'tmp_path':tmp_path,'id':this.id},function(jsondata){
					if(jsondata.status == 'success'){
						//alert(jsondata.data.page);
						$('#edit_photo_dialog_img').html(jsondata.data.page);
					}
					else{
						Contacts.UI.messageBox(jsondata.data.message);
					}
				});
				if($('#edit_photo_dialog').dialog('isOpen') == true){
					$('#edit_photo_dialog').dialog('moveToTop');
				} else {
					$('#edit_photo_dialog').dialog('open');
				}
			},
			savePhoto:function(){
				var target = $('#crop_target');
				var form = $('#cropform');
				form.submit();
				target.load(function(){
					var response=jQuery.parseJSON(target.contents().text());
					if(response != undefined && response.status == 'success'){
						// load cropped photo.
						$('#contacts_details_photo_wrapper').html(response.data.page);
					}else{
						Contacts.UI.messageBox(t('contacts','Error'), response.data.message);
					}
				});
				$('#contacts [data-id="'+this.id+'"]').find('a').css('background','url(thumbnail.php?id='+this.id+'&refresh=1'+Math.random()+') no-repeat');
			},
			addMail:function() {
				//alert('addMail');
				$('#emaillist li[class*="template"]:first-child').clone().appendTo($('#emaillist')).show();
				$('#emaillist li[class*="template"]:last-child').removeClass('template').addClass('propertycontainer');
				$('#emaillist li:last-child').find('input[type="email"]').focus();
				Contacts.UI.loadListHandlers();
				return false;
			},
			loadMails:function() {
				$('#emails').hide();
				$('#emaillist li[class*="propertycontainer"]').remove();
				for(var mail in this.data.EMAIL) {
					this.addMail();
					//$('#emaillist li:first-child').clone().appendTo($('#emaillist')).show();
					$('#emaillist li:last-child').data('checksum', this.data.EMAIL[mail]['checksum'])
					$('#emaillist li:last-child').find('input[type="email"]').val(this.data.EMAIL[mail]['value']);
					for(var param in this.data.EMAIL[mail]['parameters']) {
						if(param.toUpperCase() == 'PREF') {
							$('#emaillist li:last-child').find('input[type="checkbox"]').attr('checked', 'checked')
						}
					}
				}
				if($('#emaillist li').length > 1) {
					$('#emails').show();
					$('#contact_communication').show();
				}

				$('#emaillist li:last-child').find('input[type="text"]').focus();
				Contacts.UI.loadListHandlers();
				return false;
			},
			addPhone:function() {
				$('#phonelist li[class*="template"]:first-child').clone().appendTo($('#phonelist')); //.show();
				$('#phonelist li[class*="template"]:last-child').find('select').addClass('contacts_property');
				$('#phonelist li[class*="template"]:last-child').removeClass('template').addClass('propertycontainer');
				$('#phonelist li:last-child').find('input[type="text"]').focus();
				Contacts.UI.loadListHandlers();
				$('#phonelist li:last-child').find('select').multiselect({
														noneSelectedText: t('contacts', 'Select type'),
														header: false,
														selectedList: 4,
														classes: 'typelist'
													});
				$('#phonelist li:last-child').show();
				return false;
			},
			loadPhones:function() {
				$('#phones').hide();
				$('#phonelist li[class*="propertycontainer"]').remove();
				for(var phone in this.data.TEL) {
					this.addPhone();
					$('#phonelist li:last-child').find('select').multiselect('destroy');
					$('#phonelist li:last-child').data('checksum', this.data.TEL[phone]['checksum'])
					$('#phonelist li:last-child').find('input[type="text"]').val(this.data.TEL[phone]['value']);
					for(var param in this.data.TEL[phone]['parameters']) {
						if(param.toUpperCase() == 'PREF') {
							$('#phonelist li:last-child').find('input[type="checkbox"]').attr('checked', 'checked');
						}
						else if(param.toUpperCase() == 'TYPE') {
							for(ptype in this.data.TEL[phone]['parameters'][param]) {
								var pt = this.data.TEL[phone]['parameters'][param][ptype];
								$('#phonelist li:last-child').find('select option').each(function(){
									if ($(this).val().toUpperCase() == pt.toUpperCase()) {
										$(this).attr('selected', 'selected');
									}
								});
							}
						}
					}
					$('#phonelist li:last-child').find('select').multiselect({
														noneSelectedText: t('contacts', 'Select type'),
														header: false,
														selectedList: 4,
														classes: 'typelist'
													});
				}
				if($('#phonelist li').length > 1) {
					$('#phones').show();
					$('#contact_communication').show();
				}
				return false;
			},
		},
		Addressbooks:{
			overview:function(){
				if($('#chooseaddressbook_dialog').dialog('isOpen') == true){
					$('#chooseaddressbook_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'chooseaddressbook.php'), function(){
						$('#chooseaddressbook_dialog').dialog({
							width : 600,
							close : function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						});
					});
				}
			},
			activation:function(checkbox, bookid)
			{
				$.post(OC.filePath('contacts', 'ajax', 'activation.php'), { bookid: bookid, active: checkbox.checked?1:0 },
				  function(data) {
					if (data.status == 'success'){
						checkbox.checked = data.active == 1;
						Contacts.UI.Contacts.update();
					}
				  });
			},
			newAddressbook:function(object){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'addbook.php'));
				$(object).closest('tr').after(tr).hide();
				/* TODO: Shouldn't there be some kinda error checking here? */
			},
			editAddressbook:function(object, bookid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'editaddressbook.php') + "?bookid="+bookid);
				$(object).closest('tr').after(tr).hide();
			},
			deleteAddressbook:function(bookid){
				var check = confirm("Do you really want to delete this address book?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('contacts', 'ajax', 'deletebook.php'), { id: bookid},
					  function(jsondata) {
						if (jsondata.status == 'success'){
							$('#chooseaddressbook_dialog').dialog('destroy').remove();
							Contacts.UI.Contacts.update();
							Contacts.UI.Addressbooks.overview();
						} else {
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
							//alert('Error: ' + data.message);
						}
					  });
				}
			},
			import:function(){
				Contacts.UI.notImplemented();
			},
			submit:function(button, bookid){
				var displayname = $("#displayname_"+bookid).val().trim();
				var active = $("#edit_active_"+bookid+":checked").length;
				var description = $("#description_"+bookid).val();
				
				if(displayname.length == 0) {
					Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', 'Displayname cannot be empty.'));
					return false;
				}
				var url;
				if (bookid == 'new'){
					url = OC.filePath('contacts', 'ajax', 'createaddressbook.php');
				}else{
					url = OC.filePath('contacts', 'ajax', 'updateaddressbook.php');
				}
				$.post(url, { id: bookid, name: displayname, active: active, description: description },
					function(jsondata){
						if(jsondata.status == 'success'){
							$(button).closest('tr').prev().html(jsondata.page).show().next().remove();
							Contacts.UI.Contacts.update();
						} else {
							Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
						}
					});
			},
			cancel:function(button, bookid){
				$(button).closest('tr').prev().show().next().remove();
			}
		},
		Contacts:{
			/**
			 * Reload the contacts list.
			 */
			update:function(){
				console.log('Contacts.update, start');
				$.getJSON('ajax/contacts.php',{},function(jsondata){
					if(jsondata.status == 'success'){
						$('#contacts').html(jsondata.data.page);
						Contacts.UI.Card.update();
					}
					else{
						Contacts.UI.messageBox(t('contacts', 'Error'),jsondata.data.message);
						//alert(jsondata.data.message);
					}
				});
				setTimeout(Contacts.UI.Contacts.lazyupdate, 500);
			},
			/**
			 * Add thumbnails to the contact list as they become visible in the viewport.
			 */
			lazyupdate:function(){
				$('#contacts li').live('inview', function(){
					if (!$(this).find('a').attr('style')) {
						$(this).find('a').css('background','url(thumbnail.php?id='+$(this).data('id')+') no-repeat');
					}
				});
			}
		}
	}
}
$(document).ready(function(){

	Contacts.UI.loadHandlers();

	/**
	 * Show the Addressbook chooser
	 */
	$('#chooseaddressbook').click(function(){
		Contacts.UI.Addressbooks.overview();
		return false;
	});

	$('#contacts_newcontact').click(function(){
		Contacts.UI.Card.editNew();
	});
	
	/**
	 * Load the details view for a contact.
	 */
	$('#leftcontent li').live('click',function(){
		var id = $(this).data('id');
		var oldid = $('#rightcontent').data('id');
		if(oldid != 0){
			$('#leftcontent li[data-id="'+oldid+'"]').removeClass('active');
		}
		$.getJSON('ajax/contactdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				Contacts.UI.Card.loadContact(jsondata.data);
			}
			else{
				Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
				//alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_deletecard').live('click',function(){
		Contacts.UI.Card.delete();
	});

	$('#contacts li').bind('inview', function(event, isInView, visiblePartX, visiblePartY) {
		if (isInView) { //NOTE: I've kept all conditions for future reference ;-)
			// element is now visible in the viewport
			if (visiblePartY == 'top') {
				// top part of element is visible
			} else if (visiblePartY == 'bottom') {
				// bottom part of element is visible
			} else {
				// whole part of element is visible
				if (!$(this).find('a').attr('style')) {
					//alert($(this).data('id') + ' has background: ' + $(this).attr('style'));
					$(this).find('a').css('background','url(thumbnail.php?id='+$(this).data('id')+') no-repeat');
				}/* else {
					alert($(this).data('id') + ' has style ' + $(this).attr('style').match('url'));
				}*/
			}
		} else {
			// element has gone out of viewport
		}
	});
	
	$('.button').tipsy();
	// Triggers invisible file input
	$('#contacts_details_photo').live('click', function() {
		$('#file_upload_start').trigger('click');
		return false;
	});
	
	// NOTE: For some reason the selector doesn't work when I select by '.contacts_property' too...
	// I do the filtering in the event handler instead.
	$('input[type="text"],input[type="checkbox"],input[type="email"],input[type="tel"],input[type="date"], select').live('change', function(){
		Contacts.UI.Card.saveProperty(this);
	});

	// Name has changed. Update it and reorder.
	$('#fn').live('change',function(){
		var name = $('#fn').val();
		var item = $('#contacts [data-id="'+Contacts.UI.Card.id+'"]').clone();
		$('#contacts [data-id="'+Contacts.UI.Card.id+'"]').remove();
		$(item).find('a').html(name);
		var added = false;
		$('#contacts li').each(function(){
			if ($(this).text().toLowerCase() > name.toLowerCase()) {
				$(this).before(item).fadeIn('fast');
				added = true;
				return false;
			}
		});
		if(!added) {
			$('#leftcontent ul').append(item);
		}
	});

	/**
	 * Profile picture upload handling
	 */
	// New profile picture selected
	$('#file_upload_start').live('change',function(){
		Contacts.UI.Card.uploadPhoto(this.files);
	});
	$('#contacts_details_photo').bind('dragover',function(event){
		console.log('dragover');
		$(event.target).css('background-color','red');
		event.stopPropagation();
		event.preventDefault();  
	});
	$('#contacts_details_photo').bind('dragleave',function(event){
		console.log('dragleave');
		$(event.target).css('background-color','white');
		//event.stopPropagation();
		//event.preventDefault();  
	});
	$('#contacts_details_photo').bind('drop',function(event){
		event.stopPropagation();
		event.preventDefault();
		console.log('drop');
		$(event.target).css('background-color','white')
		$.fileUpload(event.originalEvent.dataTransfer.files);
	});

	/**
	 * Upload function for dropped files. Should go in the Contacts class/object.
	 */
	$.fileUpload = function(files){
		var file = files[0];
		console.log('size: '+file.size);
		if(file.size > $('#max_upload').val()){
			Contacts.UI.messageBox(t('contacts','Upload too large'), t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'));
			return;
		}
		if (file.type.indexOf("image") != 0) {
			Contacts.UI.messageBox(t('contacts','Wrong file type'), t('contacts','Only image files can be used as profile picture.'));
			return;
		}
		var xhr = new XMLHttpRequest();

		if (!xhr.upload) {
			Contacts.UI.messageBox(t('contacts', 'Error'), t('contacts', 'Your browser doesn\'t support AJAX upload. Please click on the profile picture to select a photo to upload.'))
		}
		fileUpload = xhr.upload,
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4){
				response = $.parseJSON(xhr.responseText);
				if(response.status == 'success') {
					if(xhr.status == 200) {
						Contacts.UI.Card.editPhoto(response.data.id, response.data.tmp);
					} else {
						Contacts.UI.messageBox(t('contacts', 'Error'), xhr.status + ': ' + xhr.responseText);
					}
				} else {
					//alert(xhr.responseText);
					Contacts.UI.messageBox(t('contacts', 'Error'), response.data.message);
				}
				// stop loading indicator
				//$('#contacts_details_photo_progress').hide();
			}
		};
	
		fileUpload.onprogress = function(e){
			if (e.lengthComputable){
				var _progress = Math.round((e.loaded * 100) / e.total);
				if (_progress != 100){
					$('#contacts_details_photo_progress').text(_progress + '%');
					$('#contacts_details_photo_progress').val(_progress);
				}
			}
		};
		// Start loading indicator.
		//$('#contacts_details_photo_progress').show()();
		xhr.open("POST", 'ajax/uploadphoto.php?id='+Contacts.UI.Card.id+'&imagefile='+encodeURIComponent(file.name), true);
		xhr.setRequestHeader('Cache-Control', 'no-cache');
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('X_FILE_NAME', encodeURIComponent(file.name));
		//xhr.setRequestHeader("X_FILENAME", file.name);
		xhr.setRequestHeader('X-File-Size', file.size);
		xhr.setRequestHeader('Content-Type', file.type);
		xhr.send(file);
	}

	$('#contacts_propertymenu_button').live('click',function(){
		$('#contacts_propertymenu').is(':hidden') && $('#contacts_propertymenu').slideDown() || $('#contacts_propertymenu').slideUp();
	});
	$('#contacts_propertymenu a').live('click',function(){
		Contacts.UI.Card.addProperty(this);
		$('#contacts_propertymenu').hide();
	});
});
