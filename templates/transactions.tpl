<?php
if($this->body_template_data['mode'] == 'getList'){

	$mode_index=$this->body_template_data['mode'];
	$this->body_template_data[$mode_index]['records'];

	if($this->body_template_data[$mode_index]['filtertext']!=''){
			$filtertext='Filter';

			if($this->body_template_data[$mode_index]['filtercount']>1){
				$filtertext='Filters';
			}
		echo '<tr>
				<td align=\'center\' class="label-yellow padding-10 filter-row" colspan="9" id="rearch_data">',$this->body_template_data[$mode_index]['filtertext'],'  <a class="clear-filter" onclick="transactionfuncs.clearSearch();">Clear Filter</a>
				</td>
			</tr>';
	}

	if($this->body_template_data[$mode_index]['records_count']==0){
		echo "<tr><td colspan='9' class='text-danger' align='center'>No transactions found.</td></tr>";

	}else{
		for($i_ul=0; $i_ul<$this->body_template_data[$mode_index]['records_count']; $i_ul++){
			$rec = $this->body_template_data[$mode_index]['records'][$i_ul];

			$type_class = $rec['transaction_type'] == 'BUY' ? 'text-success' : 'text-danger';

			echo "<tr>";
			echo "<td>";
			if($this->body_template_data['can_edit'] === true) {
				echo "<a href='javascript:void(0);' class='btn btn-sm btn-primary' onclick='transactionfuncs.editRecord(" . $rec['transaction_id'] . ")' title='Edit'><i class='fa fa-edit'></i></a> ";
			}
			if($this->body_template_data['can_delete'] === true) {
				echo "<a href='javascript:void(0);' class='btn btn-sm btn-danger' onclick='transactionfuncs.deleteRecord(" . $rec['transaction_id'] . ")' title='Delete'><i class='fa fa-trash'></i></a>";
			}
			echo "</td>";
			echo "<td>" . date('d-M-Y', strtotime($rec['transaction_date'])) . "</td>";
			echo "<td>" . htmlspecialchars($rec['portfolio_name']) . "</td>";
			echo "<td>" . htmlspecialchars($rec['stock_code']) . "</td>";
			echo "<td>" . htmlspecialchars($rec['stock_name']) . "</td>";
			echo "<td class='{$type_class}'><strong>" . $rec['transaction_type'] . "</strong></td>";
			echo "<td class='text-right'>" . number_format($rec['quantity'], 2) . "</td>";
			echo "<td class='text-right'>₹" . number_format($rec['price'], 2) . "</td>";
			echo "<td class='text-right'>₹" . number_format($rec['transaction_value'], 2) . "</td>";
			echo "</tr>";
		}

		if($this->body_template_data[$mode_index]['pagination_html']!=''){
			echo "<tr><td colspan='9' class='pagination-row'>\n";
				echo $this->body_template_data[$mode_index]['pagination_html'];
			echo "</td></tr>\n";
		}
	}
}else{

?>
<style>
.filter-section {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>

<div class="row">
    <div id='rec_list_container' class="col-12 mt-3 mb-2">
		<div class="card">
        <div class="card-body">
        	<div class="card-header-heading">
        	<div class="row">
                <div class="col-8"><h4 class="row pg_heading_line_ht">Transactions&nbsp;<span id="heading_rec_cnt" style="color: #0c0c0cab;">0</span></h4></div>
                <div class="col-4 text-right">
                	<div class="row btns-user-add" style="float:right;">
	                	<a class="btn btn-primary toggle-filters rounded" href="javascript:void(0);">
							<i class="fa fa-filter"></i> Filters
						</a>
						<a class="btn btn-success rounded ml-2" href="javascript:void(0);" onclick="transactionfuncs.exportToCSV()">
							<i class="fa fa-download"></i> Export CSV
						</a>
					</div>
				</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section d-none" id="filter-section">
			<div class="row">
				<div class="col-md-3">
					<label>Portfolio</label>
					<select class="form-control" id="filter-portfolio">
						<option value="">All Portfolios</option>
					</select>
				</div>
				<div class="col-md-2">
					<label>Transaction Type</label>
					<select class="form-control" id="filter-type">
						<option value="">All Types</option>
						<option value="BUY">BUY</option>
						<option value="SELL">SELL</option>
					</select>
				</div>
				<div class="col-md-2">
					<label>Start Date</label>
					<input type="date" class="form-control" id="filter-start-date">
				</div>
				<div class="col-md-2">
					<label>End Date</label>
					<input type="date" class="form-control" id="filter-end-date">
				</div>
				<div class="col-md-3">
					<label>&nbsp;</label><br>
					<button class="btn btn-primary" onclick="transactionfuncs.applyFilters()">Apply</button>
					<button class="btn btn-secondary" onclick="transactionfuncs.clearFilters()">Clear</button>
				</div>
			</div>
		</div>

		<div class="responsive-block-table for-cat">
		<div class="panel-body table-responsive">
			<table id="recs-list" class="table table-striped table-bordered table-hover" style="width: auto;">
				<thead class="thead">
					<tr>
						<th width="100"><span>Action</span></th>
						<th class='sortable' id="colheader_transaction_date" width="120"><span class="pull-left">Date</span><i class='fa fa-sort pull-right'></i></th>
						<th class='sortable' id="colheader_portfolio_name" width="150"><span class="pull-left">Portfolio</span><i class='fa fa-sort pull-right'></i></th>
						<th class='sortable' id="colheader_stock_code" width="100"><span class="pull-left">Stock Code</span><i class='fa fa-sort pull-right'></i></th>
						<th class='sortable' id="colheader_stock_name" width="200"><span class="pull-left">Stock Name</span><i class='fa fa-sort pull-right'></i></th>
						<th class='sortable' id="colheader_transaction_type" width="80"><span class="pull-left">Type</span><i class='fa fa-sort pull-right'></i></th>
						<th class='text-right' width="100"><span>Quantity</span></th>
						<th class='text-right' width="120"><span>Price</span></th>
						<th class='text-right' width="150"><span>Transaction Value</span></th>
					</tr>
				</thead>
				<tbody id='table_body'>
					<tr>
						<td colspan="9" style="text-align:center;" >Loading...</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
		</div>
        </div>
    </div>
</div>

<script src="js/transactions.js"></script>

<?php } ?>
