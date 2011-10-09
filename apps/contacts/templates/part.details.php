<?php if(array_key_exists('FN',$_['details'])): ?>
	<table id="contacts_details">
		<?php if(isset($_['details']['PHOTO'])): // Emails first ?>
			<tr class="contacts_details_property">
				<td class="contacts_details_left">&nbsp;</td>
				<td class="contacts_details_right">
					<img src="photo.php?id=<?php echo $_['id']; ?>">
				</td>
			</tr>
		<?php endif; ?>
		
		<?php echo $this->inc('part.property', array('property' => $_['details']['FN'][0])); ?>
		
		<?php if(isset($_['details']['BDAY'])): // Emails first ?>
			<?php echo $this->inc('part.property', array('property' => $_['details']['BDAY'][0])); ?>
		<?php endif; ?>

		<?php if(isset($_['details']['ORG'])): // Emails first ?>
			<?php echo $this->inc('part.property', array('property' => $_['details']['ORG'][0])); ?>
		<?php endif; ?>

		<?php foreach(array('EMAIL','TEL','ADR') as $type): ?>
			<?php if(isset($_['details'][$type])): // Emails first ?>
				<?php foreach($_['details'][$type] as $property): ?>
					<?php echo $this->inc('part.property',array('property' => $property )); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</table>
	<form>
		<img class="svg action" id="contacts_deletecard" src="<?php echo image_path('', 'actions/delete.svg'); ?>" title="<?php echo $l->t('Delete contact');?>" />
		<input type="button" id="contacts_addproperty" value="<?php echo $l->t('Add Property');?>">
	</form>
<?php endif; ?>
