<tr id="record_row_<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" class="responsive-task-cat">
    <td data-label="Warehouse: ">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['warehouse_name']); ?>
    </td>

    <td data-label="Item Name: ">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['item_name']); ?>
    </td>

    <td data-label="Quantity: " class="text-right">
        <?php echo number_format($this->body_template_data[$mode_index]['records'][$i_ul]['quantity'], 3); ?>
    </td>

    <td data-label="Unit: ">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['unit']); ?>
    </td>

    <td data-label="Expiry: ">
        <?php \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['expiry_display']); ?>
    </td>

    <td data-label="As On Date: ">
        <?php echo date('d-m-Y', strtotime($this->body_template_data[$mode_index]['records'][$i_ul]['as_on_date'])); ?>
    </td>
</tr>