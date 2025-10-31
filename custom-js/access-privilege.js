var access_privilege={
	ajax_data_script:'access-privilege.php',
	curr_page_hash:'',
	prev_page_hash:'',
	menu_list:[],
	users_list:[],
	roles_list:[],
	menu_list_index:{},
	users_list_index:{},
	roles_list_index:{},
	entries_made_available:[],
	entries_removed:[],
	criterion_values:[],

	init: async function(){
		$("input[name=privilege_managing_criterion]").bind(common_js_funcs.click_event,access_privilege.handlePrivilegeCriterionClick);
		$("#manage_privilege_for").bind('change',access_privilege.onSelectedCriterionsValueChange);
		$(".main-content").on('click','#available_list_items .privilege_chkbox:not(:disabled)',access_privilege.selectUnselectPrivilege);

		$('.main-content').on(common_js_funcs.click_event,'.item_add_icon',access_privilege.grantAccessTo);
		$('.main-content').on(common_js_funcs.click_event,'.item_remove_icon',access_privilege.revokeAccessTo);

		await access_privilege.getMenuNUsersNRolesLists();

		var default_criterion='U';
		$("#privilege_managing_criterion_"+default_criterion).trigger('click');
	},

	getMenuNUsersNRolesLists:function(){
		var self=this;
		return new Promise(function(resolve){
			var callserver_options={async:true,type:'post',url:access_privilege.ajax_data_script+'?mode=getMenuNUsersNRolesLists',successResponseHandler:function(resp,otherparams){
					self.menu_list=resp.menu.slice();
					self.generateListIndex('menu','menuid');
					self.users_list=resp.users.slice();
					self.generateListIndex('user','id');
					self.roles_list=resp.roles.slice();
					self.generateListIndex('roles','role_id');
					resolve('OK');
				}};
			common_js_funcs.callServer(callserver_options);
		});

	},

	handlePrivilegeCriterionClick:function(ev){

		var criterion_value=$(ev.currentTarget).val();
		$("#available_list_items, #nonavailable_list_items").html('');
		$("#available_nonavailable_lists").addClass('d-none');
		$("#hdn_entries_made_available, #hdn_entries_removed, #hdn_privilege_for").val('');

		if(criterion_value=='U'){
			$("#hdn_criterion_selected").val('for_user');
		}else if(criterion_value=='R'){
			$("#hdn_criterion_selected").val('for_role');
		}else if(criterion_value=='M'){
			$("#hdn_criterion_selected").val('for_menu');
		}

		access_privilege.entries_made_available=[];
		access_privilege.entries_removed=[];

		//$('.alert').prop('class', 'alert d-none');
		$('#page_alert').prop('class', 'alert d-none');
		access_privilege.doOnCriterionSelect(criterion_value);

	},

	doOnCriterionSelect:function(criterion_value){
		access_privilege.setCriterionValuesListLabel(criterion_value);
		access_privilege.populateCriterionValues(criterion_value);
	},

	setCriterionValuesListLabel:function(criterion_value){
		if(criterion_value=='U'){
			$("#manage_privilege_for_label").html('Manage access privileges of &nbsp;');
			$("#available_list>h5:first").text('Can access');
			$("#nonavailable_list>h5:first").text('Cannot access');
		}else if(criterion_value=='R'){
			$("#manage_privilege_for_label").html('Manage access privileges of &nbsp;');
			$("#available_list>h5:first").text('Can access');
			$("#nonavailable_list>h5:first").text('Cannot access');
		}else if(criterion_value=='M'){
			$("#manage_privilege_for_label").html('Manage access to the menu ');
			$("#available_list>h5:first").text('Accessible to');
			$("#nonavailable_list>h5:first").text('Inaccessible to');
		}else{
			$("#manage_privilege_for_label").html('');
		}
	},

	populateCriterionValues:function(criterion_value,selected_value){
		// $("#manage_privilege_for").empty(); // empty the drop down
		// var select_elem=$("#manage_privilege_for").get(0);
		if ($("#manage_privilege_for").data('select2')) {
			$("#manage_privilege_for").select2('destroy');
		}
		var data = [];
		if(criterion_value=='U'){
			// select_elem.options[0]=new Option('','');
			var placeholder='Select a user...';
			data.push({id:'', text:''});
			for(var i=0; i<access_privilege.users_list.length; i++){
				var text=access_privilege.users_list[i].name;
				text=text.replace(/\s+/,' ');
				text=$.trim(text);
				data.push({id:access_privilege.users_list[i].id, text:text});
			}
			$("#manage_privilege_for").empty().select2( {data:data});
			$("#manage_privilege_for").select2({'placeholder':placeholder});
			$("#manage_privilege_for").val('').trigger("change");
		}else if(criterion_value=='R'){


			// select_elem.options[0]=new Option('','');
			var placeholder='Select a role...';
			data.push({id:'', text:''});
			for(var i=0; i<access_privilege.roles_list.length; i++){
				var text=access_privilege.roles_list[i].role_name;
				text=text.replace(/\s+/,' ');
				text=$.trim(text);
				// var value=access_privilege.users_list[i].id;
				// select_elem.options[i+1]=new Option(text,value);
				data.push({id:access_privilege.roles_list[i].role_id, text:text});
			}

			$("#manage_privilege_for").empty().select2( {data:data});
			$("#manage_privilege_for").select2({'placeholder':placeholder});
			$("#manage_privilege_for").val('').trigger("change");

		}else if(criterion_value=='M'){
			// select_elem.options[0]=new Option('Select a menu...','');
			var placeholder='Select a menu...';
			data.push({id:'', text:''});
			for(var i=0, j=0; i<access_privilege.menu_list.length; i++){
				if(access_privilege.menu_list[i].showCatInDisp=='1')
					data[j]={text:access_privilege.menu_list[i].manucategoryname, children:[]};

				for(var k=0; k<access_privilege.menu_list[i]['menus'].length; k++){
					if(access_privilege.menu_list[i]['menus'][k].showMenuInDisplay == 1){
						var text=access_privilege.menu_list[i]['menus'][k].menuname;
						text=$.trim(text);
						// var value=access_privilege.menu_list[i].menuid;
						// select_elem.options[i+1]=new Option(text,value);
						// $(select_elem.options[i+1]).data({'menu_icon':access_privilege.menu_list[i].menu_icon});
						if(access_privilege.menu_list[i].showCatInDisp=='1')
							data[j]['children'].push({id:access_privilege.menu_list[i]['menus'][k].menuid, text:text});
						else
							data[j++]={id:access_privilege.menu_list[i]['menus'][k].menuid, text:text};
					}

				}

			}
			$("#manage_privilege_for").empty().select2( {data:data});
			$("#manage_privilege_for").select2({'placeholder':placeholder});
			$("#manage_privilege_for").val('').trigger("change");

		}else{
			$("input[name=privilege_managing_criterion]").prop('checked',false);
		}


	},

	format:function(dd_option) {
		var original_option=dd_option.element;

		return "<div class=''><div class='access-menu-icon'><i class='"+$(original_option).data('menu_icon')+"'  ></div></i> <span class='menu-text itemtext'>"+ dd_option.text+"</span></div>";
	},

	onSelectedCriterionsValueChange:function(ev){
		var selected_value=$(ev.currentTarget).select2('val');
		var criterion_selected=$("input[name=privilege_managing_criterion]:checked").val();

		access_privilege.entries_made_available=[];
		access_privilege.entries_removed=[];

		$("#available_list_items, #nonavailable_list_items").html('');
		$("#available_nonavailable_lists").addClass('d-none');
		$("#hdn_entries_made_available, #hdn_entries_removed").val('');
		$("#hdn_privilege_for").val(selected_value);
		//$('.alert').prop('class', 'alert d-none');
		$('#page_alert').prop('class', 'alert d-none');

		$("#common-processing-overlay").removeClass('d-none');

		if(selected_value!=''){
			if(criterion_selected=='U'){
				access_privilege.populateMenuListsForThisUser(selected_value);

			}else if(criterion_selected=='R'){
				access_privilege.populateMenuListForThisRole(selected_value);

			}else if(criterion_selected=='M'){
				access_privilege.populateUsersListsForThisMenu(selected_value);

			}
		}

		$("#common-processing-overlay").addClass('d-none');
	},


	cancelProcess:function(){
		access_privilege.entries_made_available=[];
		access_privilege.entries_removed=[];
		$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		$("#available_list_items, #nonavailable_list_items").html('');
		$("#available_nonavailable_lists").addClass('d-none');
		$("#hdn_entries_made_available, #hdn_entries_removed").val('');
		$("#hdn_privilege_for").val('');
		$("#manage_privilege_for").val('').trigger('change');


		//$('.alert').prop('class', 'alert d-none');
		$('#page_alert').prop('class', 'alert d-none');
		var page_scroll='.main-container-inner';
		common_js_funcs.scrollTo($(page_scroll));
	},

	savePrivileges:function(){

		var entries_made_available=access_privilege.entries_made_available.join(',');
		var entries_removed=access_privilege.entries_removed.join(',');

		$("#hdn_entries_made_available").val(entries_made_available);
		$("#hdn_entries_removed").val(entries_removed);


		if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);
			return false;
		}


		$("#common-processing-overlay").removeClass('d-none');

		return true;
	},


	/*savePrivileges:function(){

		var entries_made_available=JSON.stringify(access_privilege.entries_made_available);
		var entries_removed=JSON.stringify(access_privilege.entries_removed);

		$("#hdn_entries_made_available").val(entries_made_available);
		$("#hdn_entries_removed").val(entries_removed);


		// if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
		// 	$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		// 	return false;
		// }


		$("#common-processing-overlay").removeClass('hide');

		return true;
	},*/

	handlePrivilegeSaveResponse:function(resp){
		$("#common-processing-overlay").addClass('d-none');

		$("#hdn_entries_made_available, #hdn_entries_removed").val('');
		if(resp[0]==0){
			// success
			access_privilege.entries_made_available=[];
			access_privilege.entries_removed=[];
			var message="The changes made to the privileges were updated successfully.";
			var message_class = 'alert-success';
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);


		}else{
			var message="Something went wrong. The privileges could not be updated.";
			var message_class = 'alert-danger';

		}
		$('.alert').prop('class', 'alert '+message_class).find('.alert-message').html(message);
		var page_scroll='.main-container-inner';
		common_js_funcs.scrollTo($(page_scroll));

	},

	populateMenuListForThisRole:function(roleid){
		var callserver_options={async:false,type:'post',params:{mode:'getMenuListForRole',role_id:roleid},url:access_privilege.ajax_data_script,successResponseHandler:function(resp,otherparams){

				var assigned_menus=[];
				var unassigned_menus=[];
				var temp_assigned_indexes=[];
				var temp_index='';
				var item={};
				if(resp[0]==0){


					if(resp[1].menuids.length>0){

						for(menuid in access_privilege.menu_list_index.menuid){

							temp_cat_index=access_privilege.menu_list_index.menuid[menuid][0][0];
							temp_menu_index=access_privilege.menu_list_index.menuid[menuid][0][1];
							item=access_privilege.menu_list[temp_cat_index]['menus'][temp_menu_index];
							if($.inArray(parseInt(menuid),resp[1].menuids)>-1){
								assigned_menus.push(item);

							}else{
								unassigned_menus.push(item);
							}

						}


					}else{
						unassigned_menus=access_privilege.menu_list.splice();

					}

					access_privilege.createAvailableItemsList(assigned_menus,'R',{roleid:otherparams.roleid, menu_perms:resp[1].menu_perms});
					access_privilege.createUnavailableItemsList(unassigned_menus,'R',{roleid:otherparams.roleid});

					$("#available_nonavailable_lists").removeClass('d-none');

					$(".items_list").sortable({
						connectWith: '.items_list',
						placeholder: "menu-item-drag-place-holder",
						forcePlaceholderSize: true,
						cursor: 'move',
						items: 'div.item',
						handle: '.item_move_handle',
						revert:true,
						cancel:'.disabled_item',
						receive:access_privilege.onPrivilegeChange
					});


				}




			},

			successResponseHandlerParams:{roleid:roleid}
		};

		common_js_funcs.callServer(callserver_options);

	},




	populateMenuListsForThisUser:function(userid){
		var callserver_options={async:false,type:'post',params:{mode:'getMenuListForUser',userid:userid},url:access_privilege.ajax_data_script,successResponseHandler:function(resp,otherparams){

				var assigned_menus=[];
				var unassigned_menus=[];
				var temp_assigned_indexes=[];
				var temp_index='';
				var item={};
				if(resp[0]==0){


					if(resp[1].menuids.length>0){

						for(menuid in access_privilege.menu_list_index.menuid){

							temp_cat_index=access_privilege.menu_list_index.menuid[menuid][0][0];
							temp_menu_index=access_privilege.menu_list_index.menuid[menuid][0][1];
							item=access_privilege.menu_list[temp_cat_index]['menus'][temp_menu_index];
							if($.inArray(parseInt(menuid),resp[1].menuids)>-1){
								assigned_menus.push(item);

							}else{
								unassigned_menus.push(item);
							}

						}


					}else{
						unassigned_menus=access_privilege.menu_list.splice();

					}

					access_privilege.createAvailableItemsList(assigned_menus,'U',{userid:otherparams.userid, menu_perms:resp[1].menu_perms});
					access_privilege.createUnavailableItemsList(unassigned_menus,'U',{userid:otherparams.userid});

					$("#available_nonavailable_lists").removeClass('d-none');

					$(".items_list").sortable({
						connectWith: '.items_list',
						placeholder: "menu-item-drag-place-holder",
						forcePlaceholderSize: true,
						cursor: 'move',
						items: 'div.item',
						handle: '.item_move_handle',
						revert:true,
						cancel:'.disabled_item',
						receive:access_privilege.onPrivilegeChange
					});


				}




			},

			successResponseHandlerParams:{userid:userid}
		};

		common_js_funcs.callServer(callserver_options);

	},




	populateUsersListsForThisMenu:function(menuid){
		var callserver_options={async:false,type:'post',data:'menuid='+menuid,url:access_privilege.ajax_data_script+'?mode=getUsersListForMenu',successResponseHandler:function(resp,otherparams){

				var assigned_to=[];
				var notassigned_to=[];
				//var temp_assigned_indexes=[];
				var temp_index='';
				var item={};
				if(resp[0]==0){


					if(resp[1].userids.length>0){

						for(userid in access_privilege.users_list_index.id){

							temp_index=access_privilege.users_list_index.id[userid][0];
							item=access_privilege.users_list[temp_index];
							if(resp[1].userids.indexOf(userid)>-1){
								assigned_to.push(item);

							}else{
								notassigned_to.push(item);
							}

						}


					}else{
						notassigned_to=access_privilege.users_list.splice();

					}

					access_privilege.createAvailableItemsList(assigned_to,'U',{menuid:otherparams.menuid});
					access_privilege.createUnavailableItemsList(notassigned_to,'U',{menuid:otherparams.menuid});

					$("#available_nonavailable_lists").removeClass('d-none');

					$(".items_list").sortable({
						connectWith: '.items_list',
						placeholder: "menu-item-drag-place-holder",
						forcePlaceholderSize: true,
						cursor: 'move',
						items: 'div.item',
						handle: '.item_move_handle',
						revert:true,
						cancel:'.disabled_item',
						receive:access_privilege.onPrivilegeChange
					});


				}



			},
			successResponseHandlerParams:{menuid:menuid}
		};

		common_js_funcs.callServer(callserver_options);

	},



	onPrivilegeChange:function(event,ui){

		var item_elem_id=ui.item.prop('id');
		var sender_elem_id=ui.sender.prop('id');
		var type='grant';

		if(sender_elem_id=='available_list_items'){
			type='revoke';
		}

		access_privilege.onMenuGrantRevoke(item_elem_id,type);


		/*
		var item_elem_id_parts=item_elem_id.split('_');
		var item_id=item_elem_id_parts[1];

		var sender_elem_id=ui.sender.prop('id');

		if(sender_elem_id=='available_list_items'){

			var index = access_privilege.entries_made_available.indexOf(item_id);
			if(index!=-1){
				access_privilege.entries_made_available.splice(index, 1);
			}else{

				access_privilege.entries_removed.push(item_id);
			}


		}else if(sender_elem_id=='nonavailable_list_items'){

			var index = access_privilege.entries_removed.indexOf(item_id);
			if(index!=-1){
				access_privilege.entries_removed.splice(index, 1);
			}else{
				access_privilege.entries_made_available.push(item_id);
			}
		}

		if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		}else{
			$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		}	*/


	},


	/*onGrantRevoke:function(item_elem_id,type){

		var item_elem_id_parts=item_elem_id.split('_');
		var item_id=item_elem_id_parts[1];

		if(type=='revoke'){ //old_list_elem_id=='available_list_items'){ // revoke

			var index = access_privilege.entries_made_available.indexOf(item_id);
			if(index!=-1){
				access_privilege.entries_made_available.splice(index, 1);
			}else{

				access_privilege.entries_removed.push(item_id);
			}


		}else if(type=='grant'){ //old_list_elem_id=='nonavailable_list_items'){ // grant

			var index = access_privilege.entries_removed.indexOf(item_id);
			if(index!=-1){
				access_privilege.entries_removed.splice(index, 1);
			}else{
				access_privilege.entries_made_available.push(item_id);
			}
		}

		if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		}else{
			$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		}


	},*/


	/*onMenuGrantRevoke:function(item_elem_id,type){

		var item_elem_id_parts=item_elem_id.split('_');
		var item_id=item_elem_id_parts[1];

		if(type=='revoke'){ //old_list_elem_id=='available_list_items'){ // revoke

			$('#'+item_elem_id).find('.item_privilege_list .privilege_chkbox').each(function(){
				if($(this).is(':checked')){
					$(this).prop({checked:false, disabled:true});
					data1 = $(this).data();

					index=-1;
					if(typeof access_privilege.entries_made_available[data1.id] != 'undefined'){
						index = access_privilege.entries_made_available[data1.id].indexOf(parseInt(data1.menu_perm_id));
						if(index>-1){
							access_privilege.entries_made_available[data1.id].splice(index,1);
							if(access_privilege.entries_made_available[data1.id].length==0)
								delete access_privilege.entries_made_available[data1.id];
						}


					}

					if(index==-1){
						if(typeof access_privilege.entries_removed[data1.id] == 'undefined')
							access_privilege.entries_removed[data1.id] = new Array(0);
						access_privilege.entries_removed[data1.id].push(parseInt(data1.menu_perm_id));
					}



				}else{

					$(this).prop({disabled:true});

				}
			});


		}else if(type=='grant'){

			$('#'+item_elem_id).find('.item_privilege_list .privilege_chkbox').each(function(){
				if(!$(this).is(':checked')){
					$(this).prop({checked:true, disabled:false});
					data1 = $(this).data();

					index=-1;
					if(typeof access_privilege.entries_removed[data1.id] != 'undefined'){
						index = access_privilege.entries_removed[data1.id].indexOf(parseInt(data1.menu_perm_id));
						if(index>-1){
							access_privilege.entries_removed[data1.id].splice(index,1);
							if(access_privilege.entries_removed[data1.id].length==0)
								delete access_privilege.entries_removed[data1.id];
						}


					}

					if(index==-1){
						if(typeof access_privilege.entries_made_available[data1.id] == 'undefined')
							access_privilege.entries_made_available[data1.id] = new Array(0);
						access_privilege.entries_made_available[data1.id].push(parseInt(data1.menu_perm_id));
					}



				}
			});
		}

		// if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
		// 	$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		// }else{
		// 	$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		// }

		access_privilege.activateDeactivateSaveButton();

	},


	selectUnselectPrivilege:function(e){

		var data = $(e.currentTarget).data();
		var data1 = {};
		var index = -1;

		if($(e.currentTarget).is(':checked')){
			// access_privilege.entries_made_available
			index=-1;
			if(typeof access_privilege.entries_removed[data.id] != 'undefined'){
				index = access_privilege.entries_removed[data.id].indexOf(parseInt(data.menu_perm_id));
				if(index>-1){
					access_privilege.entries_removed[data.id].splice(index,1);
					if(access_privilege.entries_removed[data.id].length==0)
						delete access_privilege.entries_removed[data.id];
				}

			}

			if(index==-1){
				if(typeof access_privilege.entries_made_available[data.id] == 'undefined')
					access_privilege.entries_made_available[data.id] = new Array(0);
				access_privilege.entries_made_available[data.id].push(parseInt(data.menu_perm_id));
			}


			if(data.perm == 'ALL'){

				$(e.currentTarget).parents('.item_privilege_list:eq(0)').find('.privilege_chkbox').each(function(){
					if(!$(this).is(':checked')){
						$(this).prop('checked',true);
						data1 = $(this).data();

						index=-1;
						if(typeof access_privilege.entries_removed[data1.id] != 'undefined'){
							index = access_privilege.entries_removed[data1.id].indexOf(parseInt(data1.menu_perm_id));
							if(index>-1){
								access_privilege.entries_removed[data1.id].splice(index,1);
								if(access_privilege.entries_removed[data1.id].length==0)
									delete access_privilege.entries_removed[data1.id];
							}


						}

						if(index==-1){
							if(typeof access_privilege.entries_made_available[data1.id] == 'undefined')
								access_privilege.entries_made_available[data1.id] = new Array(0);
							access_privilege.entries_made_available[data1.id].push(parseInt(data1.menu_perm_id));
						}



					}
				});
			}


		}else{
			// access_privilege.entries_removed
			index=-1;
			if(typeof access_privilege.entries_made_available[data.id] != 'undefined'){
				index = access_privilege.entries_made_available[data.id].indexOf(parseInt(data.menu_perm_id));
				if(index>-1){
					access_privilege.entries_made_available[data.id].splice(index,1);
					if(access_privilege.entries_made_available[data.id].length==0)
						delete access_privilege.entries_made_available[data.id];
				}


			}

			if(index==-1){
				if(typeof access_privilege.entries_removed[data.id] == 'undefined')
					access_privilege.entries_removed[data.id] = new Array(0);
				access_privilege.entries_removed[data.id].push(parseInt(data.menu_perm_id));
			}


		}

		access_privilege.activateDeactivateSaveButton();


	},*/


	onMenuGrantRevoke:function(item_elem_id,type){

		var item_elem_id_parts=item_elem_id.split('_');
		var item_id=item_elem_id_parts[1];

		if(type=='revoke'){ //old_list_elem_id=='available_list_items'){ // revoke

			$('#'+item_elem_id).find('.item_privilege_list .privilege_chkbox').each(function(){
				if($(this).is(':checked')){
					$(this).prop({checked:false, disabled:true});
					data1 = $(this).data();

					index = access_privilege.entries_made_available.indexOf(parseInt(data1.menu_perm_id));
					if(index>-1){
						access_privilege.entries_made_available.splice(index,1);
					}

					if(index==-1){
						access_privilege.entries_removed.push(parseInt(data1.menu_perm_id));
					}



				}else{

					$(this).prop({disabled:true});

				}
			});


		}else if(type=='grant'){

			$('#'+item_elem_id).find('.item_privilege_list .privilege_chkbox').each(function(){
				if(!$(this).is(':checked')){
					$(this).prop({checked:true, disabled:false});
					data1 = $(this).data();

					index=-1;
					if(access_privilege.entries_removed.length>0){
						index = access_privilege.entries_removed.indexOf(parseInt(data1.menu_perm_id));
						if(index>-1){
							access_privilege.entries_removed.splice(index,1);

						}


					}

					if(index==-1){
						access_privilege.entries_made_available.push(parseInt(data1.menu_perm_id));
					}



				}
			});
		}

		// if(access_privilege.entries_made_available.length==0 && access_privilege.entries_removed.length==0){
		// 	$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		// }else{
		// 	$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		// }

		access_privilege.activateDeactivateSaveButton();

	},


	selectUnselectPrivilege:function(e){

		var data = $(e.currentTarget).data();
		var data1 = {};
		var index = -1;

		if($(e.currentTarget).is(':checked')){
			// access_privilege.entries_made_available
			index=-1;
			if(access_privilege.entries_removed.length>0){
				index = access_privilege.entries_removed.indexOf(parseInt(data.menu_perm_id));
				if(index>-1){
					access_privilege.entries_removed.splice(index,1);

				}

			}

			if(index==-1){
				access_privilege.entries_made_available.push(parseInt(data.menu_perm_id));
			}


			if(data.perm == 'ALL'){

				$(e.currentTarget).parents('.item_privilege_list:eq(0)').find('.privilege_chkbox').each(function(){
					if(!$(this).is(':checked')){
						$(this).prop('checked',true);
						data1 = $(this).data();

						index=-1;
						if(access_privilege.entries_removed.length>0){
							index = access_privilege.entries_removed.indexOf(parseInt(data1.menu_perm_id));
							if(index>-1){
								access_privilege.entries_removed.splice(index,1);

							}


						}

						if(index==-1){
							access_privilege.entries_made_available.push(parseInt(data1.menu_perm_id));
						}



					}
				});
			}


		}else{
			// access_privilege.entries_removed
			index=-1;
			if(access_privilege.entries_made_available.length>0){
				index = access_privilege.entries_made_available.indexOf(parseInt(data.menu_perm_id));
				if(index>-1){
					access_privilege.entries_made_available.splice(index,1);

				}


			}

			if(index==-1){
				access_privilege.entries_removed.push(parseInt(data.menu_perm_id));
			}


		}

		access_privilege.activateDeactivateSaveButton();


	},


	activateDeactivateSaveButton:function(){
		var entries_made_available_cnt = entries_removed_cnt = 0;

		entries_made_available_cnt = access_privilege.entries_made_available.length;
		entries_removed_cnt = access_privilege.entries_removed.length;

		if(entries_made_available_cnt==0 && entries_removed_cnt==0){
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		}else{
			$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		}
	},


	/*activateDeactivateSaveButton:function(){
		var entries_made_available_cnt = entries_removed_cnt = 0;

		for(id in access_privilege.entries_made_available)
			entries_made_available_cnt++;
		for(id in access_privilege.entries_removed)
			entries_removed_cnt++;

		if(entries_made_available_cnt==0 && entries_removed_cnt==0){
			$("#save_access_privilege").addClass('disabled').prop('disabled',true);
		}else{
			$("#save_access_privilege").removeClass('disabled').prop('disabled',false);
		}
	},*/


	showHideUserHelpText:function(list_for,name){
		$("p[id^=user_help_text_]").addClass('d-none');

		if(list_for=='U'){
			$("p[id^=user_help_text_1]").removeClass('d-none');
			$("div[id^=user_help_text_3]").removeClass('d-none').html("Manage <strong>"+name+"'s</strong> access privileges.");
		}else if(list_for=='R'){
			$("p[id^=user_help_text_2]").removeClass('d-none');
			if(name == 'Admin')
				$("div[id^=user_help_text_3]").removeClass('d-none').html("Privileges of the '<strong>Admin</strong>' role cannot be altered.");
			else
				$("div[id^=user_help_text_3]").removeClass('d-none').html("Privileges of the role '<strong>"+name+"</strong>' can be altered here.");
		}else{
			$("p[id^=user_help_text_1]").removeClass('d-none');

		}


	},

	createAvailableItemsList:function(items_list, list_for,otherparams){
		var name='';
		var id='';
		var menu_icon='';
		var draggable=1;
		var elem={};
		var roles='';
		var menu_perms = [];

		if(typeof otherparams=='object'){
			if(otherparams.hasOwnProperty('userid') && otherparams.userid!=''){
				var temp_index=access_privilege.users_list_index['id'][otherparams.userid];
				var roles=access_privilege.users_list[temp_index].roles; // get users assigned roles list


			}

			if(otherparams.hasOwnProperty('menuid') && otherparams.menuid!=''){
				var temp_index=access_privilege.menu_list_index['menuid'][otherparams.menuid];
				var menuslug=access_privilege.menu_list[temp_index].menuslug;

			}

		}
		var data =$("#manage_privilege_for").select2('data');
		if(list_for=='U'){
			access_privilege.showHideUserHelpText(list_for,data[0].text);
		}else if(list_for=='R'){
			access_privilege.showHideUserHelpText(list_for,data[0].text);
		}else{
			access_privilege.showHideUserHelpText('','');
		}


		for(var i=0; i<items_list.length; i++){
			if(list_for=='M'){
				name=items_list[i].fname+' '+items_list[i].lname;
				name=name.replace(/\s+/,' ');
				name=$.trim(name);
				name+=" ("+items_list[i].usertype+")";

				id=items_list[i].id;

				if(items_list[i].usertype=='ADMIN' || menuslug=='dashboard' || menuslug=='myprofile'){
					draggable=0;
				}else{
					draggable=1;
				}

			}else if(list_for=='U'){
				name=items_list[i].menuname;
				name=$.trim(name);

				id=items_list[i].menuid;
				menu_icon=items_list[i].menu_icon;
				menu_perms=items_list[i].perms;

				if(parseInt(items_list[i].availableByDefault) == 1  || roles.includes(1)){ // 1 is the id of role 'Admin'
					draggable=0;
				}else{
					draggable=1;
				}

			}else if(list_for=='R'){
				name=items_list[i].menuname;
				name=$.trim(name);

				id=items_list[i].menuid;
				menu_icon=items_list[i].menu_icon;
				menu_perms=items_list[i].perms;

				if(parseInt(items_list[i].availableByDefault) == 1  || otherparams.roleid==1){ // 1 is the id of role 'Admin'

					draggable=0;
				}else{
					draggable=1;
				}

			}

			elem=access_privilege.createItemElem({name:name,id:id,menu_perms:menu_perms,assigned_menu_perms:otherparams.menu_perms[id],menu_icon:menu_icon,draggable:draggable});
			if(list_for=='M'){
				elem.find('.item_move_handle>i').remove();
			}
			$("#available_list_items").append(elem);
		}

	},

	createUnavailableItemsList:function(items_list, list_for,otherparams){
		var name='';
		var id='';
		var menu_icon='';
		var draggable=1;
		var elem={};
		var roles = '';

		if(typeof otherparams=='object'){
			if(otherparams.hasOwnProperty('userid') && otherparams.userid!=''){
				var temp_index=access_privilege.users_list_index['id'][otherparams.userid];
				var roles=access_privilege.users_list[temp_index].roles; // get users assigned roles list

			}

		}


		for(var i=0; i<items_list.length; i++){
			if(list_for=='M'){
				name=items_list[i].fname+' '+items_list[i].lname;
				name=name.replace(/\s+/,' ');
				name=$.trim(name);
				name+=" ("+items_list[i].usertype+")";

				id=items_list[i].id;

			}else if(list_for=='U'){
				name=items_list[i].menuname;
				name=$.trim(name);

				id=items_list[i].menuid;
				menu_icon=items_list[i].menu_icon;
				menu_perms=items_list[i].perms;

				if(parseInt(items_list[i].not_available_to_admin) == '1'  &&  $.inArray('1',roles)>-1 ){ // 1 is the id of role 'Admin'

					draggable=0;
				}else{
					draggable=1;
				}

			}else if(list_for=='R'){
				name=items_list[i].menuname;
				name=$.trim(name);

				id=items_list[i].menuid;
				menu_icon=items_list[i].menu_icon;
				menu_perms=items_list[i].perms;

				if(parseInt(items_list[i].not_available_to_admin) == '1'  && otherparams.roleid==1){ // 1 is the id of role 'Admin'

					draggable=0;
				}else{
					draggable=1;
				}

			}

			elem=access_privilege.createItemElem({name:name,id:id,menu_perms:menu_perms,menu_icon:menu_icon,draggable:draggable});
			$("#nonavailable_list_items").append(elem);
		}

	},


	createItemElem:function(obj){
		var disabledrag=(obj.draggable==0)?' disable_item_move':'';
		var elem=$("#elements_to_clone>.item").clone().prop('id','item_'+obj.id).find('span.itemtext').text(obj.name).end().find('.item_move_handle>i').addClass(obj.menu_icon).end();

		var priv_list = elem.find('.item_privilege_list');

		// create the chackbox elems for all the privileges
		var privilege_elem = '';
		for(idx in obj.menu_perms){
			privilege_elem = $("#elements_to_clone>.privilege_elem").clone().prop('id','priv_elem_'+obj.id+'_'+obj.menu_perms[idx].menu_perm_id);
			if(typeof obj.assigned_menu_perms!='undefined' && $.inArray(parseInt(obj.menu_perms[idx].menu_perm_id), obj.assigned_menu_perms)>-1)
				privilege_elem.find('.privilege_chkbox').prop({'checked':true, disabled:!obj.draggable, id:'priv_'+obj.id+'_'+obj.menu_perms[idx].menu_perm_id}).val(obj.menu_perms[idx].menu_perm_id).data({'perm':obj.menu_perms[idx].perm, id:obj.id, menu_perm_id:obj.menu_perms[idx].menu_perm_id});
			else
				privilege_elem.find('.privilege_chkbox').prop({'checked':false, disabled:!obj.draggable || typeof obj.assigned_menu_perms=='undefined', id:'priv_'+obj.id+'_'+obj.menu_perms[idx].menu_perm_id}).val(obj.menu_perms[idx].menu_perm_id).data({'perm':obj.menu_perms[idx].perm, id:obj.id, menu_perm_id:obj.menu_perms[idx].menu_perm_id});

			privilege_elem.find('.privilege_label').html(obj.menu_perms[idx].perm_name);
			priv_list.append(privilege_elem);
		}


		if(obj.draggable==0){
			elem.addClass('disabled_item ui-state-disabled');

		}
		return elem;

	},


	grantAccessTo:function(e){
		$(e.currentTarget).parent().parent().detach().prependTo('#available_list_items');
		access_privilege.onMenuGrantRevoke($(e.currentTarget).parent().parent().attr('id'),'grant');

	},

	revokeAccessTo:function(e){
		$(e.currentTarget).parent().parent().detach().prependTo('#nonavailable_list_items');
		access_privilege.onMenuGrantRevoke($(e.currentTarget).parent().parent().attr('id'),'revoke');

	},

	generateListIndex:function(listtype,indexon){
		/*if(listtype=='menu'){
			access_privilege.menu_list_index[indexon]={};
			var indexkey='';
			for(var i=0; i<access_privilege.menu_list.length; i++){
				if(access_privilege.menu_list[i].hasOwnProperty(indexon)){
					indexkey=access_privilege.menu_list[i][indexon];
					if(access_privilege.menu_list_index[indexon].hasOwnProperty(indexkey)){
						access_privilege.menu_list_index[indexon][indexkey].push(i);
					}else{
						access_privilege.menu_list_index[indexon][indexkey]=[];
						access_privilege.menu_list_index[indexon][indexkey].push(i);
					}

				}
			}

		}*/if(listtype=='menu'){
			access_privilege.menu_list_index[indexon]={};
			var indexkey='';
			for(var i=0; i<access_privilege.menu_list.length; i++){ // loop through the menu categories
				for(var k=0; k<access_privilege.menu_list[i]['menus'].length; k++){ // loop through the menus within a category

					if(access_privilege.menu_list[i]['menus'][k].hasOwnProperty(indexon)){
						indexkey=access_privilege.menu_list[i]['menus'][k][indexon];
						if(!access_privilege.menu_list_index[indexon].hasOwnProperty(indexkey))
							access_privilege.menu_list_index[indexon][indexkey]=[];
						access_privilege.menu_list_index[indexon][indexkey].push([i,k]); // i refers to the index of a category and k is the index of the menu item within the 'menus' index of the category refered by i
					}

				}
			}

		}else if(listtype=='user'){
			access_privilege.users_list_index[indexon]={};

			var indexkey='';
			for(var i=0; i<access_privilege.users_list.length; i++){
				if(access_privilege.users_list[i].hasOwnProperty(indexon)){
					indexkey=access_privilege.users_list[i][indexon];
					if(!access_privilege.users_list_index[indexon].hasOwnProperty(indexkey))
						access_privilege.users_list_index[indexon][indexkey]=[];
					access_privilege.users_list_index[indexon][indexkey].push(i);

				}
			}

		}else if(listtype=='roles'){
			access_privilege.roles_list_index[indexon]={};

			var indexkey='';
			for(var i=0; i<access_privilege.roles_list.length; i++){
				if(access_privilege.roles_list[i].hasOwnProperty(indexon)){
					indexkey=access_privilege.roles_list[i][indexon];
					if(!access_privilege.roles_list_index[indexon].hasOwnProperty(indexkey))
						access_privilege.roles_list_index[indexon][indexkey]=[];
					access_privilege.roles_list_index[indexon][indexkey].push(i);

				}
			}

		}



	},

	onHashChange:function(e){
		var hash=location.hash.replace(/^#/,'');

		if(access_privilege.curr_page_hash!=access_privilege.prev_page_hash){
			access_privilege.prev_page_hash=access_privilege.curr_page_hash;
		}
		access_privilege.curr_page_hash=hash;


		var hash_params={mode:''};
		if(hash!=''){
			var hash_params_temp=hash.split('&');
			var hash_params_count= hash_params_temp.length;
			for(var i=0; i<hash_params_count; i++){
				var temp=hash_params_temp[i].split('=');
				hash_params[temp[0]]=decodeURIComponent(temp[1]);
			}
		}


		switch(hash_params.mode.toLowerCase()){
			default:
						//var selected_criterion=	hash_params.crt || 'M';
						//var privilege_for= hash_params.p_for || '';
						//$("#privilege_managing_criterion_"+selected_criterion).trigger(ace.click_event);

		}



	}


}
