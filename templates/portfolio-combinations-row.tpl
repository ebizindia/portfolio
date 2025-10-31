<tr id="record_row_<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" class="responsive-task-cat">

	<?php $action_mode = 'edit'; $class_for_edit=$this->body_template_data['can_edit']?'pointer clickable-cell pseudo-link':'';  ?>

  <td>
	<div style="width:80px;text-align:left;" class="icons_container_block">
		<?php
		if($action_mode!=='edit' || $this->body_template_data['can_edit']){
		?>
		<a href="portfolio-combinations.php#mode=<?php echo $action_mode; ?>&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" class="btn btn-xs btn-success user-edit-action record-edit-button rounded" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-rel='tooltip' title="Edit details">
				<img src="images/edit-white.webp" class="custom-button-small" alt="Edit">
			</a>
		<?php
		}

		if($this->body_template_data['can_delete']){
		?>

		<a href="#" class="btn btn-xs btn-danger record-delete-button rounded ml-2" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-combinationname="<?php echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['combination_name'], true); ?>" data-rel='tooltip' title="Delete combination">
				<img src="images/delete-white.webp" class="custom-button-small" alt="Delete">
			</a>
		<?php
		}
		?>
	</div>


	</td>

  <td data-label="Combination Name: " data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-hash="<?php echo 'mode='.$action_mode.'&recid=',$this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" class="<?php echo $class_for_edit; ?>">

  <?php

  	\eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['combination_name']);

  ?>
  </td>

  <td data-label="Description: " class="<?php echo $class_for_edit; ?>" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-hash="<?php echo 'mode='.$action_mode.'&recid=',$this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>">
  <?php
  	\eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['description'] ?? '');
  ?>
  </td>

  <td data-label="Portfolios: " class="<?php echo $class_for_edit; ?>" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-hash="<?php echo 'mode='.$action_mode.'&recid=',$this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>">
  <?php
  	$portfolio_names = $this->body_template_data[$mode_index]['records'][$i_ul]['portfolio_names'] ?? '';
	$portfolio_count = $this->body_template_data[$mode_index]['records'][$i_ul]['portfolio_count'] ?? 0;

	if($portfolio_count > 0) {
		echo '<span title="'.\eBizIndia\_esc($portfolio_names, true).'">';
		echo $portfolio_count . ' portfolio' . ($portfolio_count > 1 ? 's' : '');
		echo '</span>';
	} else {
		echo '<span class="text-muted">No portfolios</span>';
	}
  ?>
  </td>

	<td data-label="Created: " class="<?php echo $class_for_edit; ?>" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>" data-hash="<?php echo 'mode='.$action_mode.'&recid=',$this->body_template_data[$mode_index]['records'][$i_ul]['combination_id']; ?>">
	<?php
		if(!empty($this->body_template_data[$mode_index]['records'][$i_ul]['created_at'])){
			echo date('d-M-Y', strtotime($this->body_template_data[$mode_index]['records'][$i_ul]['created_at']));
		}
	?>
	</td>

</tr>
