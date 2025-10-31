<?php
namespace scriptProviderFuncs;
function getCss($page){
	$css_files=array();


	switch(strtolower($page)){
		case 'dashboard': break;
		case 'login': break;
		case 'users': 
			$css_files[]=CONST_THEMES_CSS_PATH . 'tokenize2.min.css'; 
			break;
	}

	// $css_files[]=CONST_THEMES_CSS_PATH.'bootstrap-datetimepicker.min.css';
	return $css_files;
}

function getJavascripts($page){
	$js_files=array('BSH'=>array(),'BSB'=>array());
	switch(strtolower($page)){
		case 'dashboard': 
			break;
		case 'login': 	
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . "login.".RESOURCE_VERSION.".js";
			break;
		case 'users':
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'tokenize2-customized.'.RESOURCE_VERSION.'.js'; 
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'users.'.RESOURCE_VERSION.'.js';
			break;
		case 'sectors':
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'sectors.'.RESOURCE_VERSION.'.js';
			break;
		case 'industries':
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'industries.'.RESOURCE_VERSION.'.js';
			break;
		case 'customer-groups':
			$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'customer-groups.'.RESOURCE_VERSION.'.js';
			break;
		case 'customers':
		    $js_files['BSB'][] = CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'customers.' . RESOURCE_VERSION . '.js';
		    break;
		case 'item-categories':
	        $js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'item-categories.'.RESOURCE_VERSION.'.js';
	        break;    
		case 'items':
		    $js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . 'items.'.RESOURCE_VERSION.'.js';
		    break;

			
				
			
    }
	$js_files['BSB'][]=CONST_THEMES_CUSTOM_JAVASCRIPT_PATH . "common-functions.".RESOURCE_VERSION.".js";
	return $js_files;
}


