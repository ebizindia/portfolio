<tr id="record_row_<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="responsive-task-cat">
    <?php $action_mode = 'edit'; ?>
    <td>
        <div class="">
            <a href="items.php#mode=<?php echo $action_mode; ?>&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="btn btn-xs btn-success user-edit-action record-edit-button rounded" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" data-rel='tooltip' title="Edit details">
                <img src="images/edit-white.png" class="custom-button-small" alt="Edit">
            </a>

            <a href="#" class="btn btn-xs btn-danger record-delete-button rounded ml-2" data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" data-item="<?php echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['name'], true); ?>" data-rel='tooltip' title="Delete item">
                <img src="images/delete-white.png" class="custom-button-small" alt="Delete">
            </a>
        </div>
    </td>
    <td data-label="Name: " data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="pointer clickable-cell pseudo-link resp_bold">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['name']); ?>
    </td>
    <td data-label="Make: " data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="pointer clickable-cell pseudo-link">
        <?php echo !empty($this->body_template_data[$mode_index]['records'][$i_ul]['make']) ? \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['make']) : ''; ?>
    </td>
    <td data-label="Unit: " data-in-mode="list-mode" data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['unit']); ?>
    </td>
</tr>