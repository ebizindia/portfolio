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
    <td align="center" class="label-yellow padding-10 filter-row" colspan="10" id="search_data">',
        $this->body_template_data[$mode_index]['filtertext'],
        ' <a class="clear-filter" onclick="visitReports.clearSearch();">Clear Filter</a>
    </td>
</tr>';
}

if ($this->body_template_data[$mode_index]['records_count'] == 0) {
echo "<tr><td colspan='10' class='text-danger' align='center'>No records found.</td></tr>";
} else {
for ($i_ul = 0; $i_ul < $this->body_template_data[$mode_index]['records_count']; $i_ul++) {
require CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'visit-reports-row.tpl';
}

if ($this->body_template_data[$mode_index]['pagination_html'] != '') {
echo "<tr><td colspan='10' class='pagination-row'>";
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
    /* CKEditor Styles */
    .ck-editor__editable_inline:not(.ck-comment__input *) {
        min-height: 300px;
    }
    .ck-editor__editable_inline.ck-read-only {
        background-color: #f1f1f1 !important;
    }

    /* CKEditor content display */
    .ck-content {
        font-family: inherit;
        line-height: 1.6;
    }

    .ck-content h1, .ck-content h2, .ck-content h3 {
        margin-top: 0.8em;
        margin-bottom: 0.4em;
    }

    .ck-content p {
        margin-bottom: 0.5em;
    }

    .ck-content ul, .ck-content ol {
        margin-left: 1.5em;
        margin-bottom: 0.5em;
    }

    #existing-contacts-select{
        /*min-height: 50px !important;*/
        height: auto !important;
    }

    #existing-contacts-select option{
        padding: 3px;
        margin-bottom: 5px;
    }

    /* Error styling for contacts table */
    #contacts-table.error-field {
        border: 2px solid #dc3545;
        border-radius: 4px;
    }

    /* Required field styling */
    .mandatory {
        color: #dc3545;
        font-weight: bold;
    }

    /* Admin Notes CKEditor Styles */
    #admin-notes-edit .ck-editor__editable_inline:not(.ck-comment__input *) {
        min-height: 200px;
    }

    #admin-notes-edit .ck-editor__editable_inline.ck-read-only {
        background-color: #f1f1f1 !important;
    }

    #admin-notes-edit .ck-editor__main {
        margin-bottom: 10px;
    }

    /* Admin Notes Content Display */
    #admin-notes-view.ck-content {
        font-family: inherit;
        line-height: 1.6;
    }
.view_contact_block{

}
.view_contact_block .view_contact_block_border{
    border-bottom: 1px solid #ccc !important;
}
.view_contact_block .view_contact_block_border:only-child, .view_contact_block .view_contact_block_border:last-child{
    border-bottom: 0px solid #ccc !important;
}   
@media (max-width:760px){
	#userlistbox td{
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
                                Customer Visit Reports&nbsp;
                                <span id="heading_rec_cnt" style="color: #0c0c0cab;">0</span>
                                <a id="export_visit_reports" href="" download="visit-reports.csv" class="nopropagate ml-1 d-none">
                                    <img src="images/dnld.png" alt="Export visit reports list as CSV" width="22" height="22">
                                </a>
                            </h4>
                        </div>
                        <div class="col-4 text-right">
                            <div class="row btns-user-add" style="float:right;">
                                <a class="btn btn-primary toggle-search rounded" href="javascript:void(0);">
                                    <img src="images/search-plus.png" class="custom-button fa-search-plus" alt="Search">
                                    <img src="images/search-minus.png" class="custom-button fa-search-minus" alt="Search">
                                </a>
                                <?php if ($this->body_template_data['can_add'] === true) { ?>
                                <a href="visit-reports.php#mode=addrec" class="btn btn-success record-add-button rounded" id="add-record-button">
                                    <img src="images/plus.png" class="custom-button-small" alt="Plus">
                                    <span class="hide_in_mobile">Add Visit Report</span>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-search d-none pb-2">
                    <?php include CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'visit-reports-search-form-basic.tpl'; ?>
                </div>
                <div class="responsive-block-table for-cat">
                    <div class="panel-body table-responsive">
                        <table id="recs-list" class="table table-striped table-bordered table-hover" style="width: auto;">
                            <thead class="thead">
                            <tr>
                                <th><span style="width:70px; display:block;">Action</span></th>
                                <th class='sortable' id="colheader_visit-date">
                                    <div style="width:95px;">
										<span class="pull-left">Visit Date</span>
										<i class='fa fa-sort pull-right'></i>
									</div>
								</th>
                                <th class='' id="colheader_type">
                                    <div style="width:60px;">
										<span class="pull-left">Type</span>

									</div>
                                </th>
                                <th class='sortable' id="colheader_customer-group">
									<div style="width:85px;">	
										<span class="pull-left">Group</span>
										<i class='fa fa-sort pull-right'></i>
									</div>
                                </th>
                                <th class='sortable' id="colheader_customer-name">
                                    <div style="width:200px;">	
										<span class="pull-left">Customer</span>
										<i class='fa fa-sort pull-right'></i>
									</div>
                                </th>
                                <th class='' id="colheader_department">
									<div style="width:110px;">	
										<span class="pull-left">Department</span>
									</div>	
                                </th>
                                <th class='' id="colheader_meeting-title">
                                    <div style="width:250px;">	
									<span class="pull-left">Meeting Title</span>

									</div>
                                </th>
                                <th class='' id="colheader_meeting-title" width="200">
                                    <div style="width:200px;">	
									<span class="pull-left">Attachment</span>

									</div>
                                </th>
                                <?php if (true || $this->body_template_data['user_role'] === 'ADMIN') { ?>
                                <th class='' id="colheader_created-by-name">
                                    <div style="width:150px;">	
									<span class="pull-left">Salesperson</span>

									</div>
                                </th>
                                <?php } ?>
                                <th class='sortable' id="colheader_created-on" width="200">
                                    <div style="width:200px;">
                                        <span class="pull-left">Submitted On</span>
                                        <i class='fa fa-sort pull-right'></i>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody id='userlistbox'></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 mt-3 mb-2 d-none" id='rec_detail_add_edit_container'>
        <?php require_once CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'visit-reports-add.tpl'; ?>
    </div>

    <div class="col-12 mt-3 mb-2 d-none" id='rec_detail_view_container'>
        <?php require_once CONST_THEMES_TEMPLATE_INCLUDE_PATH . 'visit-reports-view.tpl'; ?>
    </div>
</div>
<?php
}
?>