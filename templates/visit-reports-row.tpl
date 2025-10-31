<?php $action_mode = 'view'; ?>
<tr id="record_row_<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="responsive-task-cat">
    <td>
        <div style="text-align:left;" class="icons_container_block">
            <a style="margin-right:8px;" href="visit-reports.php#mode=<?php echo $action_mode; ?>&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
               class="btn btn-xs btn-info rounded user-view-action record-view-button"
               data-in-mode="list-mode"
               data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
               data-rel='tooltip'
               title="View details">
                <img src="images/view-white.png" class="custom-button-small" alt="View">
            </a>
            <?php if ($this->body_template_data[$mode_index]['user_role'] === 'ADMIN') { ?>
            <a href="javascript:void(0);"
               class="btn btn-xs btn-danger rounded delete-record-btn"
               data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
               data-rel='tooltip'
               title="Delete record">
                <img src="images/delete-white.webp" class="custom-button-small" alt="Delete">
            </a>
            <?php } ?>
        </div>
    </td>

    <td data-label="Visit Date: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php echo date('d-m-Y', strtotime($this->body_template_data[$mode_index]['records'][$i_ul]['visit_date'])); ?>
    </td>

    <td data-label="Type: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php 
        $type = '';
        switch($this->body_template_data[$mode_index]['records'][$i_ul]['type']) {
            case 1: $type = 'New'; break;
            case 2: $type = 'Existing'; break;
            default: $type = '-';
        }
        echo \eBizIndia\_esc($type);
        ?>
    </td>

    <td data-label="Group: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['customer_group_name']); ?>
    </td>

    <td data-label="Customer: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['customer_name']); ?>
    </td>

    <!-- NEW: Department Column -->
    <td data-label="Department: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php 
        $department = '';
        switch($this->body_template_data[$mode_index]['records'][$i_ul]['department']) {
            case 1: $department = 'Supply Chain'; break;
            case 2: $department = 'R & D'; break;
            case 3: $department = 'Others'; break;
            default: $department = '-';
        }
        echo \eBizIndia\_esc($department);
        ?>
    </td>

    <td data-label="Meeting Title: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['meeting_title']); ?>
    </td>

    <td data-label="Attachment: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer ">
        <?php if($this->body_template_data[$mode_index]['records'][$i_ul]['attachment_file_name']){?>
        <a href="<?php echo CONST_UPLOAD_DIR_URL.CONST_VISIT_REPORT_DIR.'/';echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['attachment_file_path']); ?>" target="doc">
            <?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['attachment_file_name'];?>
        </a>
        <?php }?>
    </td>

    <?php if (true || $this->body_template_data[$mode_index]['user_role'] === 'ADMIN') { ?>
    <td data-label="Salesperson: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['created_by_name']); ?>
    </td>
    <?php }
        $submitted_on = date('d-m-Y, g:i a', strtotime($this->body_template_data[$mode_index]['records'][$i_ul]['created_on']));

    ?>

    <td data-label="Submitted On: "
        data-in-mode="list-mode"
        data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        data-hash="<?php echo 'mode=' . $action_mode . '&recid=' . $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"
        class="pointer clickable-cell pseudo-link">
        <?php \eBizIndia\_esc($submitted_on); ?>
    </td>
</tr>