function getDomReadyJsCode($page, $dom_ready_data = []){
	$autocomplete_wait_time = AUTOCOMPLETE_WAIT_TIME;
	$js_code="";

	switch(strtolower($page)){
		case 'common':
			$cookie_path = ($dom_ready_data['cookie_path']=='')?'/':$dom_ready_data['cookie_path'];
			$js_code = <<<EOF
				var options = {
					user_settings: {$dom_ready_data['user_settings']},
					allowed_menu_perms: {$dom_ready_data['allowed_menu_perms']},
					user_types: {$dom_ready_data['user_types']},
					click_event : $.fn.tap ? "tap" : "click",
					user_uploaded_files_url_path : "{$dom_ready_data['user_uploaded_files_url_path']}",
					noimage_file : "{$dom_ready_data['noimage_file']}",
					other_data:{$dom_ready_data['other_data']},
					cookie_path:"{$cookie_path}",
					sponsor_ads:{$dom_ready_data['sponsor_ads']},
					ad_display_interval:{$dom_ready_data['ad_display_interval']},
					is_admin:{$dom_ready_data['is_admin']},
				}
				$.extend(true, common_js_funcs, options);
				$('.close-guide').on(common_js_funcs.click_event, function (){
					common_js_funcs.pageGuideClosed($(this));
				});

				$('.main-content').on('change', 'input.error-field', e=>{
					$(e.target).removeClass('error-field');
				});
								

				$('.main-content').on(common_js_funcs.click_event,'input[type=checkbox]',function(ev){
					this.blur();
				});

				$('.main-content').on('focus','tr.delete_rec>td:not(:first) input,tr.delete_rec>td:not(:first) select',function(ev){
					this.blur();
				});

				$('.main-content').on('focus','tr.non-editable-rec>td input,tr.non-editable-rec>td select',function(ev){
					this.blur();
				});

				$('.main-content').on(common_js_funcs.click_event, '.nopropagate', e=>{
					e.stopPropagation();
				});

				$('.main-content').on(common_js_funcs.click_event + ' input keydown keyup keypress paste', 'input.non-editable, select.non-editable, textarea.non-editable', e=>{
					$(e.currentTarget).blur();
					e.preventDefault();
					e.stopPropagation();
				});

				

				$('.clear-multiselect').on('click', function(e){
					e.preventDefault();
					e.stopPropagation();
					const mulselid = $(this).data('listid');
					const mulsel = $('#'+mulselid);
					mulsel.multiselect('deselectAll');
					mulsel.multiselect('rebuild');
				})

				common_js_funcs.cleanDatepicker();
EOF;
				if($dom_ready_data['show_sponsor_ad']==true){
					$js_code .= <<<EOF
					common_js_funcs.cycleSponsorAds(0);
EOF;
				}
										
				$js_code .= <<<EOF
				if($(window).hashchange){
					// In mobile view at times, when the screen changes but the page doesn't load and if the menu is open it remains so. The below code will turn off the menu for such cases. 
					// This is an additional common handler being added to the hashchange event, as individual modules have their own hashchange handler function.
					$(window).hashchange(()=>{
						if($('#menu-toggle').is(':visible')){
							$("#wrapper").removeClass("toggled");
						}
					});
				}
				
EOF;
			break;
		case 'dashboard': 	
			$js_code = <<<EOF
			$('.main-content').on(common_js_funcs.click_event,'td.clickable-cell',common_js_funcs.changeLocationWithDataProperty);
EOF;
		break;
		case 'login': 	break;
		case 'users':
			$datepicker_icon = CONST_THEMES_CUSTOM_IMAGES_PATH.'datepicker.gif';
			$js_code=<<<EOF


			$('.main-content').on(common_js_funcs.click_event,'.record-list-refresh-button',{self:usersfuncs},usersfuncs.refreshList);

			$('.main-content').on(common_js_funcs.click_event,'.clickable-cell',{self:usersfuncs},common_js_funcs.changeLocationWithDataProperty);
			$('.main-content').on(common_js_funcs.click_event,'.page-link',{self:usersfuncs},usersfuncs.changePage);
			$('.main-content').on(common_js_funcs.click_event,'.toggle-search',{self:usersfuncs},usersfuncs.toggleSearch);
			$('.main-content').on(common_js_funcs.click_event,'.toggle-sort',{self:usersfuncs},usersfuncs.toggleSortPanel);
			$('.main-content').on(common_js_funcs.click_event,'.record-delete-button',{self:usersfuncs},usersfuncs.deleteUser);

			$('#users-list>thead>tr>th.sortable').bind(common_js_funcs.click_event,{self:usersfuncs},usersfuncs.sortTable);

			$('#user_detail_view_container .email-icon-form-input, #user_detail_view_container .wa-icon-form-input, #user_detail_view_container .tel-icon-form-input, #user_detail_add_edit_container .email-icon-form-input, #user_detail_add_edit_container .wa-icon-form-input, #user_detail_add_edit_container .tel-icon-form-input').on(common_js_funcs.click_event, common_js_funcs.changeLocationWithDataProperty);			
			$('#remove_profile_pic_selection').on(common_js_funcs.click_event, usersfuncs.removeProfilePicSelection);

			$('#remove_profile_pic').on(common_js_funcs.click_event, usersfuncs.markProfilePicForDeletion);
			$('#undo_remove_profile_pic').on(common_js_funcs.click_event, usersfuncs.removeProfilePicDeleteMarker);

			$('#user_list_container').on(common_js_funcs.click_event,'.searched_elem .remove_filter' ,usersfuncs.clearSearch);

			usersfuncs.user_roles = usersfuncs.all_user_roles = {$dom_ready_data['users']['user_roles_list']}||[];
			usersfuncs.user_levels = {$dom_ready_data['users']['user_levels']}||{};


			$('#add_form_field_dob_picker').datepicker({
				dateFormat:'dd-M-yy',
				altFormat: 'yy-mm-dd',
				altField: '#add_form_field_dob',
				showOn: "both",
				buttonImage: 'images/calendar.png',
				// buttonImageOnly: true,
				showButtonPanel: true,
				changeMonth: true,
				changeYear: true,
				yearRange: '1900:+0',
				maxDate: "+0d"
			});


			$('#add_form_field_annv_picker').datepicker({
				dateFormat:'dd-M-yy',
				altFormat: 'yy-mm-dd',
				altField: '#add_form_field_annv',
				showOn: "both",
				buttonImage: 'images/calendar.png',
				// buttonImageOnly: true,
				showButtonPanel: true,
				changeMonth: true,
				changeYear: true,
				yearRange: '1900:+0',
				maxDate: "+0d"
			});

			$(window).hashchange(usersfuncs.onHashChange);
			$(window).hashchange();
			usersfuncs.salutaions={$dom_ready_data['users']['salutation']} || [];
EOF;
			break;

		case 'industries':
			$js_code=<<<EOF

			industryfuncs.init();
			
EOF;
			break;

		case 'customer-groups':
			$js_code=<<<EOF

			customergroupfuncs.init();
			
EOF;
			break;

		case 'customers':
		    $js_code = <<<EOF
		    customers.init();
		    
EOF;
		    break;

    case 'item-categories':
        $js_code=<<<EOF

        categoryFuncs.init();
        
EOF;
        break;

	case 'items':
		$js_code=<<<EOF
		itemfuncs.init();
EOF;
    	break;

	}

	return $js_code;
}




?>
