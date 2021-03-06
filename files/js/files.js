$(document).ready(function() {
	$('#fileList tr').each(function(){
		//little hack to set unescape filenames in attribute
		$(this).attr('data-file',decodeURIComponent($(this).attr('data-file')));
	});

	if($('tr[data-file]').length==0){
		$('.file_upload_filename').addClass('highlight');
	}

	$('#file_action_panel').attr('activeAction', false);

	//drag/drop of files
	$('#fileList tr td.filename').draggable(dragOptions);
	$('#fileList tr[data-type="dir"][data-write="true"] td.filename').droppable(folderDropOptions);
	$('div.crumb').droppable(crumbDropOptions);
	$('ul#apps>li:first-child').data('dir','');
	$('ul#apps>li:first-child').droppable(crumbDropOptions);

	// Triggers invisible file input
	$('.file_upload_button_wrapper').live('click', function() {
		$(this).parent().children('.file_upload_start').trigger('click');
		return false;
	});

	// Sets the file-action buttons behaviour :
	$('tr').live('mouseenter',function(event) {
		FileActions.display($(this).children('td.filename'));
	});
	$('tr').live('mouseleave',function(event) {
		FileActions.hide();
	});

	var lastChecked;

	// Sets the file link behaviour :
	$('td.filename a').live('click',function(event) {
		event.preventDefault();
		if (event.ctrlKey || event.shiftKey) {
			if (event.shiftKey) {
				var last = $(lastChecked).parent().parent().prevAll().length;
				var first = $(this).parent().parent().prevAll().length;
				var start = Math.min(first, last);
				var end = Math.max(first, last);
				var rows = $(this).parent().parent().parent().children('tr');
				for (var i = start; i < end; i++) {
					$(rows).each(function(index) {
						if (index == i) {
							var checkbox = $(this).children().children('input:checkbox');
							$(checkbox).attr('checked', 'checked');
							$(checkbox).parent().parent().addClass('selected');
						}
					});
				}
			}
			var checkbox = $(this).parent().children('input:checkbox');
			lastChecked = checkbox;
			if ($(checkbox).attr('checked')) {
				$(checkbox).removeAttr('checked');
				$(checkbox).parent().parent().removeClass('selected');
				$('#select_all').removeAttr('checked');
			} else {
				$(checkbox).attr('checked', 'checked');
				$(checkbox).parent().parent().toggleClass('selected');
				var selectedCount=$('td.filename input:checkbox:checked').length;
				if (selectedCount == $('td.filename input:checkbox').length) {
					$('#select_all').attr('checked', 'checked');
				}
			}
			procesSelection();
		} else {
			var filename=$(this).parent().parent().attr('data-file');
			var tr=$('tr').filterAttr('data-file',filename);
			var renaming=tr.data('renaming');
			if(!renaming && !FileList.isLoading(filename)){
				var mime=$(this).parent().parent().data('mime');
				var type=$(this).parent().parent().data('type');
				var action=FileActions.getDefault(mime,type);
				if(action){
					action(filename);
				}
			}
		}

	});

	// Sets the select_all checkbox behaviour :
	$('#select_all').click(function() {
		if($(this).attr('checked')){
			// Check all
			$('td.filename input:checkbox').attr('checked', true);
			$('td.filename input:checkbox').parent().parent().addClass('selected');
		}else{
			// Uncheck all
			$('td.filename input:checkbox').attr('checked', false);
			$('td.filename input:checkbox').parent().parent().removeClass('selected');
		}
		procesSelection();
	});

	$('td.filename input:checkbox').live('click',function(event) {
		if (event.shiftKey) {
			var last = $(lastChecked).parent().parent().prevAll().length;
			var first = $(this).parent().parent().prevAll().length;
			var start = Math.min(first, last);
			var end = Math.max(first, last);
			var rows = $(this).parent().parent().parent().children('tr');
			for (var i = start; i < end; i++) {
				$(rows).each(function(index) {
					if (index == i) {
						var checkbox = $(this).children().children('input:checkbox');
						$(checkbox).attr('checked', 'checked');
						$(checkbox).parent().parent().addClass('selected');
					}
				});
			}
		}
		var selectedCount=$('td.filename input:checkbox:checked').length;
		$(this).parent().parent().toggleClass('selected');
		if(!$(this).attr('checked')){
			$('#select_all').attr('checked',false);
		}else{
			if(selectedCount==$('td.filename input:checkbox').length){
				$('#select_all').attr('checked',true);
			}
		}
		procesSelection();
	});

	$('#file_newfolder_name').click(function(){
		if($('#file_newfolder_name').val() == 'New Folder'){
			$('#file_newfolder_name').val('');
		}
	});

	$('.download').click('click',function(event) {
		var files=getSelectedFiles('name').join(';');
		var dir=$('#dir').val()||'/';
		$('#notification').text(t('files','generating ZIP-file, it may take some time.'));
		$('#notification').fadeIn();
		window.location='ajax/download.php?files='+encodeURIComponent(files)+'&dir='+encodeURIComponent(dir);
		return false;
	});

	$('.delete').click(function(event) {
		var files=getSelectedFiles('name');
		event.preventDefault();
		FileList.do_delete(files);
		return false;
	});

	$('.file_upload_start').live('change',function(){
		var form=$(this).closest('form');
		var that=this;
		var uploadId=form.attr('data-upload-id');
		var files=this.files;
		var target=form.children('iframe');
		var totalSize=0;
		if(files){
			for(var i=0;i<files.length;i++){
				totalSize+=files[i].size;
				if(FileList.deleteFiles && FileList.deleteFiles.indexOf(files[i].name)!=-1){//finish delete if we are uploading a deleted file
					FileList.finishDelete(function(){
						$(that).change();
					});
					return;
				}
			}
		}
		if(totalSize>$('#max_upload').val()){
			$( "#uploadsize-message" ).dialog({
				modal: true,
				buttons: {
					Close: function() {
						$( this ).dialog( "close" );
					}
				}
			});
		}else{
			target.load(function(){
				var response=jQuery.parseJSON(target.contents().find('body').text());
				//set mimetype and if needed filesize
				if(response){
					if(response[0] != undefined && response[0].status == 'success'){
						for(var i=0;i<response.length;i++){
							var file=response[i];
							$('tr').filterAttr('data-file',file.name).data('mime',file.mime);
							if(size=='Pending'){
								$('tr').filterAttr('data-file',file.name).find('td.filesize').text(file.size);
							}
							FileList.loadingDone(file.name);
						}
					}
					else{
						$('#notification').text(t('files',response.data.message));
						$('#notification').fadeIn();
						$('#fileList > tr').not('[data-mime]').fadeOut();
						$('#fileList > tr').not('[data-mime]').remove();
					}
				}
			});
			form.submit();
			var date=new Date();
			if(files){
				for(var i=0;i<files.length;i++){
					if(files[i].size>0){
						var size=files[i].size;
					}else{
						var size=t('files','Pending');
					}
					if(files){
						FileList.addFile(files[i].name,size,date,true);
					}
				}
			}else{
				var filename=this.value.split('\\').pop(); //ie prepends C:\fakepath\ in front of the filename
				FileList.addFile(filename,'Pending',date,true);
			}

			//clone the upload form and hide the new one to allow users to start a new upload while the old one is still uploading
			var clone=form.clone();
			uploadId++;
			clone.attr('data-upload-id',uploadId);
			clone.attr('target','file_upload_target_'+uploadId);
			clone.children('iframe').attr('name','file_upload_target_'+uploadId)
			clone.insertBefore(form);
			form.hide();
		}
	});

	//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
	if(navigator.userAgent.search(/konqueror/i)==-1){
		$('.file_upload_start').attr('multiple','multiple')
	}

	//if the breadcrumb is to long, start by replacing foldernames with '...' except for the current folder
	var crumb=$('div.crumb').first();
	while($('div.controls').height()>40 && crumb.next('div.crumb').length>0){
		crumb.children('a').text('...');
		crumb=crumb.next('div.crumb');
	}
	//if that isn't enough, start removing items from the breacrumb except for the current folder and it's parent
	var crumb=$('div.crumb').first();
	var next=crumb.next('div.crumb');
	while($('div.controls').height()>40 && next.next('div.crumb').length>0){
		crumb.remove();
		crumb=next;
		next=crumb.next('div.crumb');
	}
	//still not enough, start shorting down the current folder name
	var crumb=$('div.crumb>a').last();
	while($('div.controls').height()>40 && crumb.text().length>6){
		var text=crumb.text()
		text=text.substr(0,text.length-6)+'...';
		crumb.text(text);
	}

	$(window).click(function(){
		$('#new>ul').hide();
		$('#new').removeClass('active');
		$('button.file_upload_filename').removeClass('active');
		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('input').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});
	});
	$('#new').click(function(event){
		event.stopPropagation();
	});
	$('#new>a').click(function(){
		$('#new>ul').toggle();
		$('#new').toggleClass('active');
		$('button.file_upload_filename').toggleClass('active');
	});
	$('#new li').click(function(){
		if($(this).children('p').length==0){
			return;
		}

		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('input').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});

		var type=$(this).data('type');
		var text=$(this).children('p').text();
		$(this).data('text',text);
		$(this).children('p').remove();
		var input=$('<input>');
		$(this).append(input);
		input.focus();
		input.change(function(){
			var name=$(this).val();
			switch(type){
				case 'file':
					$.post(
						OC.filePath('files','ajax','newfile.php'),
						{dir:$('#dir').val(),filename:name,content:" \n"},
						function(data){
							var date=new Date();
							FileList.addFile(name,0,date);
							var tr=$('tr').filterAttr('data-file',name);
							tr.data('mime','text/plain');
							getMimeIcon('text/plain',function(path){
								tr.find('td.filename').attr('style','background-image:url('+path+')');
							});
						}
					);
					break;
				case 'folder':
					$.post(
						OC.filePath('files','ajax','newfolder.php'),
						{dir:$('#dir').val(),foldername:name},
						function(data){
							var date=new Date();
							FileList.addDir(name,0,date);
						}
					);
					break;
				case 'web':
					if(name.substr(0,8)!='https://' && name.substr(0,7)!='http://'){
						name='http://'.name;
					}
					var localName=name;
					if(localName.substr(localName.length-1,1)=='/'){//strip /
						localName=localName.substr(0,localName.length-1)
					}
					if(localName.indexOf('/')){//use last part of url
						localName=localName.split('/').pop();
					}else{//or the domain
						localName=(localName.match(/:\/\/(.[^/]+)/)[1]).replace('www.','');
					}
					$.post(
						OC.filePath('files','ajax','newfile.php'),
						{dir:$('#dir').val(),source:name,filename:localName},
						function(result){
							if(result.status == 'success'){
								var date=new Date();
								FileList.addFile(localName,0,date);
								var tr=$('tr').filterAttr('data-file',localName);
								tr.data('mime',result.data.mime);
								getMimeIcon(result.data.mime,function(path){
									tr.find('td.filename').attr('style','background-image:url('+path+')');
								});
							}else{

							}
						}
					);
					break;
			}
			var li=$(this).parent();
			$(this).remove();
			li.append('<p>'+li.data('text')+'</p>');
			$('#new>a').click();
		});
	});

	//check if we need to scan the filesystem
	$.get(OC.filePath('files','ajax','scan.php'),{checkonly:'true'}, function(response) {
		if(response.data.done){
			scanFiles();
		}
	}, "json");
});

