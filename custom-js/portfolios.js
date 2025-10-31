var portfoliofuncs={
	searchparams:[],  /* [{searchon:'',searchtype:'',searchtext:''},{},..] */
	sortparams:[],  /* [{sorton:'',sortorder:''},{},..] */
	default_sort:{sorton:'portfolio_name',sortorder:'ASC'},
	paginationdata:{},
	defaultleadtabtext:'Portfolios',
	filtersapplied:[],
	statuschangestarted:0,
	ajax_data_script:'portfolios.php',
	curr_page_hash:'',
	prev_page_hash:'',
	list_page_hash:'',
	name_pattern: /^[A-Z0-9_ -]+$/i,

	init: function(opt={}){
		$('.main-content').on(common_js_funcs.click_event,'td.clickable-cell',{self:portfoliofuncs},common_js_funcs.changeLocationWithDataProperty);
		$('.main-content').on(common_js_funcs.click_event,'.page-link',{self:portfoliofuncs},portfoliofuncs.changePage);
		$('.main-content').on(common_js_funcs.click_event,'.toggle-search',{self:portfoliofuncs},portfoliofuncs.toggleSearch);

		if(CAN_DELETE) // attach delete handler only if the user has the delete right
			$('.main-content').on(common_js_funcs.click_event,'.record-delete-button',{self:portfoliofuncs},portfoliofuncs.deletePortfolio);

		$('#recs-list>thead>tr>th.sortable').bind(common_js_funcs.click_event,{self:portfoliofuncs},portfoliofuncs.sortTable);

		$('#rec_list_container').on(common_js_funcs.click_event,'.searched_elem .remove_filter',portfoliofuncs.clearSearch);

		$(window).hashchange(portfoliofuncs.onHashChange);
		$(window).hashchange();
	},

	toggleSearch: function(ev){
		let elem = $(ev.currentTarget);
		portfoliofuncs.setPanelVisibilityStatus('portfolio_search_toggle', elem.hasClass('search-form-visible')?'':'visible'); // set closed status for the search panel
		portfoliofuncs.showHidePanel('portfolio_search_toggle');
	},


	setPanelVisibilityStatus: function(panel, status){
		if (typeof(Storage) !== "undefined") {
			localStorage[panel] = status;
		} else {
			Cookies.set(panel, status, {path : '/'});
		}
	},

	showHidePanel: function(panel){
		if(panel === 'portfolio_search_toggle'){
			let show_srch_form = false;
			if (typeof(Storage) !== "undefined") {
				srch_frm_visible = localStorage.portfolio_search_toggle;
			} else {
				srch_frm_visible = Cookies.get('portfolio_search_toggle');
			}
			if(srch_frm_visible && srch_frm_visible == 'visible')
				show_srch_form = true;

			$('.toggle-search').toggleClass('search-form-visible', show_srch_form);
			$('#search_records').closest('.panel-search').toggleClass('d-none', !show_srch_form);
			let search_form_cont = $('#search_records').closest('.panel-search');
			if(search_form_cont.hasClass('d-none'))
				$('.toggle-search').prop('title','Open search panel');
			else{
				$('.toggle-search').prop('title','Close search panel');
				$("#search-field_portfolio_name").focus();
			}
		}
	},

	getList:function(options){
		var self=this;
		var pno=1;
		var params=[];
		if('pno' in options){
			params.push('pno='+encodeURIComponent(options.pno));
		}else{
			params.push('pno=1');
		}

		params.push('searchdata='+encodeURIComponent(JSON.stringify(self.searchparams)));
		params.push('sortdata='+encodeURIComponent(JSON.stringify(self.sortparams)));

		params.push('ref='+Math.random());

		$("#common-processing-overlay").removeClass('d-none');

		location.hash=params.join('&');


	},


	portfolio_count:0,
	showList:function(resp,otherparams){
		var self=portfoliofuncs;
		var listhtml=resp[1].list;
		self.portfolio_count=resp[1]['reccount'];
		$("#rec_list_container").removeClass('d-none');
		$("#rec_detail_add_edit_container").addClass('d-none');
		$("#common-processing-overlay").addClass('d-none');
		$("#portfoliolistbox").html(listhtml);

		if(resp[1].tot_rec_cnt>0){
			$('#heading_rec_cnt').text((resp[1]['reccount']==resp[1]['tot_rec_cnt'])?`(${resp[1]['tot_rec_cnt']})`:`(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`);

		}else{
			$('#heading_rec_cnt').text('(0)');

		}

		$("#add-record-button").removeClass('d-none');
		$("#refresh-list-button").removeClass('d-none');
		$(".back-to-list-button").addClass('d-none').attr('href',"portfolios.php#"+portfoliofuncs.curr_page_hash);
		$("#edit-record-button").addClass('d-none');
		self.paginationdata=resp[1].paginationdata;

		self.setSortOrderIcon();


	},


	onListRefresh:function(resp,otherparams){
		var self=portfoliofuncs;
		$("#common-processing-overlay").addClass('d-none');
		var listhtml=resp[1].list;
		$("#portfoliolistbox").html(listhtml);
		self.paginationdata=resp[1].paginationdata;
		self.setSortOrderIcon();
	},

	resetSearchParamsObj:function(){
		var self=portfoliofuncs;
		self.searchparams=[];
	},

	setSearchParams:function(obj){
		var self=portfoliofuncs;
		self.searchparams.push(obj);

	},

	clearSearch:function(e){
		let remove_all = true;
		if(e){
			e.stopPropagation();
			elem = e.currentTarget;
			if($(elem).hasClass('remove_filter')){
				remove_all = $(elem).data('fld');
				$(elem).parent('.searched_elem').remove();
				$('.panel-search .srchfld[data-fld='+remove_all+']').val('');
			}
		}

		var self=portfoliofuncs;
		if(remove_all===true){
			self.resetSearchParamsObj();
			document.search_form.reset();
		}else{
			self.searchparams = self.searchparams.filter(fltr=>{
				return fltr.searchon !== remove_all;
			});
		}
		var options={pno:1};
		self.getList(options);
		return false;
	},


	doSearch:function(){

		portfoliofuncs.resetSearchParamsObj();
		let fld = '';
		$('.panel-search .srchfld').each(function(i, el){
			let val = $.trim($(el).val());
			if(val!=''){
				fld = $(el).data('fld');
				portfoliofuncs.setSearchParams({searchon:$(el).data('fld'),searchtype:$(el).data('type'),searchtext:val});
			}
		});

		if(portfoliofuncs.searchparams.length<=0)
			return false;

		var options={pno:1};
		portfoliofuncs.getList(options);
		return false;
	},


	changePage:function(ev){
		ev.preventDefault();
		if(!$(ev.currentTarget).parent().hasClass('disabled')){
			var self=portfoliofuncs;
			var pno=$(ev.currentTarget).data('page');
			self.getList({pno:pno});
		}

	},


	sortTable:function(e){
		var self=e.data.self;

		var elemid=e.currentTarget.id;
		var elemidparts=elemid.split('_');
		var sorton=elemidparts[1].replace('-','_');
		var sortorder='ASC';

		if($(e.currentTarget).find("i:eq(0)").hasClass('fa-sort-up')){
			sortorder='DESC';
		}

		var pno = 1;

		portfoliofuncs.sortparams=[];
		portfoliofuncs.sortparams.push({sorton:sorton, sortorder:sortorder});
		var options={pno:pno};
		portfoliofuncs.getList(options);

	},


	setSortOrderIcon:function(){
		var self=portfoliofuncs;
		if(self.sortparams.length>0){
			var sorton = self.sortparams[0].sorton.replace('_','-');
			var colheaderelemid='colheader_'+sorton;

			if(self.sortparams[0].sortorder=='DESC'){
				var sort_order_class='fa-sort-down';
			}else{
				var sort_order_class='fa-sort-up';
			}
			$("#"+colheaderelemid).siblings('th.sortable').removeClass('sorted-col').find('i:eq(0)').removeClass('fa-sort-down fa-sort-up').addClass('fa-sort').end().end().addClass('sorted-col').find('i:eq(0)').removeClass('fa-sort-down fa-sort-up').addClass(sort_order_class);


		}
	},

	openRecordForEditing:function(recordid){
		var self=portfoliofuncs;
		if(recordid=='')
			return false;

		document.addrecform.reset();
		$(".form-control").removeClass("error-field");
		$("#record-save-button").removeClass('d-none').attr('disabled', false);
		$("#common-processing-overlay").removeClass('d-none');
		$("#record-add-cancel-button").attr('href',"portfolios.php#"+portfoliofuncs.prev_page_hash);
		$('#msgFrm').removeClass('d-none');
		var coming_from='';
		var options={mode:'editrecord',recordid:recordid,leadtabtext:'Edit Portfolio',coming_from:coming_from}
		self.openRecord(options);
		return false;

	},


	openRecord:function(options){
		var self=portfoliofuncs;
		var opts={leadtabtext:'Portfolio Details'};
		$.extend(true,opts,options);

		var params={mode:"getRecordDetails",recordid:opts.recordid};
		var options={cache:'no-cache',async:true,type:'post',dataType:'json',url:self.ajax_data_script,params:params,successResponseHandler:self.showDetailsWindow,successResponseHandlerParams:{self:self,mode:opts.mode,recordid:opts.recordid,coming_from:opts.coming_from,header_bar_text:opts.leadtabtext}};
		common_js_funcs.callServer(options);

	},


	showDetailsWindow:function(resp,otherparams){
		const self=otherparams.self;
		let container_id='';
		$("#common-processing-overlay").addClass('d-none');
		const rec_id= resp[1].record_details.portfolio_id ??'';

		if(otherparams.mode=='editrecord'){
			var coming_from=otherparams.coming_from;

			if(rec_id!=''){

				if(resp[1].can_edit===false){
					// User is not authorised to edit this record so send him back to the previous screen
					location.hash=portfoliofuncs.prev_page_hash;
					return;
				}

				portfoliofuncs.removeEditRestrictions();

				let portfolio_name = resp[1].record_details.portfolio_name || '';
				let portfolio_type = resp[1].record_details.portfolio_type || '';
				let description = resp[1].record_details.description || '';
				let status = resp[1].record_details.status || '';


				var contobj=$("#rec_detail_add_edit_container");

				$('.alert-danger').addClass('d-none').find('.alert-message').html('');
				$('#msgFrm').removeClass('d-none');
				contobj.find(".form-actions").removeClass('d-none');

				contobj.find("form[name=addrecform]:eq(0)").data('mode','edit-rec').find('input[name=status]').prop('checked',false).end().get(0).reset();

				$('.addonly').addClass('d-none');
				$('.editonly').removeClass('d-none');
				$('#add_form_field_portfolio_name, #add_form_field_portfolio_type, #add_form_field_description').attr('disabled', true);
				$('input[name=status]').attr('disabled', false);

				contobj.find("#add_edit_mode").val('updaterec');
				contobj.find("#add_edit_recordid").val(rec_id);
				contobj.find("input[name=status]").prop('checked', false);
				if(status!='')
					contobj.find("#edit_form_field_status_"+status.toLowerCase()).prop('checked', true);
				contobj.find("#add_form_field_portfolio_name").val(portfolio_name);
				contobj.find("#add_form_field_portfolio_type").val(portfolio_type);
				contobj.find("#add_form_field_description").val(description);

				let header_text = 'Edit Portfolio';

				contobj.find("#record-add-cancel-button").data('back-to',coming_from);
				contobj.find("#record-save-button>span:eq(0)").html('Save Changes');
				contobj.find("#panel-heading-text").text(header_text);
				portfoliofuncs.setheaderBarText(header_text);

				container_id='rec_detail_add_edit_container';


			}else{

				var message="Sorry, the edit window could not be opened (Server error).";
				if(resp[0]==1){
					message="Sorry, the edit window could not be opened (Portfolio ID missing).";
				}else if(resp[0]==2){
					message="Sorry, the edit window could not be opened (Server error).";
				}else if(resp[0]==3){
					message="Sorry, the edit window could not be opened (Invalid portfolio ID).";
				}

				alert(message);
				location.hash=portfoliofuncs.prev_page_hash;
				return;

			}

		}

		if(container_id!=''){
			$(".back-to-list-button").removeClass('d-none');
			$("#refresh-list-button").addClass('d-none');
			$("#add-record-button").addClass('d-none');
			$("#rec_list_container").addClass('d-none');

			if(container_id!='rec_detail_add_edit_container'){
				$("#rec_detail_add_edit_container").addClass('d-none');
				$("#edit-record-button").removeClass('d-none').data('recid',otherparams.recordid);
			}

			$("#"+container_id).removeClass('d-none');
			self.setheaderBarText(otherparams.header_bar_text);

		}

	},

	removeEditRestrictions: function(){
		const contobj=$("#rec_detail_add_edit_container");
		contobj.find("#add_form_field_portfolio_name, #add_form_field_portfolio_type, #add_form_field_description, input[name=status]").prop('disabled', false).end();
		contobj.find('.rstrctedt').removeClass('rstrctedt');
	},

	handleAddRecResponse:function(resp){
		var self=portfoliofuncs;
		$(".form-control").removeClass("error-field");

		if(resp.error_code==0){
			var message_container = '.alert-success';
			$("#record-add-cancel-button>i:eq(0)").next('span').html('Close');
			$("form[name=addrecform]").find(".error-field").removeClass('error-field').end().get(0).reset();
			$("#add_form_field_portfolio_name").focus();

			document.querySelector('.main-content').scrollIntoView(true);
		}else if(resp.error_code==2){
			var message_container ='';
			if(resp.error_fields.length>0){
				var msg = resp.message;
				alert(msg);
				$(resp.error_fields[0]).focus();
				$(resp.error_fields[0]).addClass("error-field");
			}

		}else{
			var message_container = '.alert-danger';
		}

		$('#record-save-button, #record-add-cancel-button').removeClass('disabled').attr('disabled',false);
		$("#common-processing-overlay").addClass('d-none');

		if(message_container!=''){
			$(message_container).removeClass('d-none').siblings('.alert').addClass('d-none').end().find('.alert-message').html(resp.message);
			var page_scroll='.main-container-inner';
			common_js_funcs.scrollTo($(page_scroll));
			$('#msgFrm').addClass('d-none');
		}
	},

	handleUpdateRecResponse:function(resp){
		var self=portfoliofuncs;

		var mode_container='rec_detail_add_edit_container';
		$(".form-control").removeClass("error-field");

		if(resp.error_code==0){

			var message_container = '.alert-success';

			$("#add_form_field_portfolio_name").focus();
		}else if(resp.error_code==2){
			// data validation errors

			var message_container ='';

			if(resp.error_fields.length>0){
				alert(resp.message);
				setTimeout(()=>{$(resp.error_fields[0]).addClass("error-field").focus(); },0);

			}

		}else{
			var message_container = '.alert-danger';
		}

		$('#record-save-button, #record-add-cancel-button').removeClass('disabled').attr('disabled',false);
		$("#common-processing-overlay").addClass('d-none');
		if(message_container!=''){
			$(message_container).removeClass('d-none').siblings('.alert').addClass('d-none').end().find('.alert-message').html(resp.message);
			var page_scroll='.main-container-inner';
			common_js_funcs.scrollTo($(page_scroll));
			$('#msgFrm').addClass('d-none');
		}

	},

	saveRecDetails:function(formelem){

		var self=portfoliofuncs;
		var data_mode=$(formelem).data('mode');

		var res = self.validateRecDetails({mode:data_mode});
		if(res.error_fields.length>0){

			alert(res.errors[0]);
			setTimeout(function(){
				$(res.error_fields[0],'#addrecform').focus();
			},0);
			return false;

		}

		$("#common-processing-overlay").removeClass('d-none');
		$('#record-save-button, #record-add-cancel-button').addClass('disabled').attr('disabled',true);
		$('#rec_detail_add_edit_container .error-field').removeClass('error-field');

		return true;

	},


	validateRecDetails:function(opts){
		var errors = [], error_fields=[];
		let mode='add-rec';
		$(".form-control").removeClass("error-field");
		if(typeof opts=='object' && opts.hasOwnProperty('mode'))
			mode=opts.mode;

		const frm = $('#addrecform');
		let portfolio_name = '', portfolio_type = '', status = '';

		portfolio_name=$.trim($('#add_form_field_portfolio_name').val());
		portfolio_type=$.trim($('#add_form_field_portfolio_type').val());

		if(mode=='edit-rec'){
			status = frm.find('input[name=status]:checked').val();
		}

		if(portfolio_name == ''){
			errors.push('Portfolio name is required.');
			error_fields.push('#add_form_field_portfolio_name');
			$('#add_form_field_portfolio_name').addClass("error-field");

		}else if(portfolio_type==''){
			errors.push('Portfolio type is required.');
			error_fields.push('#add_form_field_portfolio_type');
			$("#add_form_field_portfolio_type").addClass("error-field");

		}else if(mode=='edit-rec' && status==''){
			errors.push('Please select a status for the portfolio.');
			error_fields.push('#edit_form_field_status_active');
			$("#edit_form_field_status_active").addClass("error-field");

		}

		return {'errors': errors, 'error_fields': error_fields};

	},

	openAddForm:function(e){
		if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
			var self=e.data.self;
		}else{
			var self=portfoliofuncs;
		}
		document.addrecform.reset();

		portfoliofuncs.removeEditRestrictions();

		$(".form-control").removeClass("error-field");
		$("#refresh-list-button").addClass('d-none');
		$("#add-record-button").addClass('d-none');
		$("#edit-record-button").addClass('d-none');
		$("#rec_list_container").addClass('d-none');
		$("#rec_detail_add_edit_container").removeClass('d-none').find("#panel-heading-text").text('Add Portfolio').end();
		$('#msgFrm').removeClass('d-none');

		$(".back-to-list-button").removeClass('d-none');

		$("#rec_detail_add_edit_container").find("#record-save-button>span:eq(0)").html('Add Portfolio').end().find("#add_edit_mode").val('createrec').end().find("#add_edit_recordid").val('').end().find("#record-add-cancel-button").data('back-to','').attr('href',"portfolios.php#"+portfoliofuncs.prev_page_hash);
		$("form[name=addrecform]").data('mode','add-rec').find(".error-field").removeClass('error-field').end().find('input[name=status]').prop('checked',false).end().get(0).reset();

		self.setheaderBarText("");
		$('.addonly').removeClass('d-none');
		$('.editonly').addClass('d-none');
		$('#add_form_field_portfolio_name, #add_form_field_portfolio_type, #add_form_field_description').attr('disabled', false);
		$('input[name=status]').attr('disabled', true);
		$('#add_form_field_portfolio_name').focus();

		document.querySelector('.main-content').scrollIntoView(true);
		return false;

	},

	deletePortfolio:function(ev){
		ev.preventDefault();
		ev.stopPropagation();
		const elem = $(ev.currentTarget);
		let id = elem.data('recid');
		let portfolioname = elem.data('portfolioname');

		if(confirm(`Really delete the portfolio "${portfolioname}" ?`)){

			let rec_details = {};
			common_js_funcs.callServer({cache:'no-cache',async:false,dataType:'json',type:'post',url:portfoliofuncs.ajax_data_script,params:{mode:'deleterec', recordid:id},
				successResponseHandler:function(resp,status,xhrobj){
					if(resp.error_code == 0)
						portfoliofuncs.handleDeleteResp(resp);
					else
						alert(resp.message);
				},
				successResponseHandlerParams:{}});
			return rec_details;
		}

	},
	handleDeleteResp:function(resp){
		alert(resp.message);
		portfoliofuncs.refreshList();
	},

	refreshList:function(e){
		if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
			var self=e.data.self;
		}else{
			var self=portfoliofuncs;
		}

		var currpage=self.paginationdata.curr_page;

		var options={pno:currpage,successResponseHandler:self.onListRefresh};
		self.getList(options);
		return false;

	},

	closeAddForm:function(){
		location.hash=portfoliofuncs.prev_page_hash;
	},

	setheaderBarText:function(text){
		// Set header bar text if needed
	},

	onHashChange: function(e){
		let hshparsed = common_js_funcs.parseHashParams();
		let pno = hshparsed.pno||1;
		let mode = hshparsed.mode||'';
		let recid = hshparsed.recid||'';
		let ref = hshparsed.ref || '';
		let searchdata = hshparsed.searchdata || '';
		let sortdata = hshparsed.sortdata || '';
		let filterstext='',filterscount=0,searchparams=[];

		portfoliofuncs.curr_page_hash = location.hash.substring(1);

		if(mode=='' && recid==''){
			portfoliofuncs.list_page_hash = portfoliofuncs.curr_page_hash;
		}

		if(ref!=''){
			portfoliofuncs.prev_page_hash = portfoliofuncs.curr_page_hash;
		}

		if(mode=='addrec'){
			portfoliofuncs.openAddForm();
			return;
		}

		if(mode=='edit' && recid!=''){
			portfoliofuncs.openRecordForEditing(recid);
			return;
		}

		if(searchdata!=''){
			searchparams = JSON.parse(searchdata);
			portfoliofuncs.searchparams = searchparams;
			for(let i=0;i<searchparams.length;i++){
				if(searchparams[i].searchtext!=''){
					filterstext+='<span class="searched_elem" ><b>'+searchparams[i].searchon+'</b>: '+searchparams[i].searchtext+' <a href="javascript:void(0);" class="remove_filter" data-fld="'+searchparams[i].searchon+'" >x</a></span>';
					filterscount++;
				}
			}
		}

		if(sortdata!=''){
			portfoliofuncs.sortparams = JSON.parse(sortdata);
		}else{
			portfoliofuncs.sortparams=[];
			portfoliofuncs.sortparams.push(portfoliofuncs.default_sort);
		}

		let otherparams = {};
		otherparams.filterstext = filterstext;
		otherparams.filterscount = filterscount;

		let options={cache:'no-cache',async:true,type:'post',dataType:'json',url:portfoliofuncs.ajax_data_script,params:{mode:'getList',pno:pno,searchdata:searchdata,sortdata:sortdata},successResponseHandler:portfoliofuncs.showList,successResponseHandlerParams:otherparams};
		common_js_funcs.callServer(options);

	}

};

// Initialize on document ready
$(document).ready(function(){
	portfoliofuncs.init();
});
