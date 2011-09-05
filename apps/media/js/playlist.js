PlayList.render=function(){
	$('#playlist').show();
	PlayList.parent.empty();
	for(var i=0;i<PlayList.items.length;i++){
		var item=PlayList.items[i];
		var li=$('<li/>');
		li.append(item.name);
		var img=$('<img class="remove svg action" src="'+OC.imagePath('core','actions/delete')+'"/>');
		img.click(function(event){
			event.stopPropagation();
			PlayList.remove($(this).parent().data('index'));
		});
		li.click(function(event){
			PlayList.play($(this).data('index'));
		});
		li.append(img)
		li.data('index',i);
		li.addClass('song');
		PlayList.parent.append(li);
	}
}
PlayList.getSelected=function(){
	return $('tbody td.name input:checkbox:checked').parent().parent();
}
PlayList.hide=function(){
	$('#playlist').hide();
}

$(document).ready(function(){
	PlayList.parent=$('#leftcontent');
	$('#selectAll').click(function(){
		if($(this).attr('checked')){
			// Check all
			$('#leftcontent li.song input:checkbox').attr('checked', true);
			$('#leftcontent li.song input:checkbox').parent().addClass('selected');
		}else{
			// Uncheck all
			$('#leftcontent li.song input:checkbox').attr('checked', false);
			$('#leftcontent li.song input:checkbox').parent().removeClass('selected');
		}
	});
});
