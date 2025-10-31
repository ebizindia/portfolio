var usersfuncs={
	searchparams:[],  /* [{searchon:'',searchtype:'',searchtext:''},{},..] */
	sortparams:[],  /* [{sorton:'',sortorder:''},{},..] */
	default_sort:{sorton:'name',sortorder:'ASC'},
	paginationdata:{},
	defaultleadtabtext:'Members List',
	filtersapplied:[],
	statuschangestarted:0,
	ajax_data_script:'users.php',
	curr_page_hash:'',
	prev_page_hash:'',
	email_pattern:/^([^\@;:"'#()=,&]+|"[^\;:"',&]+")@(\w+([\.-]?\w+)*(\.\w{2,})+|[\[]\d{0,3}(\.\d{0,3}){3}[\]])$/,
	mobile_pattern: /^[+]?\d{8,15}$/,
	name_pattern: /^[.A-Z -]+$/i,
	salutaions:[],
	user_roles:[],
	all_user_roles:[],
	user_groups:[],
	deps_list:[],
	user_levels:{},
	dep_rowno_max:-1,
	pp_max_filesize:0,
	cities:[],
	countries:[],
	states:[],
	
	titleChanged:el=>{
		switch($(el).val().toLowerCase()){
			case 'mr':$('#add_form_field_gender_M').prop('checked', true);break;
			case 'ms':$('#add_form_field_gender_F').prop('checked', true);break;
		}
	},

	initiateStatusChange:function(statuscell){
		var self=usersfuncs;

		var currtext=$(statuscell).find(':nth-child(1)').html();
		if($(statuscell).find(':nth-child(1)').hasClass('status-live')){
			var temptext='Deactivate';
			var color='#ff3333'; // red
		}else{
			var temptext='Activate';
			var color='#00a650'; // green
		}

		$(statuscell).find(':nth-child(1)').html(temptext);
		$(statuscell).find(':nth-child(1)').css('color',color);


	},
	toggleSearch: function(ev){
		let elem = $(ev.currentTarget);
		if(elem.hasClass('search-form-visible')){
			usersfuncs.setPanelVisibilityStatus('user_search_toggle', ''); // set closed status for the search panel
		}else{
			usersfuncs.setPanelVisibilityStatus('user_search_toggle', 'visible'); // set visible status for the search panel
			usersfuncs.setPanelVisibilityStatus('user_sort_toggle', ''); // set closed status for sort panel
		}

		usersfuncs.showHidePanel('user_search_toggle');
		usersfuncs.showHidePanel('user_sort_toggle');

	},

	toggleSortPanel: function(ev){
		let elem = $(ev.currentTarget);
		if(elem.hasClass('sort-form-visible')){
			usersfuncs.setPanelVisibilityStatus('user_sort_toggle', ''); // set closed status for the sort panel
		}else{
			usersfuncs.setPanelVisibilityStatus('user_sort_toggle', 'visible'); // set visible status for the sort panel
			usersfuncs.setPanelVisibilityStatus('user_search_toggle', ''); // set closed status for search panel
		}

		usersfuncs.showHidePanel('user_sort_toggle');
		usersfuncs.showHidePanel('user_search_toggle');

	},

	setPanelVisibilityStatus: function(panel, status){
		if (typeof(Storage) !== "undefined") {
			localStorage[panel] = status;
		} else {
			Cookies.set(panel, status, {path : '/'});
		}
	},

	showHidePanel: function(panel){
		if(panel === 'user_search_toggle'){
			let show_srch_form = false;
			if (typeof(Storage) !== "undefined") {
				srch_frm_visible = localStorage.user_search_toggle;
			} else {
				srch_frm_visible = Cookies.get('user_search_toggle');
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
				setTimeout(()=>{
					$('#search-field_name').focus();
				}, 100);
			}
		}else if(panel === 'user_sort_toggle'){
			let show_sort_form = false;
			if (typeof(Storage) !== "undefined") {
				sort_frm_visible = localStorage.user_sort_toggle;
			} else {
				sort_frm_visible = Cookies.get('user_sort_toggle');
			}
			if(sort_frm_visible && sort_frm_visible == 'visible')
				show_sort_form = true;

			$('.toggle-sort').toggleClass('sort-form-visible', show_sort_form);
			$('#sort_records').closest('.panel-sort').toggleClass('d-none', !show_sort_form);
			let sort_form_cont = $('#sort_records').closest('.panel-sort');
			if(sort_form_cont.hasClass('d-none'))
				$('.toggle-sort').prop('title','Open sorting panel');
			else{
				$('.toggle-sort').prop('title','Close sorting panel');
				setTimeout(()=>{
					$('#sorton').focus();
				}, 100);
			}
		}
	},

	confirmAndExecuteStatusChange:function(statuscell){
		var self=usersfuncs;

		self.statuschangestarted=1;
		var text=$(statuscell).find(':nth-child(1)').html();
		if($(statuscell).find(':nth-child(1)').hasClass('status-live')){
			var newstatus=0;
			var newstatustext='deactivate';
		}else{
			var newstatus=1;
			var newstatustext='activate';
		}

		var rowelem=$(statuscell).parent();
		var rowid=rowelem.attr('id');
		var temp=rowid.split('_');
		var userid=temp[temp.length-1];

		var fullname=rowelem.find('td:eq(1)').html();
		if(confirm("Really "+newstatustext+" the user \""+fullname+"\"?")){
			var options={cache:'no-cache',dataType:'json',async:true,type:'post',url:usersfuncs.ajax_data_script+"?mode=changeStatus",data:"newstatus="+newstatus+"&recordid="+userid,successResponseHandler:usersfuncs.handleStatusChangeResponse,successResponseHandlerParams:{statuscell:statuscell,rowelem:rowelem}};
			common_js_funcs.callServer(options);
			$(statuscell).removeClass("status-grn");
			$(statuscell).removeClass("status-red");
			if(parseInt(newstatus)==1){
				$(statuscell).addClass("status-grn");
			}else{
				$(statuscell).addClass("status-red");
			}
		}else{
			usersfuncs.statuschangestarted=0;
			usersfuncs.abortStatusChange(statuscell);

		}
		

	},

	abortStatusChange:function(statuscell){
		var self=usersfuncs;

		if(self.statuschangestarted==0){
			$(statuscell).find(':nth-child(1)').css('color','');
			if($(statuscell).find(':nth-child(1)').hasClass('status-live')){
				var temptext='Active';

			}else{
				var temptext='Inactive';

			}
			$(statuscell).find(':nth-child(1)').html(temptext);
		}
	},


	handleStatusChangeResponse:function(resp,otherparams){
		var self=usersfuncs;

		self.statuschangestarted=0;
		if(resp.errorcode!=0){

			self.abortStatusChange(otherparams.statuscell);
			if(resp.errorcode == 5)
				alert(resp.errormsg)
			else
				alert("Sorry, the status could not be updated.");

		}else{
			if($(otherparams.statuscell).find(':nth-child(1)').hasClass('status-live')){
				$(otherparams.statuscell).find(':nth-child(1)').removeClass('status-live').addClass("status-notlive");
			}else{
				$(otherparams.statuscell).find(':nth-child(1)').removeClass('status-notlive').addClass("status-live");
			}
			otherparams.rowelem.toggleClass('inactiverow');
			self.abortStatusChange(otherparams.statuscell);
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


	user_count:0,
	showList:function(resp,otherparams){
		//console.log(resp);
		var self=usersfuncs;
		var listhtml=resp[1].list;
		self.user_count=resp[1]['reccount'];
		$("#user_list_container").removeClass('d-none');
		$("#user_detail_view_container").addClass('d-none');
		$("#user_detail_add_edit_container").addClass('d-none');
		$("#common-processing-overlay").addClass('d-none');
		// $('#search_field').select2({minimumResultsForSearch: -1});

		// Hide the search panel in mobile view
		if(resp[1].reccount>0 && $('#menu-toggle').is(':visible')){
			usersfuncs.setPanelVisibilityStatus('user_search_toggle', '');
			usersfuncs.showHidePanel('user_search_toggle');
		}


		$("#userlistbox").html(listhtml);
		
		if(resp[1].tot_rec_cnt>0){
			$('#heading_rec_cnt').text((resp[1]['reccount']==resp[1]['tot_rec_cnt'])?`(${resp[1]['tot_rec_cnt']})`:`(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`);
			usersfuncs.setExportLink(resp[1]['reccount']>0?true:false);
		}else{
			$('#heading_rec_cnt').text('(0)');
			usersfuncs.setExportLink(false);
		}

		$("#add-record-button").removeClass('d-none');
		$("#refresh-list-button").removeClass('d-none');
		$(".back-to-list-button").addClass('d-none').attr('href',"users.php#"+usersfuncs.curr_page_hash);
		$("#edit-record-button").addClass('d-none');
		self.paginationdata=resp[1].paginationdata;

		self.setSortOrderIcon();


	},


	onListRefresh:function(resp,otherparams){
		var self=usersfuncs;
		$("#common-processing-overlay").addClass('d-none');
		var listhtml=resp[1].list;
		$("#userlistbox").html(listhtml);
		self.paginationdata=resp[1].paginationdata;
		self.setSortOrderIcon();
	},

	setExportLink: function(show){
		const dnld_elem = $('#export_members');
		if(dnld_elem.length<=0) // the download link element does not exist, the user might not be in ADMIN role
			return;
		let url = '#';
		if(show===true){
			let params = [];
			params.push('mode=export');
			params.push('searchdata='+encodeURIComponent(JSON.stringify(this.searchparams)));
			params.push('sortdata='+encodeURIComponent(JSON.stringify(this.sortparams)));
			params.push('ref='+Math.random());
			url = `${window.location.origin}${window.location.pathname}?${params.join('&')}`;
			
		}
		dnld_elem.attr('href',url).toggleClass('d-none', show!==true);
	},


	expandFilterBox:function(){
		var self=usersfuncs;
		document.leadsearchform.reset();
		for(var i=0; i<self.searchparams.length; i++){
			switch(self.searchparams[i].searchon){
				case 'name': $("#fullname").val(self.searchparams[i].searchtext[0]); break;

				case 'email': $("#email").val(self.searchparams[i].searchtext[0]); break;

				case 'usertype': $("#usertype").val(self.searchparams[i].searchtext[0]); break;


			}

		}
		$("#searchbox").show();
		$("#applyfilter").hide();

	},


	collapseFilterBox:function(){
		var self=usersfuncs;
		$("#searchbox").hide();
		if($("#filterstatus").is(':hidden')){
			$("#applyfilter").show();
			$("#filterstatus").hide();
		}else{
			$("#filterstatus").show();
			$("#applyfilter").hide();

		}
		return false;
	},

	onDateFilterChange:function(elem){
		var date_filtertype=$(elem).val();
		if(date_filtertype=='EQUAL'){
			$("#enddateboxcont").hide();
			$("#enddate").val('');
			$("#startdate").val('')
			$("#startdateboxcont").show();

		}else if(date_filtertype=='BETWEEN'){
			$("#enddateboxcont").show();
			$("#enddate").val('');
			$("#startdate").val('')
			$("#startdateboxcont").show();
		}else{
			$("#enddate").val('');
			$("#startdate").val('')
			$("#enddateboxcont").hide();
			$("#startdateboxcont").hide();
		}

	},


	resetSearchParamsObj:function(){
		var self=usersfuncs;
		self.searchparams=[];
	},

	setSearchParams:function(obj){
		var self=usersfuncs;
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
				if(remove_all==='joining_dt')
					$("#search-field_joinedafterdt_picker").datepicker('setDate', null);
				else
					$('.panel-search .srchfld[data-fld='+remove_all+']').val('');
			}
		}

		var self=usersfuncs;
		// self.filtersapplied=[]; // remove the filter bar messages
		if(remove_all===true){
			self.resetSearchParamsObj();
			document.search_form.reset();
			$("#search-field_joinedafterdt_picker").datepicker('setDate', null);
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

		usersfuncs.resetSearchParamsObj();
		$('.panel-search .srchfld').each(function(i, el){
			let val = $.trim($(el).val());
			
			let display_text = '';
			if($(el).data('fld')=='sector_id' || $(el).data('fld')=='is_active' || $(el).data('fld')=='user_group_id')
				display_text = $(el).find('option:selected').text();
			if(val!=''){
				usersfuncs.setSearchParams({searchon:$(el).data('fld'),searchtype:$(el).data('type'),searchtext:val, disp_text:display_text});
			}
		});

		var options={pno:1};
		usersfuncs.getList(options);
		return false;
	},


	changePage:function(ev){
		ev.preventDefault();
		if(!$(ev.currentTarget).parent().hasClass('disabled')){
			var self=usersfuncs;
			var pno=$(ev.currentTarget).data('page');
			self.getList({pno:pno});
			// return false;
		}

	},



	sortTable:function(e){
		var self=e.data.self;

		var elemid=e.currentTarget.id;
		var elemidparts=elemid.split('_');
		var sorton=elemidparts[1].replace(/-/g,'_');
		var sortorder='ASC';

		if(sorton == 'usertype')
			sorton = 'user_type';

		if($(e.currentTarget).find("i:eq(0)").hasClass('fa-sort-up')){
			sortorder='DESC';
		}

		var pno = 1;
		if(self.sortparams[0].sorton==sorton){
			if(self.paginationdata.curr_page!='undefined' && self.paginationdata.curr_page>1){
				pno = self.paginationdata.curr_page;
			}
		}

		usersfuncs.sortparams=[];
		usersfuncs.sortparams.push({sorton:sorton, sortorder:sortorder});
		var options={pno:pno};
		usersfuncs.getList(options);

	},



	setSortOrderIcon:function(){
		var self=usersfuncs;
		if(self.sortparams.length>0){
			var sorton = self.sortparams[0].sorton == 'user_type'?'usertype':self.sortparams[0].sorton.replace(/_/g,'-');
			var colheaderelemid='colheader_'+sorton;

			if(self.sortparams[0].sortorder=='DESC'){
				var sort_order_class='fa-sort-down';
			}else{
				var sort_order_class='fa-sort-up';
			}
			$("#"+colheaderelemid).siblings('th.sortable').removeClass('sorted-col').find('i:eq(0)').removeClass('fa-sort-down fa-sort-up').addClass('fa-sort').end().end().addClass('sorted-col').find('i:eq(0)').removeClass('fa-sort-down fa-sort-up').addClass(sort_order_class);


		}
	},

	doSort: function(){
		let sorton = $('#orderlist-sorton').val();
		let sortorder = $('input[name=sortorder]:checked').val();
		usersfuncs.sortparams=[];
		usersfuncs.sortparams.push({sorton:sorton, sortorder:sortorder});
		let pno = 1;
		let options={pno: pno};
		usersfuncs.getList(options);
		return false;
	},

	openRecordForViewing:function(recordid){
		var self=usersfuncs;
		if(recordid=='')
			return false;

		$("#record-save-button").addClass('d-none').attr('disabled', 'disabled');
		$("#common-processing-overlay").removeClass('d-none');
		var coming_from='';
		var options={mode:'viewrecord',recordid:recordid,loadingmsg:"Opening the lead '"+recordid+"' for viewing...",leadtabtext:'View Member Details',coming_from:coming_from}
		self.openRecord(options);
		 return false;

	},

	openRecordForEditing:function(recordid){
		var self=usersfuncs;
		if(recordid=='')
			return false;

		document.adduserform.reset();
		$(".form-control").removeClass("error-field");
		$("#record-save-button").removeClass('d-none').attr('disabled', false);
		$("#common-processing-overlay").removeClass('d-none');
		$("#record-add-cancel-button").attr('href',"users.php#"+usersfuncs.prev_page_hash);
		$('#msgFrm').removeClass('d-none');
		var coming_from='';//elem.data('in-mode');
		var options={mode:'editrecord',recordid:recordid,leadtabtext:'Edit Member\'s Details',coming_from:coming_from}
		self.openRecord(options);
		return false;

	},


	openRecord:function(options){
		var self=usersfuncs;
		var opts={leadtabtext:'Lead Details'};
		$.extend(true,opts,options);

		usersfuncs.dep_rowno_max=-1;

		var params={mode:"getRecordDetails",recordid:opts.recordid};
		var options={cache:'no-cache',async:true,type:'post',dataType:'json',url:self.ajax_data_script,params:params,successResponseHandler:self.showLeadDetailsWindow,successResponseHandlerParams:{self:self,mode:opts.mode,recordid:opts.recordid,coming_from:opts.coming_from,header_bar_text:opts.leadtabtext}};
		common_js_funcs.callServer(options);

	},


	showLeadDetailsWindow:function(resp,otherparams){
		const self=otherparams.self;
		let container_id='';
		$("#common-processing-overlay").addClass('d-none');
		const user_id= resp[1].record_details.id ??''; // member table's id
		const login_acnt_id = resp[1].record_details.user_acnt_id ??''; // users table's id

		if(!resp[1].allow_detail_view){
			location.hash=usersfuncs.prev_page_hash;
			return;
		}

		if(otherparams.mode=='editrecord'){
			var coming_from=otherparams.coming_from;

			if(user_id!=''){

				if(resp[1].can_edit===false){
					// User is not authorised to edit this record so send him back to the previous screen
					location.hash=usersfuncs.prev_page_hash;
					return;
				}

				usersfuncs.removeEditRestrictions();
				usersfuncs.populateUserGroupDropdown();

				let title = resp[1].record_details.title || '';
				let name = resp[1].record_details.name || '';
				let email = resp[1].record_details.email || '';
				let mobile = resp[1].record_details.mobile || '';
				let wa_num = mobile;
				if(wa_num!=''){
					if(!/^[+0]/.test(wa_num))
						wa_num = `+${country_code}${wa_num}`;
				}
				let mobile2 = resp[1].record_details.mobile2 || '';
				let gender = resp[1].record_details.gender || '';
				let user_group_id = resp[1].record_details.user_group_id || '';
				let profile_pic = resp[1].record_details.profile_pic || '';
				let profile_pic_url = resp[1].record_details.profile_pic_url || '';
				let designation = resp[1].record_details.designation || '';
				let role = resp[1].record_details.assigned_roles[0]['role'] || '';
				let status = resp[1].record_details.active ?? '';
				let remarks = resp[1].record_details.remarks ?? '';

				var contobj=$("#user_detail_add_edit_container");

				$('.alert-danger').addClass('d-none').find('.alert-message').html('');
				$('#msgFrm').removeClass('d-none');
				contobj.find(".form-actions").removeClass('d-none');

				contobj.find("form[name=adduserform]:eq(0)").data('mode','edit-user').find('input[name=status]').attr('checked',false).end().get(0).reset();
				contobj.find("#add_edit_mode").val('updateUser');
				contobj.find("#add_edit_recordid").val(user_id);
				contobj.find("#add_form_field_title").val(title);
				contobj.find("#add_form_field_name").val(name);
				contobj.find("#add_form_field_email").val(email).siblings('.email-icon-form-input').data('url',`mailto:${email}`).toggleClass('d-none', email=='');
				contobj.find("#add_form_field_mobile").val(mobile).siblings('.wa-icon-form-input').data('url',`https://wa.me/${wa_num}`).toggleClass('d-none', wa_num=='').end().siblings('.tel-icon-form-input').data('url',`tel:${wa_num}`).toggleClass('d-none', wa_num=='');
				contobj.find("#add_form_field_mobile2").val(mobile2);
				contobj.find("input[name=gender]").prop('checked', false);
				if(gender!=='')
					contobj.find("#add_form_field_gender_"+gender).prop('checked', true);
				contobj.find("#add_form_field_user_group_id").val(user_group_id);
				contobj.find("#add_form_field_profilepic").val('');
				contobj.find("#profile_pic_img").attr('src', profile_pic_url).css('opacity','').end().find('.profile_image').removeClass('d-none').end().find('.profile_image >.remove_image').toggleClass('d-none', profile_pic=='').find('#remove_profile_pic').removeClass('d-none').end().find('#undo_remove_profile_pic').addClass('d-none').end().end().find('#img_del_marked_msg').addClass('d-none');
				contobj.find('#delete_profile_pic').val('0');

				if(profile_pic!='' && resp[1].record_details.profile_pic_org_width < resp[1].record_details.profile_pic_max_width)
					contobj.find("#profile_pic_img").attr('width', resp[1].record_details.profile_pic_org_width);
				else
					contobj.find("#profile_pic_img").attr('width','');

				contobj.find("#add_form_field_designation").val(designation);
				contobj.find("#add_form_field_role_"+role).prop('checked', true);
				contobj.find("#add_form_field_password").val('');
				contobj.find("#add_form_field_status_"+status).prop('checked', true);
				contobj.find("#add_form_field_remarks").val(remarks);

				contobj.find("#default_pwd_msg").addClass('d-none');

				let header_text = 'Edit User';
				if(resp[1].cuid == login_acnt_id){ // cuid has the users table's id
					contobj.find("#add_form_field_status_"+status).parents('div.form-group').hide().end().end();
					header_text = 'Edit Your Profile';
				}else{
					contobj.find("#add_form_field_status_"+status).parents('div.form-group').show().end().end();
				}

				contobj.find("#record-add-cancel-button").data('back-to',coming_from);
				contobj.find("#record-save-button>span:eq(0)").html('Save Changes');
				contobj.find("#add_password_msg").addClass('d-none');
				contobj.find("#edit_password_msg").removeClass('d-none');
				contobj.find("#pswd_field_mandatory_marker").addClass('d-none');
				contobj.find("#panel-heading-text").text(header_text);
				usersfuncs.setheaderBarText(header_text);

				usersfuncs.applyEditRestrictions(resp[1].edit_restricted_fields);
				container_id='user_detail_add_edit_container';
				setTimeout(()=>{
					$("#add_form_field_title").focus();
				},100);

			}else{
				var message="Sorry, the edit window could not be opened (Server error).";
				if(resp[0]==1){
					message="Sorry, the edit window could not be opened (User ID missing).";
				}else if(resp[0]==2){
					message="Sorry, the edit window could not be opened (Server error).";
				}else if(resp[0]==3){
					message="Sorry, the edit window could not be opened (Invalid user ID).";
				}

				alert(message);
				location.hash=usersfuncs.prev_page_hash;
				return;
			}
		}

		if(container_id!=''){
			$(".back-to-list-button").removeClass('d-none');
			$("#refresh-list-button").addClass('d-none');
			$("#add-record-button").addClass('d-none');
			$("#user_list_container").addClass('d-none');

			if(container_id!='user_detail_add_edit_container'){
				$("#user_detail_add_edit_container").addClass('d-none');
				$("#edit-record-button").removeClass('d-none').data('recid',otherparams.recordid);
			}else if(container_id!='user_detail_view_container'){
				$("#user_detail_view_container").addClass('d-none');
				$("#edit-record-button").addClass('d-none');
			}

			$("#"+container_id).removeClass('d-none');
			self.setheaderBarText(otherparams.header_bar_text);
		}
	},

	applyEditRestrictions: function(restricted_fields){
		const contobj=$("#user_detail_add_edit_container");
		restricted_fields.forEach(fld=>{
			switch(fld){
				case 'role':
					contobj.find("input[name=role]").prop('disabled', restricted_fields.includes('role')).addClass('rstrctedt');
					break;
				case 'status':
					contobj.find("input[name=status]").prop('disabled', restricted_fields.includes('status')).addClass('rstrctedt');
					break;
				case 'user_group_id':
					contobj.find("#add_form_field_user_group_id").prop('disabled', restricted_fields.includes('user_group_id')).addClass('rstrctedt');
					break;
				case 'profile_pic':
					contobj.find("#add_form_field_profilepic").prop('disabled', restricted_fields.includes('profile_pic'));
					contobj.find(".profile_image").addClass('d-none');
					contobj.find("#remove_profile_pic_selection").addClass('d-none');
					break;
				case 'remarks':
					contobj.find("#add_form_field_remarks").prop('disabled', restricted_fields.includes('remarks')).addClass('rstrctedt');
					break;
			}
		});
	},

	removeEditRestrictions: function(){
		const contobj=$("#user_detail_add_edit_container");
		contobj.find("input[name=role], input[name=status], #add_form_field_user_group_id, #add_form_field_profilepic, #add_form_field_remarks").prop('disabled', false).end()
			.find(".profile_image, #remove_profile_pic_selection").removeClass('d-none').end();
		contobj.find('.rstrctedt').removeClass('rstrctedt');
	},


	initializeRolesSelector:function(){
		var contobj=$("#user_detail_add_edit_container");
		if(contobj.find("#add_form_field_role").hasClass('select2-hidden-accessible'))
			contobj.find("#add_form_field_role").select2('destroy');
		contobj.find("#add_form_field_role").select2({
			minLength:0,
			tags:true,
			tokenSeparators: [','],
			width:'200px',
			placeholder:'Select/add one or more roles...',
			data:usersfuncs.user_roles,

			multiple: true

		});

	},

	backToList:function(e){
		
	},


	refreshList:function(e){
		if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
			var self=e.data.self;
		}else{
			var self=usersfuncs;
		}

		var currpage=self.paginationdata.curr_page;

		var options={pno:currpage,successResponseHandler:self.onListRefresh};
		self.getList(options);
		return false;

	},

	handleAddUserResponse:function(resp){
		var self=usersfuncs;
		$(".form-control").removeClass("error-field");

		if(resp.error_code==0){
			var message_container = '.alert-success';
			$("#record-add-cancel-button>i:eq(0)").next('span').html('Close');
			$("form[name=adduserform]").find(".error-field").removeClass('error-field').end().get(0).reset();
			$("#add_form_field_title").val('');
			$("#add_form_field_role_REGULAR").prop('checked',true);
			$("#add_form_field_status_y").prop('checked',true).parents('div.form-group').show();
			$('#img_del_marked_msg').addClass('d-none');
			$('#profile_pic_img').attr('src',""); // empty the profile image src
			$('#add_form_field_profilepic').val(''); // empty the profile pic file input
			//$("#add_form_field_dob_picker").datepicker('setDate', null);
			//$("#add_form_field_annv_picker").datepicker('setDate', null);
			$('input[name=gender]').prop('checked', false);
			$('#add_form_field_gender_M').prop('checked', true);
			document.querySelector('.main-content').scrollIntoView(true);
			setTimeout(()=>{
				$("#add_form_field_title").focus();
			},0);
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

	handleUpdateUserResponse:function(resp){
		var self=usersfuncs;

		var mode_container='user_detail_add_edit_container';
		$(".form-control").removeClass("error-field");

		if(resp.error_code==0){
			var back_to_mode=$("#record-add-cancel-button").data('back-to');
			mode_container=(back_to_mode!='list-mode')?'user_detail_add_edit_container':'user_list_container';
			var message_container = '.alert-success';

			
			let email = $("#add_form_field_email").val().trim();
			let mobile = $("#add_form_field_mobile").val().trim();
			mobile = (resp.other_data.mobile!='')?resp.other_data.mobile:mobile;
			$("#add_form_field_mobile").val(mobile);
			let mailto = `mailto:${email}`;
			let tel = '';
			let wame = '';
			if(mobile!=''){
				if(!/^[+0]/.test(mobile))
					mobile = `+${country_code}${mobile}`;
				wame = `https://wa.me/${mobile}`;	
				tel = `tel:${mobile}`;
			}
			
			$("#add_form_field_email").siblings('.email-icon-form-input').data('url',mailto).toggleClass('d-none',mailto=='');
			$("#add_form_field_mobile").siblings('.wa-icon-form-input').data('url',wame).toggleClass('d-none', wame=='').end().siblings('.tel-icon-form-input').data('url',tel).toggleClass('d-none', tel=='');

			// Update the profile image if required
			if(resp.other_data.profile_pic_deleted && resp.other_data.profile_pic_deleted==1){
				$('#delete_profile_pic').val('0');
				$('#user_detail_add_edit_container .profile_image .remove_image').addClass('d-none');
				$('#profile_pic_img').attr('src', resp.other_data.placeholder_image).css('opacity','');
			}else if(resp.other_data.profile_pic_url && resp.other_data.profile_pic_url!=''){
				if(resp.other_data.profile_pic_org_width < resp.other_data.profile_pic_max_width)
					$('#profile_pic_img').attr({width:resp.other_data.profile_pic_org_width, src:resp.other_data.profile_pic_url}).css('opacity','');
				else
					$('#profile_pic_img').attr({width:'', src:resp.other_data.profile_pic_url}).css('opacity','');
				$('#user_detail_add_edit_container .profile_image .remove_image').removeClass('d-none').find('#remove_profile_pic').removeClass('d-none').end().find('#undo_remove_profile_pic').addClass('d-none').end();
			}else if(resp.other_data.placeholder_image && resp.other_data.placeholder_image!=''){
				$('#profile_pic_img').attr('src', resp.other_data.placeholder_image).css('opacity','');
			}
			$('#img_del_marked_msg').addClass('d-none');
			$('#delete_profile_pic').val('0');
			$('#add_form_field_profilepic, #add_form_field_password').val('');
			if(resp.other_data.recordid==resp.other_data.loggedin_user_id){
				// The user has edited his own profile
				$('.user-info>.user-name').text(resp.other_data.profile_details.name);
			}

			setTimeout(()=>{
				$("#add_form_field_title").focus();
			},0);
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
			$(message_container).removeClass('d-none').siblings('.alert').addClass('d-none').end().find('.alert-message').html(resp.message);//.end().delay(3000).fadeOut(800,function(){$(this).css('display','').addClass('d-none');});
			var page_scroll='.main-container-inner';
			common_js_funcs.scrollTo($(page_scroll));
			$('#msgFrm').addClass('d-none');
		}

	},

	saveUserDetails:function(formelem){

		var self=usersfuncs;
		var data_mode=$(formelem).data('mode');

		var res = self.validateUserDetails({mode:data_mode});
		if(res.error_fields.length>0){

			alert(res.errors[0]);
			setTimeout(function(){
				$(res.error_fields[0],'#adduserform').focus();
			},0);
			return false;

		}

		$("#common-processing-overlay").removeClass('d-none');
		$('#record-save-button, #record-add-cancel-button').addClass('disabled').attr('disabled',true);
		$('#user_detail_add_edit_container .error-field').removeClass('error-field');

		return true;

	},


	validateUserDetails:function(opts){
		var errors = [], error_fields=[];
		let mode='add-user';
		$(".form-control").removeClass("error-field");
		if(typeof opts=='object' && opts.hasOwnProperty('mode'))
			mode=opts.mode;

		const frm = $('#adduserform');
		let title=$.trim(frm.find('#add_form_field_title').val()).replace(/(<([^>]+)>)/ig,"");
		let name=$.trim(frm.find('#add_form_field_name').val()).replace(/(<([^>]+)>)/ig,"");
		let email=$.trim(frm.find('#add_form_field_email').val());
		let mobile=$.trim(frm.find('#add_form_field_mobile').val());
		let mobile2=$.trim(frm.find('#add_form_field_mobile2').val());
		let gender=$.trim(frm.find('input[name=gender]:checked').val());
		let user_group_id=$.trim(frm.find('#add_form_field_user_group_id').val());
		let role="regular";
		let user_status =frm.find('input[name=status]:checked').val();
		let pswd =frm.find('#add_form_field_password').val().trim();
		let remarks =frm.find('#add_form_field_remarks:not(:disabled)').val() || '';

		if(!frm.find('#add_form_field_title').hasClass('rstrctedt') && title == ''){
			errors.push('Salutation is required.');
			error_fields.push('#add_form_field_title');
			$("#add_form_field_title").addClass("error-field");

		}else if(!frm.find('#add_form_field_title').hasClass('rstrctedt') && usersfuncs.salutaions.indexOf(title)==-1){
			errors.push('Salutation should be one of these: '+usersfuncs.salutaions.join(', '));
			error_fields.push('#add_form_field_title');
			$("#add_form_field_title").addClass("error-field");

		}else if(!frm.find('#add_form_field_name').hasClass('rstrctedt') && name == ''){
			errors.push('Name is required.');
			error_fields.push('#add_form_field_name');
			$("#add_form_field_name").addClass("error-field");

		}else if(!usersfuncs.name_pattern.test(name)){
			errors.push('The name has invalid characters.');
			error_fields.push('#add_form_field_name');
			$("#add_form_field_name").addClass("error-field");

		}else if(mobile==''){
			errors.push('The WhatsApp number is required.');
			error_fields.push('#add_form_field_mobile');
			$("#add_form_field_mobile").addClass("error-field");
		}else if(!usersfuncs.mobile_pattern.test(mobile)){
			errors.push('The WhatsApp number is not valid.');
			error_fields.push('#add_form_field_mobile');
			$("#add_form_field_mobile").addClass("error-field");
		}else if(mobile2!='' && !usersfuncs.mobile_pattern.test(mobile2)){
			errors.push('The alternate mobile number is not valid.');
			error_fields.push('#add_form_field_mobile2');
			$("#add_form_field_mobile2").addClass("error-field");
		}else if(gender==''){
			errors.push('The gender is required.');
			error_fields.push('#add_form_field_gender_M');
			$("#add_form_field_gender_M").addClass("error-field");
		}else if(gender!='M' && gender!='F'){
			errors.push('The gender value is invalid.');
			error_fields.push('#add_form_field_gender_M');
			$("#add_form_field_gender_M").addClass("error-field");
		}else if(!frm.find('#add_form_field_user_group_id').hasClass('rstrctedt') && user_group_id==''){
			errors.push('User group is required.');
			error_fields.push('#add_form_field_user_group_id');
			$("#add_form_field_user_group_id").addClass("error-field");
		}else if(!frm.find('input[name=role]').hasClass('rstrctedt') && role==''){
			errors.push('Please select a role for the member.');
			error_fields.push('#add_form_field_role_REGULAR');
			$("#add_form_field_role_REGULAR").addClass("error-field");
		}else if(mode==='add-user' && pswd==''){
			errors.push('Please set a password for the user. Must be at least 6 characters long');
			error_fields.push('#add_form_field_password');
			$("#add_form_field_password").addClass("error-field");
		}else if(pswd!=='' && pswd.length<6){
			errors.push('Password must be at least 6 characters long');
			error_fields.push('#add_form_field_password');
			$("#add_form_field_password").addClass("error-field");
		}

		return {'errors': errors, 'error_fields': error_fields};
	},

	openAddUserForm:function(e){
		if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
			var self=e.data.self;
		}else{
			var self=usersfuncs;
		}
		document.adduserform.reset();

		usersfuncs.removeEditRestrictions();
		usersfuncs.populateUserGroupDropdown();

		usersfuncs.dep_rowno_max=-1;
		$(".form-control").removeClass("error-field");
		$("#refresh-list-button").addClass('d-none');
		$("#add-record-button").addClass('d-none');
		$("#edit-record-button").addClass('d-none');
		$("#user_list_container").addClass('d-none');
		$("#user_detail_view_container").addClass('d-none');
		$("#user_detail_add_edit_container").removeClass('d-none').find("#panel-heading-text").text('Add User').end();
		$('#msgFrm').removeClass('d-none');

		$(".back-to-list-button").removeClass('d-none');
		$("#add_password_msg").removeClass('d-none');
		$("#edit_password_msg").addClass('d-none');
		$("#pswd_field_mandatory_marker").removeClass('d-none');

		$("#user_detail_add_edit_container").find("#record-save-button>span:eq(0)").html('Add User').end().find("#add_edit_mode").val('createUser').end().find("#add_edit_recordid").val('').end().find("#add_edit_usertype").val('').end().find("#record-add-cancel-button").data('back-to','').attr('href',"users.php#"+usersfuncs.prev_page_hash);
		$("#record-save-button").removeClass('d-none').attr('disabled', false);
		$("form[name=adduserform]").data('mode','add-user').find(".error-field").removeClass('error-field').end().find('input[name=status]').attr('checked',false).end().get(0).reset();

		$("form[name=adduserform]").find("#add_form_field_title").val('');

		$("#add_form_field_role_ADMIN").prop('checked',false);
		$("#add_form_field_role_REGULAR").prop('checked',true);
		$("#add_form_field_status_n").prop('checked',false);
		$("#add_form_field_status_y").prop('checked',true).parents('div.form-group').show();

		$("#add_form_field_email").val('').siblings('.email-icon-form-input').data('url','').toggleClass('d-none',true);
		$("#add_form_field_mobile").val('').siblings('.wa-icon-form-input').data('url','').toggleClass('d-none', true).end().siblings('.tel-icon-form-input').data('url','').toggleClass('d-none', true);

		$('#user_detail_add_edit_container .profile_image').addClass('d-none'); // hide the profile image viewer section
		$('#delete_profile_pic').val('0');
		$('#img_del_marked_msg').addClass('d-none');
		$('#profile_pic_img').attr('src',""); // empty the profile image src
		$('#add_form_field_profilepic').val(''); // empty the profile pic file input
		$('input[name=gender]').prop('checked', false);
		$('#add_form_field_gender_M').prop('checked', true);

		self.setheaderBarText("");
		$("#default_pwd_msg").removeClass('d-none');

		document.querySelector('.main-content').scrollIntoView(true);
		setTimeout(()=>{
			$("#add_form_field_title").focus();
		},100);
	},

	deleteUser:function(ev){
		var elem = $(ev.currentTarget);
		var id =elem.data('recid');
		if(confirm('Do you want to delete this user?')){

			var rec_details = {};
			common_js_funcs.callServer({cache:'no-cache',async:false,dataType:'json',type:'post',url:usersfuncs.ajax_data_script,params:{mode:'deleteUser', user_id:id},
				successResponseHandler:function(resp,status,xhrobj){
					if(resp.error_code == 0)
						usersfuncs.handleDeleteResp(resp);
					else
						alert(resp.message);
				},
				successResponseHandlerParams:{}});
			return rec_details;
		}

	},
	handleDeleteResp:function(resp){
		// console.log(resp);return false;
		alert(resp.message);
		usersfuncs.refreshList();
	},

	closeAddUserForm:function(){
		var self =this;
		return true;

	},


	setheaderBarText:function(text){
		$("#header-bar-text").find(":first-child").html(text);
		
	},

	removeProfilePicSelection: function(e){
		e.preventDefault();
		e.stopPropagation();
		$('#add_form_field_profilepic').val('').removeClass('error-field');
	},

	markProfilePicForDeletion: function(e){
		e.preventDefault();
		e.stopPropagation();
		$(e.currentTarget).addClass('d-none');
		$('#undo_remove_profile_pic, #img_del_marked_msg').removeClass('d-none');
		$('#profile_pic_img').css('opacity','0.3');
		$('#delete_profile_pic').val('1');
	},

	removeProfilePicDeleteMarker: function(e){
		e.preventDefault();
		e.stopPropagation();
		$(e.currentTarget).addClass('d-none');
		$('#img_del_marked_msg').addClass('d-none');
		$('#remove_profile_pic').removeClass('d-none');
		$('#profile_pic_img').css('opacity','');
		$('#delete_profile_pic').val('0');

	},

	populateUserGroupDropdown: function(){
		const user_group_dropdown = $('#add_form_field_user_group_id');
		const search_user_group_dropdown = $('#search-field_user_group');

		user_group_dropdown.empty().append('<option value="">-- Select user group --</option>');
		search_user_group_dropdown.empty().append('<option value="">-- Any --</option>');

		if(usersfuncs.user_groups && usersfuncs.user_groups.length > 0){
			usersfuncs.user_groups.forEach(group => {
				user_group_dropdown.append(`<option value="${group.id}">${group.text}</option>`);
				search_user_group_dropdown.append(`<option value="${group.id}">${group.text}</option>`);
			});
		}
	},

	onHashChange:function(e){
		var hash=location.hash.replace(/^#/,'');
		if(usersfuncs.curr_page_hash!=usersfuncs.prev_page_hash){
			usersfuncs.prev_page_hash=usersfuncs.curr_page_hash;
		}
		usersfuncs.curr_page_hash=hash;

		var hash_params={mode:''};
		if(hash!=''){
			var hash_params_temp=hash.split('&');
			var hash_params_count= hash_params_temp.length;
			for(var i=0; i<hash_params_count; i++){
				var temp=hash_params_temp[i].split('=');
				hash_params[temp[0]]=decodeURIComponent(temp[1]);
			}
		}else{
			if(default_list_filter && default_list_filter.length>0){
				$('#search-field_status').val(default_list_filter[0]['value']).closest('form').find('.search_button').trigger('click');
				return;
			}
		}

		switch(hash_params.mode.toLowerCase()){
			case 'adduser':
				$('.alert-success, .alert-danger').addClass('d-none');
				$('#msgFrm').removeClass('d-none');
				usersfuncs.openAddUserForm();
				break;

			case 'view':
				// View is not allowed
				$('.alert-success, .alert-danger').addClass('d-none');
				location.hash=usersfuncs.prev_page_hash;
				break;

			case 'edit':
				$('.alert-success, .alert-danger').addClass('d-none');
				$('#msgFrm').removeClass('d-none');
				if(hash_params.hasOwnProperty('recid') && hash_params.recid!=''){
					usersfuncs.openRecordForEditing(hash_params.recid);
				}else{
					location.hash=usersfuncs.prev_page_hash;
				}
				break;

			default:
				$('.alert-success, .alert-danger').addClass('d-none');
				$('#msgFrm').removeClass('d-none');
				var params={mode:'getList',pno:1, searchdata:"[]", sortdata:JSON.stringify(usersfuncs.sortparams), listformat:'html'};

				if(hash_params.hasOwnProperty('pno')){
					params['pno']=hash_params.pno
				}else{
					params['pno']=1;
				}

				if(hash_params.hasOwnProperty('searchdata')){
					params['searchdata']=hash_params.searchdata;
				}

				if(hash_params.hasOwnProperty('sortdata')){
					params['sortdata']=hash_params.sortdata;
				}

				usersfuncs.searchparams=JSON.parse(params['searchdata']);
				usersfuncs.sortparams=JSON.parse(params['sortdata']);

				if(usersfuncs.sortparams.length==0){
					usersfuncs.sortparams.push(usersfuncs.default_sort);
					params['sortdata']=JSON.stringify(usersfuncs.sortparams);
				}

				// Set the selected sort field and order in the sorting panel
				$('#orderlist-sorton').val(usersfuncs.sortparams[0].sorton);
				$('#orderlist_sortorder_'+usersfuncs.sortparams[0].sortorder).attr('checked', true);

				// Set the selected search criteria in the search panel
				if(usersfuncs.searchparams.length>0){
					$.each(usersfuncs.searchparams , function(idx,data) {
						switch (data.searchon) {
							case 'name':
								$("#search-field_name").val(data.searchtext);
								break;
							case 'email':
								$("#search-field_email").val(data.searchtext);
								break;
							case 'mob':
								$("#search-field_mob").val(data.searchtext);
								break;
							case 'user_group_id':
								$("#search-field_user_group").val(data.searchtext);
								break;
							case 'is_active':
								$("#search-field_status").val(data.searchtext);
								break;
						}
					});
				}

				$("#common-processing-overlay").removeClass('d-none');
				common_js_funcs.callServer({cache:'no-cache',async:true,dataType:'json',type:'post', url:usersfuncs.ajax_data_script,params:params,successResponseHandler:usersfuncs.showList,successResponseHandlerParams:{self:usersfuncs}});

				usersfuncs.showHidePanel('user_search_toggle');
				usersfuncs.showHidePanel('user_sort_toggle');
		}
	}

}