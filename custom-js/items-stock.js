var itemsStock = {
    searchparams: [], // Search parameters
    sortparams: [], // Sorting parameters
    default_sort: {sorton: 'warehouse_name', sortorder: 'ASC'},
    paginationdata: {},
    defaultleadtabtext: 'Items Stock',
    filtersapplied: [],
    ajax_data_script: 'items-stock.php',
    curr_page_hash: '',
    prev_page_hash: '',
    max_allowed_size: {},

    // Initialization method
    init: function() {
        // Bind event handlers
        $('.main-content').on('click', '.page-link', {self: itemsStock}, itemsStock.changePage);
        $('.main-content').on('click', '.toggle-search', {self: itemsStock}, itemsStock.toggleSearch);
        $('#recs-list>thead>tr>th.sortable').bind('click', {self: itemsStock}, itemsStock.sortTable);
        $('#rec_list_container').on('click', '.searched_elem .remove_filter', itemsStock.clearSearch);
        $('#csv_file').on('change', itemsStock.onFileSelected);
        $(window).hashchange(itemsStock.onHashChange);
        $(window).hashchange();
    },

    // Toggle search panel visibility
    toggleSearch: function(ev) {
        itemsStock.setPanelVisibilityStatus('stock_search_toggle',
            $(ev.currentTarget).hasClass('search-form-visible') ? '' : 'visible');
        itemsStock.showHidePanel('stock_search_toggle');
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
        if(panel === 'stock_search_toggle') {
            let show_srch_form = false;
            if (typeof(Storage) !== "undefined") {
                srch_frm_visible = localStorage.stock_search_toggle;
            } else {
                srch_frm_visible = Cookies.get('stock_search_toggle');
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
                $("#search-field_warehouse_id").focus();
            }
        }
    },

    getList: function(options) {
        var self = itemsStock;
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

    showList: function(resp, otherparams) {
        var self = itemsStock;
        var listhtml = resp[1].list;
        self.record_count = resp[1]['reccount'];

        $("#rec_list_container").removeClass('d-none');
        $("#rec_import_container").addClass('d-none');
        $("#common-processing-overlay").addClass('d-none');

        $("#stocklistbox").html(listhtml);

        if (resp[1].tot_rec_cnt > 0) {
            $('#heading_rec_cnt').text(
                (resp[1]['reccount'] == resp[1]['tot_rec_cnt'])
                    ? `(${resp[1]['tot_rec_cnt']})`
                    : `(${resp[1]['reccount'] || 0} of ${resp[1]['tot_rec_cnt']})`
            );
        } else {
            $('#heading_rec_cnt').text('(0)');
        }

        $("#import-records-button").removeClass('d-none');
        $(".back-to-list-button").addClass('d-none').attr('href', "items-stock.php#"+itemsStock.curr_page_hash);

        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },

    changePage: function(ev) {
        ev.preventDefault();
        if (!$(ev.currentTarget).parent().hasClass('disabled')) {
            var self = itemsStock;
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

        itemsStock.sortparams = [];
        itemsStock.sortparams.push({sorton: sorton, sortorder: sortorder});

        var options = {pno: 1};
        itemsStock.getList(options);
    },

    setSortOrderIcon: function() {
        var self = itemsStock;
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
        itemsStock.searchparams = [];
    },

    setSearchParams: function(obj) {
        itemsStock.searchparams.push(obj);
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
            itemsStock.resetSearchParamsObj();
            document.search_form.reset();
        } else {
            itemsStock.searchparams = itemsStock.searchparams.filter(fltr => {
                return fltr.searchon !== remove_all;
            });
        }

        var options = {pno: 1};
        itemsStock.getList(options);
        return false;
    },

    doSearch: function() {
        itemsStock.resetSearchParamsObj();
        let fld = '';
        $('.panel-search .srchfld').each(function(i, el) {
            let val = $.trim($(el).val());
            if (val != '') {
                fld = $(el).data('fld');
                itemsStock.setSearchParams({
                    searchon: $(el).data('fld'),
                    searchtype: $(el).data('type'),
                    searchtext: val
                });
            }
        });

        if (itemsStock.searchparams.length <= 0)
            return false;

        var options = {pno: 1};
        itemsStock.getList(options);
        return false;
    },

    openImportForm: function(e) {
        $("#rec_list_container").addClass('d-none');
        $("#rec_import_container").removeClass('d-none');
        $("#import-records-button").addClass('d-none');
        $(".back-to-list-button").removeClass('d-none');

        // Reset form
        document.importform.reset();
        $(".form-control").removeClass("error-field");
        $('.alert-success, .alert-danger').addClass('d-none');
        $('#csv_file').siblings('.file-info').remove();
        $('#msgFrm').removeClass('d-none');

        document.querySelector('.main-content').scrollIntoView(true);
        return false;
    },

    validateImportForm: function() {
        $(".form-control").removeClass("error-field");

        let warehouse_id = $('#import_warehouse_id').val();
        let file_input = $('#csv_file')[0].files[0];

        if (!warehouse_id) {
            $('#import_warehouse_id').addClass("error-field");
            return {'error_msg': 'Please select a warehouse.', 'error_field': $('#import_warehouse_id')};
        }

        if (!file_input) {
            $('#csv_file').addClass("error-field");
            return {'error_msg': 'Please select a file.', 'error_field': $('#csv_file')};
        }

        // Updated file type validation
        const fileName = file_input.name.toLowerCase();
        const allowedExtensions = ['.csv', '.xlsx', '.xls'];
        const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

        if (!isValidExtension) {
            $('#csv_file').addClass("error-field");
            return {'error_msg': 'Please select a valid CSV or Excel file (.csv, .xlsx, .xls).', 'error_field': $('#csv_file')};
        }

        // Updated file size validation - stricter limits for Excel
        let maxSize, maxSizeText;
        if (fileName.endsWith('.csv')) {
            maxSize = itemsStock.max_allowed_size.CSV.bytes // 10485760; // 10MB in bytes, for CSV
            maxSizeText = itemsStock.max_allowed_size.CSV.disp;
        } else {
            maxSize = itemsStock.max_allowed_size.EXCEL.bytes; // 8MB in bytes for Excel (reduced due to memory overhead)
            maxSizeText = itemsStock.max_allowed_size.EXCEL.disp;
        }

        if (file_input.size > maxSize) {
            $('#csv_file').addClass("error-field");
            const currentSizeMB = (file_input.size / (1048576)).toFixed(2); // 1024 x 1024 = 1048576
            let errorMsg = `File size exceeds ${maxSizeText} limit. Current size: ${currentSizeMB}MB.`;

            if (!fileName.endsWith('.csv')) {
                errorMsg += ' Consider using CSV format for larger files.';
            }

            return {'error_msg': errorMsg, 'error_field': $('#csv_file')};
        }

        return {'error_msg': null, 'error_field': null};
    },

    getFileTypeInfo: function(fileName) {
        const extension = fileName.toLowerCase().substring(fileName.lastIndexOf('.'));

        switch(extension) {
            case '.csv':
                return {type: 'CSV', maxSize: itemsStock.max_allowed_size.CSV.disp, icon: 'fa-file-text-o'};
            case '.xlsx':
                return {type: 'Excel (XLSX)', maxSize: itemsStock.max_allowed_size.EXCEL.disp, icon: 'fa-file-excel-o'};
            case '.xls':
                return {type: 'Excel (XLS)', maxSize: itemsStock.max_allowed_size.EXCEL.disp, icon: 'fa-file-excel-o'};
            default:
                return {type: 'Unknown', maxSize: 'N/A', icon: 'fa-file-o'};
        }
    },

    onFileSelected: function(e) {
        const fileInput = e.target;
        const file = fileInput.files[0];

        if (file) {
            const fileInfo = itemsStock.getFileTypeInfo(file.name);
            const fileSizeMB = (file.size / (1048576)).toFixed(2); // 1024 x 1024 = 1048576

            // You can add UI feedback here, for example:
            // console.log(`Selected: ${file.name} (${fileInfo.type}, ${fileSizeMB}MB)`);

            // Optional: Show file info to user
            const infoHtml = `<small class="text-info">
            <i class="${fileInfo.icon}"></i> 
            ${file.name} (${fileInfo.type}, ${fileSizeMB}MB of ${fileInfo.maxSize} max)
        </small>`;

            // Remove any existing info and add new info
            $('#csv_file').siblings('.file-info').remove();
            $('#csv_file').after(`<div class="file-info mt-1">${infoHtml}</div>`);
        }
    },

    importCSV: function(formelem) {
        var self = this;

        var res = self.validateImportForm();
        if(res.error_field){
            alert(res.error_msg);
            setTimeout(function(){
                $(res.error_field).focus();
            }, 0);
            return false;
        }

        $("#common-processing-overlay").removeClass('d-none');
        $('#import-button').addClass('disabled').attr('disabled', true);
        $('#rec_import_container .error-field').removeClass('error-field');

        // Create FormData object for file upload
        var formData = new FormData();
        formData.append('mode', 'importCSV');
        formData.append('warehouse_id', $('#import_warehouse_id').val());
        formData.append('csv_file', $('#csv_file')[0].files[0]);

        // AJAX call for file upload
        $.ajax({
            url: itemsStock.ajax_data_script,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                itemsStock.handleImportResponse(response);
            },
            error: function(xhr, status, error) {
                $("#common-processing-overlay").addClass('d-none');
                $('#import-button').removeClass('disabled').attr('disabled', false);
                alert('Error uploading file. Please try again.');
            }
        });

        return false;
    },

    handleImportResponse: function(resp) {
        $(".form-control").removeClass("error-field");

        if(resp.error_code == 0){
            var message_container = '.alert-success';
            document.importform.reset();
            $("#import_warehouse_id").focus();
            $('#csv_file').siblings('.file-info').remove();
            document.querySelector('.main-content').scrollIntoView(true);
        } else if(resp.error_code == 2){
            var message_container = '';
            if(resp.error_fields && resp.error_fields.length > 0){
                var msg = resp.message;
                alert(msg);
                $(resp.error_fields[0]).focus();
                $(resp.error_fields[0]).addClass("error-field");
            }
        } else {
            var message_container = '.alert-danger';
        }

        $('#import-button').removeClass('disabled').attr('disabled', false);
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

    onHashChange: function(e) {
        var hash = location.hash.replace(/^#/, '');

        if (itemsStock.curr_page_hash != itemsStock.prev_page_hash) {
            itemsStock.prev_page_hash = itemsStock.curr_page_hash;
        }
        itemsStock.curr_page_hash = hash;

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
            case 'import':
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                itemsStock.openImportForm();
                break;

            default:
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');

                var params = {
                    mode: 'getList',
                    pno: 1,
                    searchdata: "[]",
                    sortdata: JSON.stringify(itemsStock.sortparams),
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

                itemsStock.searchparams = JSON.parse(params['searchdata']);
                itemsStock.sortparams = JSON.parse(params['sortdata']);

                if (itemsStock.sortparams.length == 0) {
                    itemsStock.sortparams.push(itemsStock.default_sort);
                    params['sortdata'] = JSON.stringify(itemsStock.sortparams);
                }

                if (itemsStock.searchparams.length > 0) {
                    $.each(itemsStock.searchparams, function(idx, data) {
                        switch (data.searchon) {
                            case 'warehouse_id':
                                $("#search-field_warehouse_id").val(data.searchtext);
                                break;
                            case 'item_name':
                                $("#search-field_item_name").val(data.searchtext);
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
                    url: itemsStock.ajax_data_script,
                    params: params,
                    successResponseHandler: itemsStock.showList,
                    successResponseHandlerParams: {self: this}
                });

                itemsStock.showHidePanel('stock_search_toggle');
        }
    }
};