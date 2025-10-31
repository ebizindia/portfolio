<?php
if ($this->body_template_data['mode'] == 'getList') {
$mode_index = $this->body_template_data['mode'];
$this->body_template_data[$mode_index]['records'];

if ($this->body_template_data[$mode_index]['filtertext'] != '') {
$filtertext = 'Filter';
if ($this->body_template_data[$mode_index]['filtercount'] > 1) {
$filtertext = 'Filters';
}
echo '<tr>
    <td align="center" class="label-yellow padding-10 filter-row" colspan="6" id="search_data">',
        $this->body_template_data[$mode_index]['filtertext'],
        ' <a class="clear-filter" onclick="itemsStock.clearSearch();">Clear Filter</a>
    </td>
</tr>';
}

if ($this->body_template_data[$mode_index]['records_count'] == 0) {
echo "<tr><td colspan='6' class='text-danger' align='center'>No records found.</td></tr>";
} else {
for ($i_ul = 0; $i_ul < $this->body_template_data[$mode_index]['records_count']; $i_ul++) {
require CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'items-stock-row.tpl';
}

if ($this->body_template_data[$mode_index]['pagination_html'] != '') {
echo "<tr><td colspan='6' class='pagination-row'>";
        echo $this->body_template_data[$mode_index]['pagination_html'];
        echo "</td></tr>";
}
}
} else {
?>
<style>
    .rounded {
        border-radius: 0.35rem !important;
    }
    .import-section {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .csv-format-info {
        background-color: #e3f2fd;
        border: 1px solid #bbdefb;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-top: 0.5rem;
    }
    @media (max-width:760px) {
        #stocklistbox tr td{
            white-space:normal;
            word-break:break-word;
        }
    }
</style>
<div class="row">
    <div id='rec_list_container' class="col-12 mt-3 mb-2">
        <div class="card">
            <div class="card-body">
                <div class="card-header-heading">
                    <div class="row">
                        <div class="col-8">
                            <h4 class="row pg_heading_line_ht">
                                Items Stock&nbsp;
                                <span id="heading_rec_cnt" style="color: #0c0c0cab;">0</span>
                            </h4>
                        </div>
                        <div class="col-4 text-right">
                            <div class="row btns-user-add" style="float:right;">
                                <a class="btn btn-primary toggle-search rounded" href="javascript:void(0);">
                                    <img src="images/search-plus.png" class="custom-button fa-search-plus" alt="Search">
                                    <img src="images/search-minus.png" class="custom-button fa-search-minus" alt="Search">
                                </a>
                                <?php if ($this->body_template_data['can_import'] === true) { ?>
                                <a href="items-stock.php#mode=import" class="btn btn-success rounded record-import-button" id="import-records-button">
                                    <img src="images/import.png" class="custom-button-small" alt="Upload">
                                    <span class="hide_in_mobile">Import File</span>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-search d-none pb-2">
                    <?php include CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'items-stock-search-form-basic.tpl'; ?>
                </div>
                <div class="responsive-block-table for-cat">
                    <div class="panel-body table-responsive">
                        <table id="recs-list" class="table table-striped table-bordered table-hover">
                            <thead class="thead">
                            <tr>
                                <th class='sortable' id="colheader_warehouse-name" width="150">
                                    <span class="pull-left">Warehouse</span>
                                    <i class='fa fa-sort pull-right'></i>
                                </th>
                                <th class='sortable' id="colheader_item-name" width="200">
                                    <span class="pull-left">Item Name</span>
                                    <i class='fa fa-sort pull-right'></i>
                                </th>
                                <th class='sortable' id="colheader_quantity" width="100">
                                    <span class="pull-left">Quantity</span>
                                    <i class='fa fa-sort pull-right'></i>
                                </th>
                                <th width="80" id="colheader_unit">
                                    <span class="pull-left">Unit</span>
                                </th>
                                <th width="100" id="colheader_expiry">
                                    <span class="pull-left">Expiry</span>
                                </th>
                                <th class='sortable' id="colheader_as-on-date" width="120">
                                    <span class="pull-left">As On Date</span>
                                    <i class='fa fa-sort pull-right'></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody id='stocklistbox'></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3 mb-2 d-none" id='rec_import_container'>
        <?php require_once CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'items-stock-import.tpl'; ?>
    </div>
</div>
<?php
}
?>