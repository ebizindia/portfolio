var usergroupfuncs={
    searchparams:[],  /* [{searchon:'',searchtype:'',searchtext:''},{},..] */
    sortparams:[],  /* [{sorton:'',sortorder:''},{},..] */
    default_sort:{sorton:'name',sortorder:'ASC'},
    paginationdata:{},
    defaultleadtabtext:'User Groups',
    filtersapplied:[],
    statuschangestarted:0,
    ajax_data_script:'user-groups.php',
    curr_page_hash:'',
    prev_page_hash:'',
    list_page_hash:'',
    name_pattern: /^[A-Z0-9_ -]+$/i,
    pp_max_filesize:0,

    init: function(opt={}){
        $('.main-content').on(common_js_funcs.click_event,'td.clickable-cell',{self:usergroupfuncs},common_js_funcs.changeLocationWithDataProperty);
        $('.main-content').on(common_js_funcs.click_event,'.page-link',{self:usergroupfuncs},usergroupfuncs.changePage);
        $('.main-content').on(common_js_funcs.click_event,'.toggle-search',{self:usergroupfuncs},usergroupfuncs.toggleSearch);

        if(CAN_DELETE) // attach delete handler only if the user has the delete right
            $('.main-content').on(common_js_funcs.click_event,'.record-delete-button',{self:usergroupfuncs},usergroupfuncs.deleteUserGroup);

        $('#recs-list>thead>tr>th.sortable').bind(common_js_funcs.click_event,{self:usergroupfuncs},usergroupfuncs.sortTable);

        $('#rec_list_container').on(common_js_funcs.click_event,'.searched_elem .remove_filter',usergroupfuncs.clearSearch);

        $(window).hashchange(usergroupfuncs.onHashChange);
        $(window).hashchange();
    },

    toggleSearch: function(ev){
        let elem = $(ev.currentTarget);
        usergroupfuncs.setPanelVisibilityStatus('usergroup_search_toggle', elem.hasClass('search-form-visible')?'':'visible'); // set closed status for the search panel
        usergroupfuncs.showHidePanel('usergroup_search_toggle');
    },


    setPanelVisibilityStatus: function(panel, status){
        if (typeof(Storage) !== "undefined") {
            localStorage[panel] = status;
        } else {
            Cookies.set(panel, status, {path : '/'});
        }
    },

    showHidePanel: function(panel){
        if(panel === 'usergroup_search_toggle'){
            let show_srch_form = false;
            if (typeof(Storage) !== "undefined") {
                srch_frm_visible = localStorage.usergroup_search_toggle;
            } else {
                srch_frm_visible = Cookies.get('usergroup_search_toggle');
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
                $("#search-field_name").focus();
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


    user_count:0,
    showList:function(resp,otherparams){
        var self=usergroupfuncs;
        var listhtml=resp[1].list;
        self.user_count=resp[1]['reccount'];
        $("#rec_list_container").removeClass('d-none');
        $("#rec_detail_add_edit_container").addClass('d-none');
        $("#common-processing-overlay").addClass('d-none');
        $("#userlistbox").html(listhtml);

        if(resp[1].tot_rec_cnt>0){
            $('#heading_rec_cnt').text((resp[1]['reccount']==resp[1]['tot_rec_cnt'])?`(${resp[1]['tot_rec_cnt']})`:`(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`);

        }else{
            $('#heading_rec_cnt').text('(0)');

        }

        $("#add-record-button").removeClass('d-none');
        $("#refresh-list-button").removeClass('d-none');
        $(".back-to-list-button").addClass('d-none').attr('href',"user-groups.php#"+usergroupfuncs.curr_page_hash);
        $("#edit-record-button").addClass('d-none');
        self.paginationdata=resp[1].paginationdata;

        self.setSortOrderIcon();


    },


    onListRefresh:function(resp,otherparams){
        var self=usergroupfuncs;
        $("#common-processing-overlay").addClass('d-none');
        var listhtml=resp[1].list;
        $("#userlistbox").html(listhtml);
        self.paginationdata=resp[1].paginationdata;
        self.setSortOrderIcon();
    },

    resetSearchParamsObj:function(){
        var self=usergroupfuncs;
        self.searchparams=[];
    },

    setSearchParams:function(obj){
        var self=usergroupfuncs;
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

        var self=usergroupfuncs;
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

        usergroupfuncs.resetSearchParamsObj();
        let fld = '';
        $('.panel-search .srchfld').each(function(i, el){
            let val = $.trim($(el).val());
            if(val!=''){
                fld = $(el).data('fld');
                usergroupfuncs.setSearchParams({searchon:$(el).data('fld'),searchtype:$(el).data('type'),searchtext:val});
            }
        });

        if(usergroupfuncs.searchparams.length<=0)
            return false;

        var options={pno:1};
        usergroupfuncs.getList(options);
        return false;
    },


    changePage:function(ev){
        ev.preventDefault();
        if(!$(ev.currentTarget).parent().hasClass('disabled')){
            var self=usergroupfuncs;
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

        usergroupfuncs.sortparams=[];
        usergroupfuncs.sortparams.push({sorton:sorton, sortorder:sortorder});
        var options={pno:pno};
        usergroupfuncs.getList(options);

    },


    setSortOrderIcon:function(){
        var self=usergroupfuncs;
        if(self.sortparams.length>0){
            var sorton = self.sortparams[0].sorton == 'user_type'?'usertype':self.sortparams[0].sorton.replace('_','-');
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
        var self=usergroupfuncs;
        if(recordid=='')
            return false;

        document.addrecform.reset();
        $(".form-control").removeClass("error-field");
        $("#record-save-button").removeClass('d-none').attr('disabled', false);
        $("#common-processing-overlay").removeClass('d-none');
        $("#record-add-cancel-button").attr('href',"user-groups.php#"+usergroupfuncs.prev_page_hash);
        $('#msgFrm').removeClass('d-none');
        var coming_from='';
        var options={mode:'editrecord',recordid:recordid,leadtabtext:'Edit User Group',coming_from:coming_from}
        self.openRecord(options);
        return false;

    },


    openRecord:function(options){
        var self=usergroupfuncs;
        var opts={leadtabtext:'User Group Details'};
        $.extend(true,opts,options);

        usergroupfuncs.dep_rowno_max=-1;

        var params={mode:"getRecordDetails",recordid:opts.recordid};
        var options={cache:'no-cache',async:true,type:'post',dataType:'json',url:self.ajax_data_script,params:params,successResponseHandler:self.showLeadDetailsWindow,successResponseHandlerParams:{self:self,mode:opts.mode,recordid:opts.recordid,coming_from:opts.coming_from,header_bar_text:opts.leadtabtext}};
        common_js_funcs.callServer(options);

    },


    showLeadDetailsWindow:function(resp,otherparams){
        const self=otherparams.self;
        let container_id='';
        $("#common-processing-overlay").addClass('d-none');
        const rec_id= resp[1].record_details.id ??'';

        if(otherparams.mode=='editrecord'){
            var coming_from=otherparams.coming_from;

            if(rec_id!=''){

                if(resp[1].can_edit===false){
                    // User is not authorised to edit this record so send him back to the previous screen
                    location.hash=usergroupfuncs.prev_page_hash;
                    return;
                }

                usergroupfuncs.removeEditRestrictions();

                let name = resp[1].record_details.name || '';
                let name_disp = resp[1].record_details.name_disp || '';
                let active = resp[1].record_details.active || '';


                var contobj=$("#rec_detail_add_edit_container");

                $('.alert-danger').addClass('d-none').find('.alert-message').html('');
                $('#msgFrm').removeClass('d-none');
                contobj.find(".form-actions").removeClass('d-none');

                contobj.find("form[name=addrecform]:eq(0)").data('mode','edit-rec').find('input[name=status]').prop('checked',false).end().get(0).reset();

                $('.addonly').addClass('d-none');
                $('.editonly').removeClass('d-none');
                $('#edit_form_field_name, input[name=active]').attr('disabled', false);
                $('#add_form_field_name').attr('disabled', true)

                contobj.find("#add_edit_mode").val('updaterec');
                contobj.find("#add_edit_recordid").val(rec_id);
                contobj.find("input[name=status]").prop('checked', false);
                if(active!='')
                    contobj.find("#edit_form_field_status_"+active).prop('checked', true);
                contobj.find("#edit_form_field_name").val(name).focus();

                let header_text = 'Edit User Group';

                contobj.find("#record-add-cancel-button").data('back-to',coming_from);
                contobj.find("#record-save-button>span:eq(0)").html('Save Changes');
                contobj.find("#panel-heading-text").text(header_text);
                contobj.find("#infoMsg").html('Edit User Group <b>' + name_disp +  '</b>');
                usergroupfuncs.setheaderBarText(header_text);

                usergroupfuncs.applyEditRestrictions(resp[1].edit_restricted_fields);
                container_id='rec_detail_add_edit_container';


            }else{

                var message="Sorry, the edit window could not be opened (Server error).";
                if(resp[0]==1){
                    message="Sorry, the edit window could not be opened (User Group ID missing).";
                }else if(resp[0]==2){
                    message="Sorry, the edit window could not be opened (Server error).";
                }else if(resp[0]==3){
                    message="Sorry, the edit window could not be opened (Invalid user group ID).";
                }

                alert(message);
                location.hash=usergroupfuncs.prev_page_hash;
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
            }else if(container_id!='user_detail_view_container'){
                $("#user_detail_view_container").addClass('d-none');
                $("#edit-record-button").addClass('d-none');
            }

            $("#"+container_id).removeClass('d-none');
            self.setheaderBarText(otherparams.header_bar_text);

        }

    },

    applyEditRestrictions: function(restricted_fields){
        const contobj=$("#rec_detail_add_edit_container");
        restricted_fields.forEach(fld=>{
            switch(fld){
                case 'name':
                    contobj.find("#add_form_field_name").prop('disabled', restricted_fields.includes('name')).addClass('rstrctedt');
                    break;
                case 'active':
                    contobj.find("input[name=active]").prop('disabled', restricted_fields.includes('active')).addClass('rstrctedt');
                    break;
            }

        });
    },

    removeEditRestrictions: function(){
        const contobj=$("#rec_detail_add_edit_container");
        contobj.find("#add_form_field_name, input[name=active]").prop('disabled', false).end();
        contobj.find('.rstrctedt').removeClass('rstrctedt');
    },

    handleAddRecResponse:function(resp){
        var self=usergroupfuncs;
        $(".form-control").removeClass("error-field");

        if(resp.error_code==0){
            var message_container = '.alert-success';
            $("#record-add-cancel-button>i:eq(0)").next('span').html('Close');
            $("form[name=addrecform]").find(".error-field").removeClass('error-field').end().get(0).reset();
            $("#add_form_field_name").focus();

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
        var self=usergroupfuncs;

        var mode_container='rec_detail_add_edit_container';
        $(".form-control").removeClass("error-field");

        if(resp.error_code==0){

            var message_container = '.alert-success';

            $("#add_form_field_name").focus();
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

        var self=usergroupfuncs;
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
        let name = active = name_fld = '';
        if(mode=='add-rec'){
            name_fld_id = '#add_form_field_name';
            name=$.trim($(name_fld_id).val());
        }else{
            name_fld_id = '#edit_form_field_name';
            name=$.trim($(name_fld_id).val());
            active =frm.find('input[name=active]:checked').val();
        }

        if(name == ''){
            errors.push('User Group name is required.');
            error_fields.push(name_fld_id);
            $(name_fld_id).addClass("error-field");

        }else if(mode=='edit-rec' && active==''){
            errors.push('Please select a status for the user group.');
            error_fields.push('#edit_form_field_status_y');
            $("#edit_form_field_status_y").addClass("error-field");

        }

        return {'errors': errors, 'error_fields': error_fields};

    },

    openAddUserForm:function(e){
        if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
            var self=e.data.self;
        }else{
            var self=usergroupfuncs;
        }
        document.addrecform.reset();

        usergroupfuncs.removeEditRestrictions();

        usergroupfuncs.dep_rowno_max=-1;
        $(".form-control").removeClass("error-field");
        $("#refresh-list-button").addClass('d-none');
        $("#add-record-button").addClass('d-none');
        $("#edit-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_add_edit_container").removeClass('d-none').find("#panel-heading-text").text('Add User Groups').end();
        $('#msgFrm').removeClass('d-none');

        $(".back-to-list-button").removeClass('d-none');

        $("#rec_detail_add_edit_container").find("#record-save-button>span:eq(0)").html('Add User Groups').end().find("#add_edit_mode").val('createrec').end().find("#add_edit_recordid").val('').end().find("#record-add-cancel-button").data('back-to','').attr('href',"user-groups.php#"+usergroupfuncs.prev_page_hash);
        $("form[name=addrecform]").data('mode','add-rec').find(".error-field").removeClass('error-field').end().find('input[name=active]').prop('checked',false).end().get(0).reset();

        self.setheaderBarText("");
        $('.addonly').removeClass('d-none');
        $('.editonly').addClass('d-none');
        $('#edit_form_field_name, input[name=active]').attr('disabled', true);
        $('#add_form_field_name').attr('disabled', false).focus();

        document.querySelector('.main-content').scrollIntoView(true);
        return false;

    },

    deleteUserGroup:function(ev){
        ev.preventDefault();
        ev.stopPropagation();
        const elem = $(ev.currentTarget);
        let id = elem.data('recid');
        let groupname = elem.data('groupname');

        if(confirm(`Really delete the user group "${groupname}" ?`)){

            let rec_details = {};
            common_js_funcs.callServer({cache:'no-cache',async:false,dataType:'json',type:'post',url:usergroupfuncs.ajax_data_script,params:{mode:'deleteUserGroup', rec_id:id},
                successResponseHandler:function(resp,status,xhrobj){
                    if(resp.error_code == 0)
                        usergroupfuncs.handleDeleteResp(resp);
                    else
                        alert(resp.message);
                },
                successResponseHandlerParams:{}});
            return rec_details;
        }

    },
    handleDeleteResp:function(resp){
        alert(resp.message);
        usergroupfuncs.refreshList();
    },

    refreshList:function(e){
        if(typeof e=='object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')){
            var self=e.data.self;
        }else{
            var self=usergroupfuncs;
        }

        var currpage=self.paginationdata.curr_page;

        var options={pno:currpage,successResponseHandler:self.onListRefresh};
        self.getList(options);
        return false;

    },

    closeAddUserForm:function(){
        var self =this;
        return true;

    },


    setheaderBarText:function(text){
        $("#header-bar-text").find(":first-child").html(text);
    },


    onHashChange:function(e){
        var hash=location.hash.replace(/^#/,'');
        if(usergroupfuncs.curr_page_hash!=usergroupfuncs.prev_page_hash){
            usergroupfuncs.prev_page_hash=usergroupfuncs.curr_page_hash;
        }
        usergroupfuncs.curr_page_hash=hash;

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
            case 'addrec':
                if(!CAN_ADD)
                    window.history.pushState({}, "", CONST_APP_ABSURL+ usergroupfuncs.ajax_data_script +'#'+usergroupfuncs.list_page_hash);
                else{
                    $('.alert-success, .alert-danger').addClass('d-none');
                    $('#msgFrm').removeClass('d-none');
                    usergroupfuncs.openAddUserForm();
                }
                break;

            case 'edit':
                if(!CAN_EDIT)
                    window.history.pushState({}, "", CONST_APP_ABSURL+ usergroupfuncs.ajax_data_script +'#'+usergroupfuncs.list_page_hash);
                else{
                    $('.alert-success, .alert-danger').addClass('d-none');
                    $('#msgFrm').removeClass('d-none');
                    if(hash_params.hasOwnProperty('recid') && hash_params.recid!=''){
                        usergroupfuncs.openRecordForEditing(hash_params.recid);

                    }else{
                        location.hash=usergroupfuncs.prev_page_hash;
                    }
                }
                break;



            default:
                if(hash_params.mode==='')
                    usergroupfuncs.list_page_hash = hash;
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                var params={mode:'getList',pno:1, searchdata:"[]", sortdata:JSON.stringify(usergroupfuncs.sortparams), listformat:'html'};

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

                usergroupfuncs.searchparams=JSON.parse(params['searchdata']);
                usergroupfuncs.sortparams=JSON.parse(params['sortdata']);

                if(usergroupfuncs.sortparams.length==0){
                    usergroupfuncs.sortparams.push(usergroupfuncs.default_sort);
                    params['sortdata']=JSON.stringify(usergroupfuncs.sortparams);
                }

                if(usergroupfuncs.searchparams.length>0){
                    $.each(usergroupfuncs.searchparams, function(idx,data) {
                        switch (data.searchon) {
                            case 'name':
                                $("#search-field_name").val(data.searchtext);
                                break;
                        }
                    });
                }
                $("#common-processing-overlay").removeClass('d-none');

                common_js_funcs.callServer({cache:'no-cache',async:true,dataType:'json',type:'post', url:self.ajax_data_script,params:params,successResponseHandler:usergroupfuncs.showList,successResponseHandlerParams:{self:usergroupfuncs}});

                usergroupfuncs.showHidePanel('usergroup_search_toggle');

        }
    }
}