function scanFiles(force){
	force=!!force; //cast to bool
	scanFiles.scanning=true;
	$('#scanning-message').show();
	$('#fileList').remove();
	var scannerEventSource=new OC.EventSource(OC.filePath('files','ajax','scan.php'),{force:force});
	scanFiles.cancel=scannerEventSource.close.bind(scannerEventSource);
	scannerEventSource.listen('scanning',function(data){
		$('#scan-count').text(data.count+' files scanned');
		$('#scan-current').text(data.file+'/');
	});
	scannerEventSource.listen('success',function(success){
		scanFiles.scanning=false;
		if(success){
			window.location.reload();
		}else{
			alert('error while scanning');
		}
	});
}
scanFiles.scanning=false;

function boolOperationFinished(data, callback) {
	result = jQuery.parseJSON(data.responseText);
	if(result.status == 'success'){
		callback.call();
	} else {
		alert(result.data.message);
	}
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

//options for file drag/dropp
var dragOptions={
	distance: 20, revert: 'invalid', opacity: 0.7,
	stop: function(event, ui) {
		$('#fileList tr td.filename').addClass('ui-draggable');
	}
};
var folderDropOptions={
	drop: function( event, ui ) {
		var file=ui.draggable.text().trim();
		var target=$(this).text().trim();
		var dir=$('#dir').val();
		$.ajax({
			url: 'ajax/move.php',
		data: "dir="+dir+"&file="+file+'&target='+dir+'/'+target,
		complete: function(data){boolOperationFinished(data, function(){
			var el = $('#fileList tr').filterAttr('data-file',file).find('td.filename');
			el.draggable('destroy');
			FileList.remove(file);
		});}
		});
	}
}
var crumbDropOptions={
	drop: function( event, ui ) {
		var file=ui.draggable.text().trim();
		var target=$(this).data('dir');
		var dir=$('#dir').val();
		while(dir.substr(0,1)=='/'){//remove extra leading /'s
				dir=dir.substr(1);
		}
		dir='/'+dir;
		if(dir.substr(-1,1)!='/'){
			dir=dir+'/';
		}
		if(target==dir){
			return;
		}
		$.ajax({
			url: 'ajax/move.php',
		 data: "dir="+dir+"&file="+file+'&target='+target,
		 complete: function(data){boolOperationFinished(data, function(){
			 FileList.remove(file);
		 });}
		});
	},
	tolerance: 'pointer'
}

function procesSelection(){
	var selected=getSelectedFiles();
	var selectedFiles=selected.filter(function(el){return el.type=='file'});
	var selectedFolders=selected.filter(function(el){return el.type=='dir'});
	if(selectedFiles.length==0 && selectedFolders.length==0){
		$('#headerName>span.name').text(t('files','Name'));
		$('#headerSize').text(t('files','Size'));
		$('#modified').text(t('files','Modified'));
		$('th').removeClass('multiselect');
		$('.selectedActions').hide();
		$('thead').removeClass('fixed');
		$('#headerName').css('width','auto');
		$('#headerSize').css('width','auto');
		$('#headerDate').css('width','auto');
		$('table').css('padding-top','0');
	}else{
		var width={name:$('#headerName').css('width'),size:$('#headerSize').css('width'),date:$('#headerDate').css('width')};
		$('#headerName').css('width',width.name);
		$('#headerSize').css('width',width.size);
		$('#headerDate').css('width',width.date);
		$('.selectedActions').show();
		var totalSize=0;
		for(var i=0;i<selectedFiles.length;i++){
			totalSize+=selectedFiles[i].size;
		};
		for(var i=0;i<selectedFolders.length;i++){
			totalSize+=selectedFolders[i].size;
		};
		simpleSize=simpleFileSize(totalSize);
		$('#headerSize').text(simpleSize);
		$('#headerSize').attr('title',humanFileSize(totalSize));
		var selection='';
		if(selectedFolders.length>0){
			if(selectedFolders.length==1){
				selection+='1 '+t('files','folder');
			}else{
				selection+=selectedFolders.length+' '+t('files','folders');
			}
			if(selectedFiles.length>0){
				selection+=' & ';
			}
		}
		if(selectedFiles.length>0){
			if(selectedFiles.length==1){
				selection+='1 '+t('files','file');
			}else{
				selection+=selectedFiles.length+' '+t('files','files');
			}
		}
		$('#headerName>span.name').text(selection);
		$('#modified').text('');
		$('th').addClass('multiselect');
	}
}

/**
 * @brief get a list of selected files
 * @param string property (option) the property of the file requested
 * @return array
 *
 * possible values for property: name, mime, size and type
 * if property is set, an array with that property for each file is returnd
 * if it's ommited an array of objects with all properties is returned
 */
function getSelectedFiles(property){
	var elements=$('td.filename input:checkbox:checked').parent().parent();
	var files=[];
	elements.each(function(i,element){
		var file={
			name:$(element).attr('data-file'),
			mime:$(element).data('mime'),
			type:$(element).data('type'),
			size:$(element).data('size'),
		};
		if(property){
			files.push(file[property]);
		}else{
			files.push(file);
		}
	});
	return files;
}

function relative_modified_date(timestamp) {
	var timediff = Math.round((new Date()).getTime() / 1000) - timestamp;
	var diffminutes = Math.round(timediff/60);
	var diffhours = Math.round(diffminutes/60);
	var diffdays = Math.round(diffhours/24);
	var diffmonths = Math.round(diffdays/31);
	var diffyears = Math.round(diffdays/365);
	if(timediff < 60) { return t('files','seconds ago'); }
	else if(timediff < 120) { return '1 '+t('files','minute ago'); }
	else if(timediff < 3600) { return diffminutes+' '+t('files','minutes ago'); }
	//else if($timediff < 7200) { return '1 hour ago'; }
	//else if($timediff < 86400) { return $diffhours.' hours ago'; }
	else if(timediff < 86400) { return t('files','today'); }
	else if(timediff < 172800) { return t('files','yesterday'); }
	else if(timediff < 2678400) { return diffdays+' '+t('files','days ago'); }
	else if(timediff < 5184000) { return t('files','last month'); }
	//else if($timediff < 31556926) { return $diffmonths.' months ago'; }
	else if(timediff < 31556926) { return t('files','months ago'); }
	else if(timediff < 63113852) { return t('files','last year'); }
	else { return diffyears+' '+t('files','years ago'); }
}

function getMimeIcon(mime, ready){
	if(getMimeIcon.cache[mime]){
		ready(getMimeIcon.cache[mime]);
	}else{
		$.get( OC.filePath('files','ajax','mimeicon.php')+'?mime='+mime, function(path){
			getMimeIcon.cache[mime]=path;
			ready(getMimeIcon.cache[mime]);
		});
	}
}
getMimeIcon.cache={};
