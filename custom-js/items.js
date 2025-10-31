var itemfuncs = {
    searchparams: [],  /* [{searchon:'',searchtype:'',searchtext:''},{},..] */
    sortparams: [],  /* [{sorton:'',sortorder:''},{},..] */
    default_sort: {sorton:'name', sortorder:'ASC'},
    paginationdata: {},
    defaultleadtabtext: 'Items',
    filtersapplied: [],
    ajax_data_script: 'items.php',
    curr_page_hash: '',
    prev_page_hash: '',

    init: function(opt={}){
        $('.main-content').on('click', 'td.clickable-cell', {self:itemfuncs}, common_js_funcs.changeLocationWithDataProperty);
        $('.main-content').on('click', '.page-link', {self:itemfuncs}, itemfuncs.changePage);
        $('.main-content').on('click', '.toggle-search', {self:itemfuncs}, itemfuncs.toggleSearch);
        $('.main-content').on('click', '.record-delete-button', {self:itemfuncs}, itemfuncs.deleteItem);
        $('#recs-list>thead>tr>th.sortable').bind('click', {self:itemfuncs}, itemfuncs.sortTable);
        $('#rec_list_container').on('click', '.searched_elem .remove_filter', itemfuncs.clearSearch);
        $(window).hashchange(itemfuncs.onHashChange);
        $(window).hashchange();
    },
    
    toggleSearch: function(ev) {
        itemfuncs.setPanelVisibilityStatus('items_search_toggle', $(ev.currentTarget).hasClass('search-form-visible') ? '' : 'visible');
        itemfuncs.showHidePanel('items_search_toggle');
    },
    
    setPanelVisibilityStatus: function(panel, status) {
        if (typeof(Storage) !== "undefined") {
            localStorage[panel] = status;
        } else {
            Cookies.set(panel, status, {path: '/'});
        }
    },
    
    showHidePanel: function(panel) {
        if(panel === 'items_search_toggle') {
            let show_srch_form = false;
            if (typeof(Storage) !== "undefined") {
                srch_frm_visible = localStorage.items_search_toggle;
            } else {
                srch_frm_visible = Cookies.get('items_search_toggle');
            }
            if(srch_frm_visible && srch_frm_visible == 'visible')
                show_srch_form = true;
            $('.toggle-search').toggleClass('search-form-visible', show_srch_form);
            $('#search_records').closest('.panel-search').toggleClass('d-none', !show_srch_form);
            let search_form_cont = $('#search_records').closest('.panel-search');
            if(search_form_cont.hasClass('d-none'))
                $('.toggle-search').prop('title', 'Open search panel');
            else {
                $('.toggle-search').prop('title', 'Close search panel');
                $("#search-field_sku").focus();
            }
        }
    },
    
    getList: function(options) {
        var self = this;
        var pno = 1;
        var params = [];
        if('pno' in options) {
            params.push('pno=' + encodeURIComponent(options.pno));
        } else {
            params.push('pno=1');
        }
        params.push('searchdata=' + encodeURIComponent(JSON.stringify(self.searchparams)));
        params.push('sortdata=' + encodeURIComponent(JSON.stringify(self.sortparams)));
        params.push('ref=' + Math.random());
        $("#common-processing-overlay").removeClass('d-none');
        location.hash = params.join('&');
    },
    
    item_count: 0,
    
    showList: function(resp, otherparams) {
        var self = itemfuncs;
        var listhtml = resp[1].list;
        self.item_count = resp[1]['reccount'];
        $("#rec_list_container").removeClass('d-none');
        $("#rec_detail_add_edit_container").addClass('d-none');
        $("#common-processing-overlay").addClass('d-none');
        $("#itemslistbox").html(listhtml);
        
        if(resp[1].tot_rec_cnt > 0) {
            $('#heading_rec_cnt').text((resp[1]['reccount'] == resp[1]['tot_rec_cnt']) ? 
                `(${resp[1]['tot_rec_cnt']})` : 
                `(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`);
            itemfuncs.setExportLink(resp[1]['reccount'] > 0 ? true : false);
        } else {
            $('#heading_rec_cnt').text('(0)');
            itemfuncs.setExportLink(false);
        }
        
        $("#add-record-button").removeClass('d-none');
        $("#refresh-list-button").removeClass('d-none');
        $(".back-to-list-button").addClass('d-none').attr('href', "items.php#" + itemfuncs.curr_page_hash);
        $("#edit-record-button").addClass('d-none');
        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },
    
    onListRefresh: function(resp, otherparams) {
        var self = itemfuncs;
        $("#common-processing-overlay").addClass('d-none');
        var listhtml = resp[1].list;
        $("#itemslistbox").html(listhtml);
        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },
    
    setExportLink: function(show) {
        const dnld_elem = $('#export_items');
        if(dnld_elem.length <= 0) // the download link element does not exist, the user might not be in ADMIN role
            return;
        let url = '#';
        if(show === true) {
            let params = [];
            params.push('mode=export');
            params.push('searchdata=' + encodeURIComponent(JSON.stringify(this.searchparams)));
            params.push('sortdata=' + encodeURIComponent(JSON.stringify(this.sortparams)));
            params.push('ref=' + Math.random());
            url = `${window.location.origin}${window.location.pathname}?${params.join('&')}`;
        }
        dnld_elem.attr('href', url).toggleClass('d-none', show !== true);
    },
    
    resetSearchParamsObj: function() {
        var self = itemfuncs;
        self.searchparams = [];
    },
    
    setSearchParams: function(obj) {
        var self = itemfuncs;
        self.searchparams.push(obj);
    },

    clearSearch: function(e) {
        let remove_all = true;
        if(e) {
            e.stopPropagation();
            elem = e.currentTarget;
            if($(elem).hasClass('remove_filter')) {
                remove_all = $(elem).data('fld');
                $(elem).parent('.searched_elem').remove();
                $('.panel-search .srchfld[data-fld=' + remove_all + ']').val('');
            }
        }
        var self = itemfuncs;
        if(remove_all === true) {
            self.resetSearchParamsObj();
            document.search_form.reset();
        } else {
            self.searchparams = self.searchparams.filter(fltr => {
                return fltr.searchon !== remove_all;
            });
        }
        var options = {pno: 1};
        self.getList(options);
        return false;
    },

    doSearch: function() {
        itemfuncs.resetSearchParamsObj();
        let fld = '';
        $('.panel-search .srchfld').each(function(i, el) {
            let val = $.trim($(el).val());
            if(val != '') {
                fld = $(el).data('fld');
                let disp_text='';
                itemfuncs.setSearchParams({searchon: $(el).data('fld'), searchtype: $(el).data('type'), searchtext: val, disp_text:disp_text});
            }
        });
        if(itemfuncs.searchparams.length <= 0)
            return false;
        var options = {pno: 1};
        itemfuncs.getList(options);
        return false;
    },
    
    changePage: function(ev) {
        ev.preventDefault();
        if(!$(ev.currentTarget).parent().hasClass('disabled')) {
            var self = itemfuncs;
            var pno = $(ev.currentTarget).data('page');
            self.getList({pno: pno});
        }
    },
    
    sortTable: function(e) {
        var self = e.data.self;
        var elemid = e.currentTarget.id;
        var elemidparts = elemid.split('_');
        var sorton = elemidparts[1].replaceAll('-', '_');
        var sortorder = 'ASC';
        if($(e.currentTarget).find("i:eq(0)").hasClass('fa-sort-up'))
            sortorder = 'DESC';
        itemfuncs.sortparams = [];
        itemfuncs.sortparams.push({sorton: sorton, sortorder: sortorder});
        var options = {pno: 1};
        itemfuncs.getList(options);
    },
    
    setSortOrderIcon: function() {
        var self = itemfuncs;
        if(self.sortparams.length > 0) {
            var sorton = self.sortparams[0].sorton.replaceAll('_', '-');
            var colheaderelemid = 'colheader_' + sorton;
            if(self.sortparams[0].sortorder == 'DESC') {
                var sort_order_class = 'fa-sort-down';
            } else {
                var sort_order_class = 'fa-sort-up';
            }
            $("#" + colheaderelemid).siblings('th.sortable').removeClass('sorted-col')
                .find('i:eq(0)').removeClass('fa-sort-down fa-sort-up').addClass('fa-sort').end()
                .end().addClass('sorted-col').find('i:eq(0)')
                .removeClass('fa-sort-down fa-sort-up').addClass(sort_order_class);
        }
    },
    
    openRecordForEditing: function(recordid) {
        var self = itemfuncs;
        if(recordid == '')
            return false;
        document.addrecform.reset();
        $(".form-control").removeClass("error-field");
        $("#record-save-button").removeClass('d-none').attr('disabled', false);
        $("#common-processing-overlay").removeClass('d-none');
        $("#record-add-cancel-button").attr('href', "items.php#" + itemfuncs.prev_page_hash);
        $('#msgFrm').removeClass('d-none');
        var coming_from = '';
        var options = {mode: 'editrecord', recordid: recordid, leadtabtext: 'Edit Item', coming_from: coming_from}
        self.openRecord(options);
        return false;
    },
    
    openRecord: function(options) {
        var self = itemfuncs;
        var opts = {leadtabtext: 'Item Details'};
        $.extend(true, opts, options);
        var params = {mode: "getRecordDetails", recordid: opts.recordid};
        var options = {
            cache: 'no-cache', 
            async: true, 
            type: 'post', 
            dataType: 'json', 
            url: self.ajax_data_script, 
            params: params, 
            successResponseHandler: self.showItemDetailsWindow, 
            successResponseHandlerParams: {
                self: self, 
                mode: opts.mode, 
                recordid: opts.recordid, 
                coming_from: opts.coming_from, 
                header_bar_text: opts.leadtabtext
            }
        };
        common_js_funcs.callServer(options);
    },
    
    showItemDetailsWindow: function(resp, otherparams) {
        const self = otherparams.self;
        let container_id = '';
        $("#common-processing-overlay").addClass('d-none');
        const rec_id = resp[1].record_details.id ?? '';

        if(otherparams.mode == 'editrecord') {
            var coming_from = otherparams.coming_from;
            if(rec_id != '') {
                if(resp[1].can_edit === false) {
                    location.hash = itemfuncs.prev_page_hash;
                    return;
                }

                var contobj = $("#rec_detail_add_edit_container");
                $('.alert-danger').addClass('d-none').find('.alert-message').html('');
                $('#msgFrm').removeClass('d-none');
                contobj.find(".form-actions").removeClass('d-none');
                contobj.find("form[name=addrecform]:eq(0)").data('mode', 'edit-rec').get(0).reset();
                $('.addonly').addClass('d-none');
                $('.editonly').removeClass('d-none');

                contobj.find("#add_edit_mode").val('updaterec');
                contobj.find("#add_edit_recordid").val(rec_id);

                let header_text = 'Edit Item';
                contobj.find("#record-add-cancel-button").data('back-to', coming_from);
                contobj.find("#record-save-button>span:eq(0)").html('Save Changes');
                contobj.find("#panel-heading-text").text(header_text);
                contobj.find("#infoMsg").html('Edit Item <b>' + resp[1].record_details.name + '</b>');
                itemfuncs.setheaderBarText(header_text);

                // Fill in the form with the record details - Updated field names
                contobj.find('#add_name').val(resp[1].record_details.name || '').end()
                    .find('#add_make').val(resp[1].record_details.make || '').end()
                    .find('#add_unit').val(resp[1].record_details.unit || '');

                contobj.find("#add_name").focus();
                container_id = 'rec_detail_add_edit_container';
            } else {
                var message = "Sorry, the edit window could not be opened (Server error).";
                if(resp[0] == 1) {
                    message = "Sorry, the edit window could not be opened (Item ID missing).";
                } else if(resp[0] == 2) {
                    message = "Sorry, the edit window could not be opened (Server error).";
                } else if(resp[0] == 3) {
                    message = "Sorry, the edit window could not be opened (Invalid item ID).";
                }
                alert(message);
                location.hash = itemfuncs.prev_page_hash;
                return;
            }
        }
        
        if(container_id != '') {
            $(".back-to-list-button").removeClass('d-none');
            $("#refresh-list-button").addClass('d-none');
            $("#add-record-button").addClass('d-none');
            $("#rec_list_container").addClass('d-none');
            if(container_id != 'rec_detail_add_edit_container') {
                $("#rec_detail_add_edit_container").addClass('d-none');
                $("#edit-record-button").removeClass('d-none').data('recid', otherparams.recordid);
            } else if(container_id != 'user_detail_view_container') {
                $("#user_detail_view_container").addClass('d-none');
                $("#edit-record-button").addClass('d-none');
            }
            $("#" + container_id).removeClass('d-none');
            self.setheaderBarText(otherparams.header_bar_text);
        }
    },

    handleAddRecResponse: function(resp) {
        var self = itemfuncs;
        $(".form-control").removeClass("error-field");
        if(resp.error_code == 0) {
            var message_container = '.alert-success';
            $("#record-add-cancel-button>i:eq(0)").next('span').html('Close');
            $("form[name=addrecform]").find(".error-field").removeClass('error-field').end().get(0).reset();
            $("#add_name").focus();
            document.querySelector('.main-content').scrollIntoView(true);
        } else if(resp.error_code == 2) {
            var message_container = '';
            if(resp.error_fields && resp.error_fields.length > 0) {
                var msg = resp.message;
                alert(Array.isArray(msg) ? msg.join('\n') : msg);
                $(resp.error_fields[0]).focus();
                $(resp.error_fields[0]).addClass("error-field");
            }
        } else {
            var message_container = '.alert-danger';
        }

        $('#record-save-button, #record-add-cancel-button').removeClass('disabled').attr('disabled', false);
        $("#common-processing-overlay").addClass('d-none');

        if(message_container != '') {
            $(message_container).removeClass('d-none').siblings('.alert').addClass('d-none')
                .end().find('.alert-message').html(Array.isArray(resp.message) ? resp.message.join('<br>') : resp.message);
            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
            $('#msgFrm').addClass('d-none');
        }
    },

    handleUpdateRecResponse: function(resp) {
        var self = itemfuncs;
        var mode_container = 'rec_detail_add_edit_container';
        $(".form-control").removeClass("error-field");

        if(resp.error_code == 0) {
            var message_container = '.alert-success';
            $("#add_name").focus();
        } else if(resp.error_code == 2) {
            var message_container = '';
            if(resp.error_fields && resp.error_fields.length > 0) {
                alert(Array.isArray(resp.message) ? resp.message.join('\n') : resp.message);
                setTimeout(() => {
                    $(resp.error_fields[0]).addClass("error-field").focus();
                }, 0);
            }
        } else {
            var message_container = '.alert-danger';
        }

        $('#record-save-button, #record-add-cancel-button').removeClass('disabled').attr('disabled', false);
        $("#common-processing-overlay").addClass('d-none');

        if(message_container != '') {
            $(message_container).removeClass('d-none').siblings('.alert').addClass('d-none')
                .end().find('.alert-message').html(Array.isArray(resp.message) ? resp.message.join('<br>') : resp.message);
            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
            $('#msgFrm').addClass('d-none');
        }
    },
    
    saveRecDetails: function(formelem) {
        var self = itemfuncs;
        var data_mode = $(formelem).data('mode');
        var res = self.validateRecDetails({mode: data_mode});
        if(res.error_field) {
            alert(res.error_msg);
            setTimeout(function() {
                $(res.error_field).focus();
            }, 0);
            return false;
        }
        $("#common-processing-overlay").removeClass('d-none');
        $('#record-save-button, #record-add-cancel-button').addClass('disabled').attr('disabled', true);
        $('#rec_detail_add_edit_container .error-field').removeClass('error-field');
        return true;
    },

    validateRecDetails: function(opts) {
        var errors = [], error_fields = [];
        let mode = opts.mode ?? 'add-rec';
        $(".form-control").removeClass("error-field");

        const frm = $('#addrecform');
        let name = $('#add_name').val().trim();
        let unit = $('#add_unit').val().trim();

        // Validate required fields
        if(name == '') {
            $('#add_name').addClass("error-field");
            return {'error_msg': 'Name is required.', 'error_field': $('#add_name')};
        } else if(name.length > 100) {
            $('#add_name').addClass("error-field");
            return {'error_msg': 'Name must not exceed 100 characters.', 'error_field': $('#add_name')};
        }

        if(unit == '') {
            $('#add_unit').addClass("error-field");
            return {'error_msg': 'Unit is required.', 'error_field': $('#add_unit')};
        }

        return {'error_msg': null, 'error_field': null};
    },

    openAddUserForm: function(e) {
        if(typeof e == 'object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')) {
            var self = e.data.self;
        } else {
            var self = itemfuncs;
        }

        document.addrecform.reset();
        $(".form-control").removeClass("error-field");
        $("#refresh-list-button").addClass('d-none');
        $("#add-record-button").addClass('d-none');
        $("#edit-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_add_edit_container").removeClass('d-none').find("#panel-heading-text").text('Add Item').end();
        $('#msgFrm').removeClass('d-none');
        $(".back-to-list-button").removeClass('d-none');

        $("#rec_detail_add_edit_container")
            .find("#record-save-button>span:eq(0)").html('Add Item').end()
            .find("#add_edit_mode").val('createrec').end()
            .find("#add_edit_recordid").val('').end()
            .find("#record-add-cancel-button").data('back-to', '').attr('href', "items.php#" + itemfuncs.prev_page_hash);

        $("form[name=addrecform]").data('mode', 'add-rec')
            .find(".error-field").removeClass('error-field').end().get(0).reset();

        self.setheaderBarText("");
        $('.addonly').removeClass('d-none');
        $('.editonly').addClass('d-none');

        var contobj = $("#rec_detail_add_edit_container");
        contobj.find("#add_name").focus();

        document.querySelector('.main-content').scrollIntoView(true);
        return false;
    },
    
    deleteItem: function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        const elem = $(ev.currentTarget);
        let id = elem.data('recid');
        let item = elem.data('item');
        
        if(confirm(`Really delete the item "${item}"?`)) {
            common_js_funcs.callServer({
                cache: 'no-cache', 
                async: false, 
                dataType: 'json', 
                type: 'post', 
                url: itemfuncs.ajax_data_script, 
                params: {mode: 'deleteItem', rec_id: id},
                successResponseHandler: function(resp, status, xhrobj) {
                    if(resp.error_code == 0)
                        itemfuncs.handleDeleteResp(resp);
                    else
                        alert(resp.message);
                }
            });
        }
    },
    
    handleDeleteResp: function(resp) {
        alert(resp.message);
        itemfuncs.refreshList();
    },
    
    refreshList: function(e) {
        if(typeof e == 'object' && e.hasOwnProperty('data') && e.data.hasOwnProperty('self')) {
            var self = e.data.self;
        } else {
            var self = itemfuncs;
        }
        var currpage = self.paginationdata.curr_page;
        var options = {pno: currpage, successResponseHandler: self.onListRefresh};
        self.getList(options);
        return false;
    },
    
    setheaderBarText: function(text) {
        $("#header-bar-text").find(":first-child").html(text);
    },
    
    onHashChange: function(e) {
        var hash = location.hash.replace(/^#/, '');
        
        if(itemfuncs.curr_page_hash != itemfuncs.prev_page_hash) {
            itemfuncs.prev_page_hash = itemfuncs.curr_page_hash;
        }
        itemfuncs.curr_page_hash = hash;
        
        var hash_params = {mode: ''};
        if(hash != '') {
            var hash_params_temp = hash.split('&');
            var hash_params_count = hash_params_temp.length;
            for(var i = 0; i < hash_params_count; i++) {
                var temp = hash_params_temp[i].split('=');
                hash_params[temp[0]] = decodeURIComponent(temp[1]);
            }
        }
        
        switch(hash_params.mode.toLowerCase()) {
            case 'addrec':
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                itemfuncs.openAddUserForm();
                break;
            case 'edit':
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                if(hash_params.hasOwnProperty('recid') && hash_params.recid != '') {
                    itemfuncs.openRecordForEditing(hash_params.recid);
                } else {
                    location.hash = itemfuncs.prev_page_hash;
                }
                break;
            default:
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                var params = {mode: 'getList', pno: 1, searchdata: "[]", sortdata: JSON.stringify(itemfuncs.sortparams), listformat: 'html'};
                
                if(hash_params.hasOwnProperty('pno')) {
                    params['pno'] = hash_params.pno;
                } else {
                    params['pno'] = 1;
                }
                
                if(hash_params.hasOwnProperty('searchdata')) {
                    params['searchdata'] = hash_params.searchdata;
                }
                
                if(hash_params.hasOwnProperty('sortdata')) {
                    params['sortdata'] = hash_params.sortdata;
                }
                
                itemfuncs.searchparams = JSON.parse(params['searchdata']);
                itemfuncs.sortparams = JSON.parse(params['sortdata']);
                
                if(itemfuncs.sortparams.length == 0) {
                    itemfuncs.sortparams.push(itemfuncs.default_sort);
                    params['sortdata'] = JSON.stringify(itemfuncs.sortparams);
                }
                
                if(itemfuncs.searchparams.length > 0) {
                    $.each(itemfuncs.searchparams, function(idx, data) {
                        switch(data.searchon) {
                            case 'name':
                                $("#search-field_name").val(data.searchtext);
                                break;
                            case 'unit':
                                $("#search-field_unit").val(data.searchtext);
                                break;
                        }
                    });
                }
                
                $("#common-processing-overlay").removeClass('d-none');
                common_js_funcs.callServer({
                    cache: 'no-cache',
                    async: true,
                    dataType: 'json',
                    type: 'post',
                    url: itemfuncs.ajax_data_script,
                    params: params,
                    successResponseHandler: itemfuncs.showList,
                    successResponseHandlerParams: {self: itemfuncs}
                });
                
                itemfuncs.showHidePanel('items_search_toggle');
        }
    }
}