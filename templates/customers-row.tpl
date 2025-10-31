<?php $action_mode = 'edit'; $class_for_edit=$this->body_template_data['can_edit']?'pointer clickable-cell pseudo-link':''; ?>
<tr id="record_row_<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="responsive-task-cat">
    <?php 
    $status_cls = 'text-danger';
    $status_span_cls = 'status-notlive';
    $status_text = 'No';
    if ($this->body_template_data[$mode_index]['records'][$i_ul]['active'] == 'y') {
        $status_cls = 'text-success';
        $status_span_cls = 'status-live';
        $status_text = 'Yes';
    }
    ?>
    <td>
        <div style="width:80px;text-align:left;" class="icons_container_block">
            <?php
		    if($this->body_template_data['can_edit']){
            ?>
            <a href="customers.php#mode=<?php echo $action_mode; ?>&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
               class="btn btn-xs btn-success user-edit-action record-edit-button rounded" 
               data-in-mode="list-mode" 
               data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
               data-rel='tooltip' 
               title="Edit details">
                <img src="images/edit-white.webp" class="custom-button-small" alt="Edit">
            </a>
            <?php
            }

            if($this->body_template_data['can_delete']){
            ?>
            <a href="javascript:void(0);" 
               class="btn btn-xs btn-danger record-delete-button rounded ml-2" 
               data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
               data-rel='tooltip' 
               title="Delete customer">
                <img src="images/delete-white.webp" class="custom-button-small" alt="Delete">
            </a>
            <?php
            }
            ?>
        </div>
    </td>
    
    <td data-label="Customer: " 
        data-in-mode="list-mode" 
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        class="<?php echo $class_for_edit; ?>">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['name']); ?>
    </td>
    <td data-label="Customer Group: " 
        data-in-mode="list-mode" 
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        class="<?php echo $class_for_edit; ?>">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['customer_group_name']); ?>
    </td>
    <td data-label="Customer Industry: " 
        data-in-mode="list-mode" 
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        class="<?php echo $class_for_edit; ?>">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['industry_name']); ?>
    </td>
    <td data-label="Active: " 
        class="hidden-480 <?php echo $status_cls.' '.$class_for_edit; ?> "
        data-in-mode="list-mode" 
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" 
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>">
        <span class='<?php echo $status_span_cls; ?> '><?php \eBizIndia\_esc($status_text); ?></span>
    </td>
</tr>