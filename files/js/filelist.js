FileList={
	update:function(fileListHtml) {
		$('#fileList').empty().html(fileListHtml);
	},
	addFile:function(name,size,lastModified,loading){
		var img=(loading)?OC.imagePath('core', 'loading.gif'):OC.imagePath('core', 'filetypes/file.png');
		var html='<tr data-file="'+name+'" data-type="file" data-size="'+size+'">';
		if(name.indexOf('.')!=-1){
			var basename=name.substr(0,name.lastIndexOf('.'));
			var extention=name.substr(name.lastIndexOf('.'));
		}else{
			var basename=name;
			var extention=false;
		}
		html+='<td class="filename" style="background-image:url('+img+')"><input type="checkbox" />';
		html+='<a class="name" href="download.php?file='+$('#dir').val()+'/'+name+'"><span class="nametext">'+basename
		if(extention){
			html+='<span class="extention">'+extention+'</span>';
		}
		html+='</span></a></td>';
		if(size!='Pending'){
			simpleSize=simpleFileSize(size);
		}else{
			simpleSize='Pending';
		}
		sizeColor = Math.round(200-size/(1024*1024)*2);
		lastModifiedTime=Math.round(lastModified.getTime() / 1000);
		modifiedColor=Math.round((Math.round((new Date()).getTime() / 1000)-lastModifiedTime)/60/60/24*14);
		html+='<td class="filesize" title="'+humanFileSize(size)+'" style="color:rgb('+sizeColor+','+sizeColor+','+sizeColor+')">'+simpleSize+'</td>';
		html+='<td class="date"><span class="modified" title="'+formatDate(lastModified)+'" style="color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')">'+relative_modified_date(lastModified.getTime() / 1000)+'</span></td>';
		html+='</tr>';
		FileList.insertElement(name,'file',$(html));
		if(loading){
			$('tr[data-file="'+name+'"]').data('loading',true);
		}else{
			$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
		}
	},
	addDir:function(name,size,lastModified){
		var html='<tr data-file="'+name+'" data-type="dir" data-size="'+size+'">';
		html+='<td class="filename" style="background-image:url('+OC.imagePath('core', 'places/folder')+')"><input type="checkbox" /><a class="name" href="index.php?dir='+$('#dir').val()+'/'+name+'">'+name+'</a></td>';
		if(size!='Pending'){
			simpleSize=simpleFileSize(size);
		}else{
			simpleSize='Pending';
		}
		sizeColor = Math.round(200-Math.pow((size/(1024*1024)),2));
		lastModifiedTime=Math.round(lastModified.getTime() / 1000);
		modifiedColor=Math.round((Math.round((new Date()).getTime() / 1000)-lastModifiedTime)/60/60/24*5);
		html+='<td class="filesize" title="'+humanFileSize(size)+'" style="color:rgb('+sizeColor+','+sizeColor+','+sizeColor+')">'+simpleSize+'</td>';
		html+='<td class="date"><span class="modified" title="'+formatDate(lastModified)+'" style="color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')">'+relative_modified_date(lastModified.getTime() / 1000)+'</span></td>';
		html+='</tr>';
		
		FileList.insertElement(name,'dir',$(html));
		$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
		$('tr[data-file="'+name+'"] td.filename').droppable(folderDropOptions);
	},
	refresh:function(data) {
		result = jQuery.parseJSON(data.responseText);
		if(typeof(result.data.breadcrumb) != 'undefined'){
			updateBreadcrumb(result.data.breadcrumb);
		}
		FileList.update(result.data.files);
		resetFileActionPanel();
	},
	remove:function(name){
		$('tr[data-file="'+name+'"] td.filename').draggable('destroy');
		$('tr[data-file="'+name+'"]').remove();
		if($('tr[data-file]').length==0){
			$('#emptyfolder').show();
			$('.file_upload_filename').addClass('highlight');
		}
	},
	insertElement:function(name,type,element){
		//find the correct spot to insert the file or folder
		var fileElements=$('tr[data-file][data-type="'+type+'"]');
		var pos;
		if(name.localeCompare($(fileElements[0]).attr('data-file'))<0){
			pos=-1;
		}else if(name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file'))>0){
			pos=fileElements.length-1;
		}else{
			for(var pos=0;pos<fileElements.length-1;pos++){
				if(name.localeCompare($(fileElements[pos]).attr('data-file'))>0 && name.localeCompare($(fileElements[pos+1]).attr('data-file'))<0){
					break;
				}
			}
		}
		if(fileElements.length){
			if(pos==-1){
				$(fileElements[0]).before(element);
			}else{
				$(fileElements[pos]).after(element);
			}
		}else if(type=='dir' && $('tr[data-file]').length>0){
			$('tr[data-file]').first().before(element);
		}else{
			$('#fileList').append(element);
		}
		$('#emptyfolder').hide();
		$('.file_upload_filename').removeClass('highlight');
	},
	loadingDone:function(name){
		$('tr[data-file="'+name+'"]').data('loading',false);
		var mime=$('tr[data-file="'+name+'"]').data('mime');
		$('tr[data-file="'+name+'"] td.filename').attr('style','background-image:url('+getMimeIcon(mime)+')');
		$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
	},
	isLoading:function(name){
		return $('tr[data-file="'+name+'"]').data('loading');
	},
	rename:function(name){
		var tr=$('tr[data-file="'+name+'"]');
		tr.data('renaming',true);
		var td=tr.children('td.filename');
		var input=$('<input value="'+name+'" class="filename"></input>');
		var form=$('<form action="#"></form>')
		form.append(input);
		td.children('a.name').text('');
		td.children('a.name').append(form)
		input.focus();
		form.submit(function(event){
			event.stopPropagation();
			event.preventDefault();
			var newname=input.val();
			tr.data('renaming',false);
			tr.attr('data-file',newname);
			td.children('a.name').empty();
			if(newname.indexOf('.')>0){
				basename=newname.substr(0,newname.lastIndexOf('.'));
			}else{
				basename=newname;
			}
			var span=$('<span class="nametext"></span>');
			span.text(basename);
			td.children('a.name').append(span);
			if(newname.indexOf('.')>0){
				span.append($('<span class="extention">'+newname.substr(newname.lastIndexOf('.'))+'</span>'));
			}
			$.ajax({
				url: 'ajax/rename.php',
				data: "dir="+$('#dir').val()+"&newname="+encodeURIComponent(newname)+"&file="+encodeURIComponent(name)
			});
		});
		form.click(function(event){
			event.stopPropagation();
			event.preventDefault();
		});
		input.blur(function(){
			form.trigger('submit');
		});
	},
	do_delete:function(files){
		if(FileList.deleteFiles){//finish any ongoing deletes first
			FileList.finishDelete(function(){
				FileList.do_delete(files);
			});
			return;
		}
		if(files.substr){
			files=[files];
		}
		$.each(files,function(index,file){
			$('tr[data-file="'+file+'"]').hide();
			$('tr[data-file="'+file+'"]').find('input[type="checkbox"]').removeAttr('checked');
			$('tr[data-file="'+file+'"]').removeClass('selected');
		});
		procesSelection();
		FileList.deleteCanceled=false;
		FileList.deleteFiles=files;
		$('#notification').text(t('files','undo deletion'));
		$('#notification').fadeIn();
	},
	finishDelete:function(ready,sync){
		if(!FileList.deleteCanceled && FileList.deleteFiles){
			var fileNames=FileList.deleteFiles.join(';');
			$.ajax({
				url: 'ajax/delete.php',
				async:!sync,
				data: "dir="+$('#dir').val()+"&files="+encodeURIComponent(fileNames),
				complete: function(data){
					boolOperationFinished(data, function(){
						$('#notification').fadeOut();
						$.each(FileList.deleteFiles,function(index,file){
// 							alert(file);
							FileList.remove(file);
						});
						FileList.deleteCanceled=true;
						FileList.deleteFiles=null;
						if(ready){
							ready();
						}
					});
				}
			});
		}
	}
}

$(document).ready(function(){
	$('#notification').hide();
	$('#notification').click(function(){
		FileList.deleteCanceled=true;
		$('#notification').fadeOut();
		$.each(FileList.deleteFiles,function(index,file){
			$('tr[data-file="'+file+'"]').show();
// 			alert(file);
		});
		FileList.deleteFiles=null;
	});
	$(window).bind('beforeunload', function (){
		FileList.finishDelete(null,true);
	});
});
