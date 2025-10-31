var customers = {
    searchparams: [], // Search parameters
    sortparams: [], // Sorting parameters
    default_sort: {sorton: 'name', sortorder: 'ASC'},
    paginationdata: {},
    defaultleadtabtext: 'Customers',
    filtersapplied: [],
    statuschangestarted: 0,
    ajax_data_script: 'customers.php', // Updated from company.php
    curr_page_hash: '',
    prev_page_hash: '',
    list_page_hash:'',
    name_pattern: /^[A-Z0-9_ -]+$/i,
    pp_max_filesize: 0,

    // Initialization method
    init: function() {
        // Any initialization logic
        $('.main-content').on('click', 'td.clickable-cell', {self: customers}, common_js_funcs.changeLocationWithDataProperty);
        $('.main-content').on('click', '.page-link', {self: customers}, customers.changePage);
        $('.main-content').on('click', '.toggle-search', {self: customers}, customers.toggleSearch);
        $('#recs-list>thead>tr>th.sortable').bind('click', {self: customers}, customers.sortTable);
        $('#rec_list_container').on('click', '.searched_elem .remove_filter', customers.clearSearch);
        if(CAN_DELETE) // attach delete handler only if the user has the delete right
            $('.main-content').on('click', '.record-delete-button', customers.deleteCustomer);
        $('.main-content').on('click', '#add-contact-btn', customers.addContactRow);
        $('.main-content').on('click', '.remove-contact', customers.removeContactRow);
        $(window).hashchange(customers.onHashChange);
        $(window).hashchange();


    },

    // Method to add a contact row dynamically
    addContactRow: function(contactData) {
        const contact = contactData || { id: '', name: '', department: '', designation: '', email: '', phone: '' };
        const contactTemplate = `
            <tr class="contact-row">
                <td data-label="Name *">
                    <input type="hidden" name="contact_id[]" class="contact-id" value="${contact.id || ''}">
                    <input type="text" class="form-control contact-name" name="contact_name[]" value="${contact.name || ''}" placeholder="Name" required>
                </td>
                <td data-label="Department">
                    <input type="text" class="form-control contact-department" name="contact_department[]" value="${contact.department || ''}" placeholder="Department">
                </td>
                <td data-label="Designation">
                    <input type="text" class="form-control contact-designation" name="contact_designation[]" value="${contact.designation || ''}" placeholder="Designation">
                </td>
                <td data-label="Email">
                    <input type="email" class="form-control contact-email" name="contact_email[]" value="${contact.email || ''}" placeholder="Email">
                </td>
                <td data-label="Phone">
                    <input type="tel" class="form-control contact-phone" name="contact_phone[]" value="${contact.phone || ''}" placeholder="Phone">
                </td>
                <td data-label="Actions">
                    <button type="button" class="btn btn-danger remove-contact rounded">Remove</button>
                </td>
            </tr>
        `;
        /*const contactTemplate = `
            <tr class="contact-row">
                <td>
                    <input type="hidden" name="contact_id[]" class="contact-id" value="${contact.id || ''}">
                    <input type="text" class="form-control contact-name" name="contact_name[]" value="${contact.name || ''}" placeholder="Name" required>
                </td>
                <td>
                    <input type="text" class="form-control contact-department" name="contact_department[]" value="${contact.department || ''}" placeholder="Department">
                </td>
                <td>
                    <input type="text" class="form-control contact-designation" name="contact_designation[]" value="${contact.designation || ''}" placeholder="Designation">
                </td>
                <td>
                    <input type="email" class="form-control contact-email" name="contact_email[]" value="${contact.email || ''}" placeholder="Email">
                </td>
                <td>
                    <input type="tel" class="form-control contact-phone" name="contact_phone[]" value="${contact.phone || ''}" placeholder="Phone">
                </td>
                <td>
                    <button type="button" class="btn btn-danger remove-contact rounded">Remove</button>
                </td>
            </tr>
        `;*/
        $('#contacts-table tbody').append(contactTemplate);
    },

    // Method to remove a contact row
    removeContactRow: function(event) {
        $(event.target).closest('.contact-row').remove();
    },

    // Toggle search panel visibility
    toggleSearch: function(ev) {
        customers.setPanelVisibilityStatus('customer_search_toggle', 
            $(ev.currentTarget).hasClass('search-form-visible') ? '' : 'visible');
        customers.showHidePanel('customer_search_toggle');
    },

    // Set panel visibility status in local storage or cookies
    setPanelVisibilityStatus: function(panel, status) {
        if (typeof(Storage) !== "undefined") {
            localStorage[panel] = status;
        } else {
            Cookies.set(panel, status, {path : '/'});
        }
    },

    // Show or hide search panel
    showHidePanel: function(panel) {
        if(panel === 'customer_search_toggle') {
            let show_srch_form = false;
            if (typeof(Storage) !== "undefined") {
                srch_frm_visible = localStorage.customer_search_toggle;
            } else {
                srch_frm_visible = Cookies.get('customer_search_toggle');
            }
            
            if(srch_frm_visible && srch_frm_visible == 'visible')
                show_srch_form = true;
            
            $('.toggle-search').toggleClass('search-form-visible', show_srch_form);
            $('#search_records').closest('.panel-search').toggleClass('d-none', !show_srch_form);
            
            let search_form_cont = $('#search_records').closest('.panel-search');
            if(search_form_cont.hasClass('d-none'))
                $('.toggle-search').prop('title','Open search panel');
            else {
                $('.toggle-search').prop('title','Close search panel');
                $("#search-field_name").focus(); // Updated from comp_name
            }
        }
    },


    getList:function(options) {
        var self = customers;
        var pno = 1;
        var params = [];
        
        if ('pno' in options) {
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

    showList:function(resp, otherparams) {
        var self = customers;
        var listhtml = resp[1].list;
        self.user_count = resp[1]['reccount'];
        
        $("#rec_list_container").removeClass('d-none');
        $("#rec_detail_add_edit_container").addClass('d-none');
        $("#common-processing-overlay").addClass('d-none');
        
        $("#userlistbox").html(listhtml);
        
        if (resp[1].tot_rec_cnt > 0) {
            $('#heading_rec_cnt').text(
                (resp[1]['reccount'] == resp[1]['tot_rec_cnt']) 
                ? `(${resp[1]['tot_rec_cnt']})` 
                : `(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`
            );
            customers.setExportLink(common_js_funcs.is_admin && resp[1]['reccount'] > 0 ? true : false);
        } else {
            $('#heading_rec_cnt').text('(0)');
            customers.setExportLink(false);
        }
        
        $("#add-record-button").removeClass('d-none');
        $("#refresh-list-button").removeClass('d-none');
        $(".back-to-list-button").addClass('d-none').attr('href', "customers.php#"+customers.curr_page_hash);
        $("#edit-record-button").addClass('d-none');
        
        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },

    onListRefresh: function(resp, otherparams) {
        var self = customers;
        $("#common-processing-overlay").addClass('d-none');
        var listhtml = resp[1].list;
        $("#userlistbox").html(listhtml);
        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },

    setExportLink:function(show) {
        const dnld_elem = $('#export_customers');
        if (dnld_elem.length <= 0) 
            return;
        
        let url = '#';
        if (show === true) {
            let params = [];
            params.push('mode=export');
            params.push('searchdata=' + encodeURIComponent(JSON.stringify(customers.searchparams)));
            params.push('sortdata=' + encodeURIComponent(JSON.stringify(customers.sortparams)));
            params.push('ref=' + Math.random());
            url = `${window.location.origin}${window.location.pathname}?${params.join('&')}`;
        }
        
        dnld_elem.attr('href', url).toggleClass('d-none', show !== true);
    },

    changePage: function(ev) {
        ev.preventDefault();
        if (!$(ev.currentTarget).parent().hasClass('disabled')) {
            var self = customers;
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
        
        if ($(e.currentTarget).find("i:eq(0)").hasClass('fa-sort-up'))
            sortorder = 'DESC';
        
        customers.sortparams = [];
        customers.sortparams.push({sorton: sorton, sortorder: sortorder});
        
        var options = {pno: 1};
        customers.getList(options);
    },

    setSortOrderIcon: function() {
        var self = customers;
        if (self.sortparams.length > 0) {
            var sorton = self.sortparams[0].sorton.replaceAll('_', '-');
            var colheaderelemid = 'colheader_' + sorton;
            
            if (self.sortparams[0].sortorder == 'DESC') {
                var sort_order_class = 'fa-sort-down';
            } else {
                var sort_order_class = 'fa-sort-up';
            }
            
            $("#" + colheaderelemid)
                .siblings('th.sortable')
                .removeClass('sorted-col')
                .find('i:eq(0)')
                .removeClass('fa-sort-down fa-sort-up')
                .addClass('fa-sort')
                .end()
                .end()
                .addClass('sorted-col')
                .find('i:eq(0)')
                .removeClass('fa-sort-down fa-sort-up')
                .addClass(sort_order_class);
        }
    },

    resetSearchParamsObj: function() {
        customers.searchparams = [];
    },

    setSearchParams: function(obj) {
        customers.searchparams.push(obj);
    },

    clearSearch: function(e) {
        let remove_all = true;
        if (e) {
            e.stopPropagation();
            elem = e.currentTarget;
            if ($(elem).hasClass('remove_filter')) {
                remove_all = $(elem).data('fld');
                $(elem).parent('.searched_elem').remove();
                $('.panel-search .srchfld[data-fld=' + remove_all + ']').val('');
            }
        }

        if (remove_all === true) {
            customers.resetSearchParamsObj();
            document.search_form.reset();
        } else {
            customers.searchparams = customers.searchparams.filter(fltr => {
                return fltr.searchon !== remove_all;
            });
        }

        var options = {pno: 1};
        customers.getList(options);
        return false;
    },

    doSearch: function() {
        customers.resetSearchParamsObj();
        let fld = '';
        $('.panel-search .srchfld').each(function(i, el) {
            let val = $.trim($(el).val());
            if (val != '') {
                fld = $(el).data('fld');
                customers.setSearchParams({
                    searchon: $(el).data('fld'),
                    searchtype: $(el).data('type'),
                    searchtext: val
                });
            }
        });

        if (customers.searchparams.length <= 0)
            return false;

        var options = {pno: 1};
        customers.getList(options);
        return false;
    },

    openRecordForViewing: function(recordid) {
        if (recordid == '')
            return false;

        $("#record-save-button").addClass('d-none').attr('disabled', 'disabled');
        $("#common-processing-overlay").removeClass('d-none');
        
        var options = {
            mode: 'viewrecord', 
            recordid: recordid, 
            loadingmsg: "Opening the customer '" + recordid + "' for viewing...",
            leadtabtext: 'View Customer Details'
        };
        
        customers.openRecord(options);
        return false;
    },

    openRecordForEditing: function(recordid) {
        if (recordid == '')
            return false;

        document.addrecform.reset();
        $(".form-control").removeClass("error-field");
        
        $("#record-save-button").removeClass('d-none').attr('disabled', false);
        $("#common-processing-overlay").removeClass('d-none');
        $("#record-add-cancel-button").attr('href', "customers.php#" + customers.prev_page_hash);
        $('#msgFrm').removeClass('d-none');
        
        var options = {
            mode: 'editrecord', 
            recordid: recordid, 
            leadtabtext: 'Edit Customer'
        };
        
        customers.openRecord(options);
        return false;
    },

    openRecord: function(options) {
        var opts = {leadtabtext: 'Customer Details'};
        $.extend(true, opts, options);
        
        var params = {
            mode: "getRecordDetails", 
            recordid: opts.recordid
        };
        
        var ajaxOptions = {
            cache: 'no-cache',
            async: true,
            type: 'post',
            dataType: 'json',
            url: customers.ajax_data_script,
            params: params,
            successResponseHandler: customers.showLeadDetailsWindow,
            successResponseHandlerParams: {
                self: this,
                mode: opts.mode,
                recordid: opts.recordid,
                header_bar_text: opts.leadtabtext
            }
        };
        
        common_js_funcs.callServer(ajaxOptions);
    },


    
    showLeadDetailsWindow: function(resp, otherparams) {
        if (resp[0] != 0) {
            alert('Error loading customer details. Please try again.');
            $("#common-processing-overlay").addClass('d-none');
            return false;
        }
        
        var recorddetails = resp[1].record_details;
        var contobj = $("#rec_detail_add_edit_container");
        
        // Clear existing contacts
        $('#contacts-table tbody').empty();
        
        // Update form fields
        contobj.find('#add_comp_name').val(recorddetails.name);
        contobj.find('#add_mem_cat_id').val(recorddetails.customer_group_id);
        contobj.find('#add_sector_id').val(recorddetails.industry_id);
        contobj.find('#add_comp_address_1').val(recorddetails.address_1);
        contobj.find('#add_comp_address_2').val(recorddetails.address_2);
        contobj.find('#add_comp_address_3').val(recorddetails.address_3);
        contobj.find('#add_comp_city').val(recorddetails.city);
        contobj.find('#add_comp_state').val(recorddetails.state);
        contobj.find('#add_comp_pin').val(recorddetails.pin);
        contobj.find('#add_website').val(recorddetails.website);
        contobj.find('#add_business_details').val(recorddetails.business_details);
        contobj.find('input[name=active][value=' + recorddetails.active + ']').prop('checked', true);
        
        // Add existing contacts with IDs
        if (recorddetails.contacts && recorddetails.contacts.length > 0) {
            recorddetails.contacts.forEach(function(contact) {
                customers.addContactRow(contact);
            });
        }
        
        $("#refresh-list-button").addClass('d-none');
        $("#add-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_add_edit_container").removeClass('d-none');
        
        $("#panel-heading-text").text('Edit Customer');
        $("#record-save-button span").text('Update Customer');
        $("#add_edit_mode").val('updaterec');
        $("#add_edit_recordid").val(recorddetails.id);
        
        $(".back-to-list-button").removeClass('d-none');
        
        $("#common-processing-overlay").addClass('d-none');
        
        document.querySelector('.main-content').scrollIntoView(true);
    },


    openAddUserForm: function(e) {
        document.addrecform.reset();
        customers.removeEditRestrictions();
        
        $(".form-control").removeClass("error-field");
        $("#refresh-list-button").addClass('d-none');
        $("#add-record-button").addClass('d-none');
        $("#edit-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_add_edit_container").removeClass('d-none')
            .find("#panel-heading-text").text('Add Customer').end();
        
        $('#msgFrm').removeClass('d-none');
        $(".back-to-list-button").removeClass('d-none');
        
        $("#rec_detail_add_edit_container")
            .find("#record-save-button>span:eq(0)").html('Add Customer').end()
            .find("#add_edit_mode").val('createrec').end()
            .find("#add_edit_recordid").val('').end()
            .find("#record-add-cancel-button").data('back-to','').attr('href', "customers.php#" + customers.prev_page_hash);
        
        $("form[name=addrecform]").data('mode','add-rec')
            .find(".error-field").removeClass('error-field').end()
            .find('input[name=active]').prop('checked',false).end()
            .get(0).reset();
        
        $('.addonly').removeClass('d-none');
        $('.editonly').addClass('d-none');

        $('#contacts-table > tbody').empty();
        
        var contobj = $("#rec_detail_add_edit_container");
        contobj.find('#add_comp_name,#add_sector_id,#add_comp_address_1,#add_comp_address_2,#add_comp_address_3,#add_comp_city,#add_comp_state,#add_comp_pin,#add_website')
            .val('')
            .prop('disabled',false);
        
        contobj.find("#add_comp_name").focus();
        document.querySelector('.main-content').scrollIntoView(true);
        
        return false;
    },

    validateRecDetails: function(opts) {
        $(".form-control").removeClass("error-field");
        
        let mode = opts.mode ?? 'add-rec';
        
        let customer_name = $('#add_comp_name').val().trim();
        let industry_id = $('#add_sector_id').val();
        let customer_group_id = $('#add_mem_cat_id').val();
        let address_1 = $('#add_comp_address_1').val().trim();
        let city = $('#add_comp_city').val().trim();
        let state = $('#add_comp_state').val();
        let pin = $('#add_comp_pin').val().trim();
        let website = $('#add_website').val().trim();
        let active = $('input[name=active]:checked').val();
        let contact_name0 = $('input[name=active]:checked').val();
        
        if(customer_name == ''){
            $('#add_comp_name').addClass("error-field");
            return {'error_msg': 'Customer name is required.', 'error_field': $('#add_comp_name')};
        }
        
        if(customer_group_id == ''){
            $('#add_mem_cat_id').addClass("error-field");
            return {'error_msg': 'Please select a customer group.', 'error_field': $('#add_mem_cat_id')};
        }
        
        if(pin && !/^\d{3}\s?\d{3}$/.test(pin)){
            $('#add_comp_pin').addClass("error-field");
            return {'error_msg': 'Please enter a valid PIN.', 'error_field': $('#add_comp_pin')};
        }
        
        if(website && !/^https?\:\/\//.test(website)){
            $('#add_website').addClass("error-field");
            return {'error_msg': 'Please enter a valid website URL.', 'error_field': $('#add_website')};
        }
        
        if(!active){
            $('input[name=active][value=y]').addClass("error-field");
            return {'error_msg': 'Please select active status.', 'error_field': $('input[name=active][value=y]')};
        }
        
        // Contact validation
        return customers.validateContacts();
    },

    validateContacts: function() {
        let contactsValid = true;
        let contactErrors = [];
        
        $('.contact-row').each(function(index) {
            let name = $(this).find('.contact-name').val().trim();
            let email = $(this).find('.contact-email').val().trim();
            let phone = $(this).find('.contact-phone').val().trim();
            
            // Name is mandatory
            if(name === ''){
                $(this).find('.contact-name').addClass('error-field');
                contactErrors.push('Contact name is required.');
                contactsValid = false;
                return false;
            }
            
            // Email validation if provided
            if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
                $(this).find('.contact-email').addClass('error-field');
                contactErrors.push('Invalid email format.');
                contactsValid = false;
                return false;
            }
            
            // Phone validation if provided
            if(phone && !/^[0-9]{10}$/.test(phone)){
                $(this).find('.contact-phone').addClass('error-field');
                contactErrors.push('Phone number must be 10 digits.');
                contactsValid = false;
                return false;
            }
        });
        
        return contactsValid ? 
            {'error_msg': null, 'error_field': null} : 
            {
                'error_msg': contactErrors[0], 
                'error_field': $('.contact-name.error-field:first')
            };
    },

    saveRecDetails: function(formelem) {
        var self = this;
        var data_mode = $(formelem).data('mode');
        
        var res = self.validateRecDetails({mode: data_mode});
        if(res.error_field){
            alert(res.error_msg);
            setTimeout(function(){
                $(res.error_field).focus();
            }, 0);
            return false;
        }
        
        $("#common-processing-overlay").removeClass('d-none');
        $('#record-save-button, #record-add-cancel-button')
            .addClass('disabled')
            .attr('disabled', true);
        
        $('#rec_detail_add_edit_container .error-field').removeClass('error-field');
        
        return true;
    },

    handleAddRecResponse: function(resp) {
        $(".form-control").removeClass("error-field");
        
        if(resp.error_code == 0){
            var message_container = '.alert-success';
            $("#record-add-cancel-button>i:eq(0)").next('span').html('Close');
            $("form[name=addrecform]").find(".error-field").removeClass('error-field').end().get(0).reset();
            $('#contacts-table > tbody').empty();
            $("#add_comp_name").focus();
            document.querySelector('.main-content').scrollIntoView(true);
        } else if(resp.error_code == 2){
            var message_container = '';
            if(resp.error_fields.length > 0){
                var msg = resp.message;
                alert(msg);
                $(resp.error_fields[0]).focus();
                $(resp.error_fields[0]).addClass("error-field");
            }
        } else {
            var message_container = '.alert-danger';
        }
        
        $('#record-save-button, #record-add-cancel-button')
            .removeClass('disabled')
            .attr('disabled', false);
        
        $("#common-processing-overlay").addClass('d-none');
        
        if(message_container != ''){
            $(message_container)
                .removeClass('d-none')
                .siblings('.alert')
                .addClass('d-none')
                .end()
                .find('.alert-message')
                .html(resp.message);
            
            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
            $('#msgFrm').addClass('d-none');
        }
    },

    handleUpdateRecResponse: function(resp) {
        $(".form-control").removeClass("error-field");
        
        var mode_container = 'rec_detail_add_edit_container';
        
        if(resp.error_code == 0){
            var message_container = '.alert-success';
            $("#add_comp_name").focus();
        } else if(resp.error_code == 2){
            var message_container = '';
            if(resp.error_fields.length > 0){
                alert(resp.message);
                setTimeout(() => {
                    $(resp.error_fields[0]).addClass("error-field").focus();
                }, 0);
            }
        } else {
            var message_container = '.alert-danger';
        }
        
        $('#record-save-button, #record-add-cancel-button')
            .removeClass('disabled')
            .attr('disabled', false);
        
        $("#common-processing-overlay").addClass('d-none');
        
        if(message_container != ''){
            $(message_container)
                .removeClass('d-none')
                .siblings('.alert')
                .addClass('d-none')
                .end()
                .find('.alert-message')
                .html(resp.message);
            
            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
            $('#msgFrm').addClass('d-none');
        }
    },

    removeEditRestrictions: function() {
        const contobj = $("#rec_detail_add_edit_container");
        contobj.find(".form-control")
            .prop('disabled', false)
            .removeClass('rstrctedt');
    },

    deleteCustomer: function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var recordid = $(e.currentTarget).data('recid');
        if (!confirm('Are you sure you want to delete this customer?')) {
            return false;
        }
        
        $("#common-processing-overlay").removeClass('d-none');
        
        var params = {
            mode: 'deleterec',
            recordid: recordid
        };
        
        var ajaxOptions = {
            cache: 'no-cache',
            async: true,
            type: 'post',
            dataType: 'json',
            url: customers.ajax_data_script,
            params: params,
            successResponseHandler: function(resp) {
                $("#common-processing-overlay").addClass('d-none');
                if (resp.error_code == 0) {
                    customers.getList({pno: 1});
                } else {
                    alert(resp.message || 'Error deleting customer');
                }
            }
        };
        
        common_js_funcs.callServer(ajaxOptions);
        return false;
    },

    setheaderBarText: function(text) {
        $("#header-bar-text").find(":first-child").html(text);
    },

    // Add any remaining methods if needed


    onHashChange: function(e) {
        var hash = location.hash.replace(/^#/, '');
        
        if (customers.curr_page_hash != customers.prev_page_hash) {
            customers.prev_page_hash = customers.curr_page_hash;
        }
        customers.curr_page_hash = hash;
        
        var hash_params = {mode: ''};
        if (hash != '') {
            var hash_params_temp = hash.split('&');
            var hash_params_count = hash_params_temp.length;
            
            for (var i = 0; i < hash_params_count; i++) {
                var temp = hash_params_temp[i].split('=');
                hash_params[temp[0]] = decodeURIComponent(temp[1]);
            }
        }
        
        switch (hash_params.mode.toLowerCase()) {
            case 'addrec':
                if(!CAN_ADD)
                    window.history.pushState({}, "", CONST_APP_ABSURL+ customers.ajax_data_script +'#'+customers.list_page_hash);
                else{
                    $('.alert-success, .alert-danger').addClass('d-none');
                    $('#msgFrm').removeClass('d-none');
                    customers.openAddUserForm();
                }
                break;
            
            case 'edit':
                if(!CAN_EDIT)
                    window.history.pushState({}, "", CONST_APP_ABSURL+ customers.ajax_data_script +'#'+customers.list_page_hash);
                else{
                    $('.alert-success, .alert-danger').addClass('d-none');
                    $('#msgFrm').removeClass('d-none');

                    if (hash_params.hasOwnProperty('recid') && hash_params.recid != '') {
                        customers.openRecordForEditing(hash_params.recid);
                    } else {
                        location.hash = customers.prev_page_hash;
                    }
                }
                break;
            
            default:
                if(hash_params.mode==='')
                    customers.list_page_hash = hash;
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                
                var params = {
                    mode: 'getList',
                    pno: 1, 
                    searchdata: "[]", 
                    sortdata: JSON.stringify(customers.sortparams), 
                    listformat: 'html'
                };
                
                if (hash_params.hasOwnProperty('pno')) {
                    params['pno'] = hash_params.pno;
                }
                
                if (hash_params.hasOwnProperty('searchdata')) {
                    params['searchdata'] = hash_params.searchdata;
                }
                
                if (hash_params.hasOwnProperty('sortdata')) {
                    params['sortdata'] = hash_params.sortdata;
                }
                
                customers.searchparams = JSON.parse(params['searchdata']);
                customers.sortparams = JSON.parse(params['sortdata']);
                
                if (customers.sortparams.length == 0) {
                    customers.sortparams.push(customers.default_sort);
                    params['sortdata'] = JSON.stringify(customers.sortparams);
                }
                
                if (customers.searchparams.length > 0) {
                    $.each(customers.searchparams, function(idx, data) {
                        switch (data.searchon) {
                            case 'name':
                                $("#search-field_name").val(data.searchtext);
                                break;
                            case 'industry_name':
                                $("#search-field_industry_name").val(data.searchtext);
                                break;
                            case 'customer_group_name':
                                $("#search-field_customer_group_name").val(data.searchtext);
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
                    url: customers.ajax_data_script,
                    params: params,
                    successResponseHandler: customers.showList,
                    successResponseHandlerParams: {self: this}
                });
                
                customers.showHidePanel('customer_search_toggle');
        }
    },

};

// Expose methods globally or for module loading
// export default customers;