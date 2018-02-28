define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'workinf/index',
                    add_url: 'workinf/add',
                    edit_url: 'workinf/edit',
                    del_url: 'workinf/del',
                    multi_url: 'workinf/multi',
                    table: 'work_inf',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                paginationVAlign: 'top',
                maintainSelected: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'oper_id', title: __('Oper_id'), operate: false},
                        {field: 'timestamp', title: __('Timestamp'), formatter: Table.api.formatter.datetime, operate: false, cellStyle: function () {return {css: {"min-width": "150px"}}}},
                        {field: 'product_id', title: __('Product_id')},
                        {field: 'BNet_Account', title: __('Bnet_account')},
                        {field: 'accNbr', title: __('Accnbr')},
                        {field: 'oper_type', title: __('Oper_type'), formatter: Table.api.formatter.opertype, searchList: {'newProd': __('NewProd'), 'modifyProdState': __('ModifyProdState'), 'modifyProdAttribute': __('ModifyProdAttribute'), 'cancelProd': __('CancelProd'), 'invalidProd': __('InvalidProd')}, style: 'min-width:100px;'},
                        {field: 'cust_code', cellStyle: function () {return {css: {"min-width": "200px"}}}, title: __('Cust_code')},
                        {field: 'contract_id', title: __('Contract_id')},
                        {field: 'cust_city_id', title: __('Cust_city_id'), operate: false},
                        {field: 'cust_install_addr', cellStyle: function () {return {css: {"min-width": "250px"}}}, title: __('Cust_install_addr'), operate: 'LIKE ...%', placeholder: '模糊搜索'},
                        {field: 'cust_name', cellStyle: function () {return {css: {"min-width": "200px"}}}, title: __('Cust_name'), operate: 'LIKE ...%', placeholder: '模糊搜索'},
                        {field: 'cust_phone', title: __('Cust_phone'), operate: false},
                        {field: 'contract_valid_date', title: __('Contract_valid_date'), operate: false},
                        {field: 'installer_name', title: __('Installer_name')},
                        {field: 'installer_phone', title: __('Installer_phone'), operate: false},
                        {field: 'product_mix', title: __('Product_mix'), formatter: Table.api.formatter.productmix},
                        {field: 'pay_grade', title: __('Pay_grade'), formatter: Table.api.formatter.paygrade},
                        {field: 'iTV_option', title: __('Itv_option'), formatter: Table.api.formatter.itvoption, searchList: {1:__('Standard Definition'), 2:__('High Definition')}},
                        {field: 'eTV_license_count', title: __('Etv_license_count')},
                        {field: 'iTV_count', title: __('Itv_count'), formatter: Table.api.formatter.itvcount},
                        {field: 'custom_fee', title: __('Custom_Fee'), formatter: Table.api.formatter.customfee},
                        {field: 'reply_status', title: __('Reply_status'), formatter: Table.api.formatter.replystatus, searchList: {'0': __('Noreceipt'), '1':__('Hadreceipt'), '2':__('Receipterror')}},
                        {field: 'complete_time', title: __('Complete_time'), formatter: Table.api.formatter.date, cellStyle: function () {return {css: {"min-width": "150px"}}}, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, cellStyle: function () {return {css: {"min-width": "150px"}}},
                            buttons: [
                                {name: 'detail', text: __('Detail'), classname: 'btn btn-xs btn-warning btn-detail btn-dialog', icon: 'fa fa-list', url: 'workinf/detail'}
                            ],
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                if (row.reply_status != "1") {
                                    return '<a val="/zxttms/public/api.php/Gdbnet/replyoper/operId/'+row.oper_id+' " class="btn btn-xs btn-success confirm_oper_button" title="回单">回单</a>';
                                }
                            }
                        }
                    ]
                ]
            });

            // 回单
            $(document).on('click', '.confirm_oper_button', function(event) {
                var $url = $(this).attr("val");
                $.ajax({
                    url: $url,
                    type: 'GET',
                    dataType: 'json',
                })
                .done(function(data) {
                    if (data['code'] == 200) {
                        alert(__('Operation completed'));
                    }else if(data['code'] == 0){
                        alert(__('Operation failed'));
                    }
                    $("#orderlistTab").find(".btn-refresh").trigger("click");//点击tab刷新
                })
                .fail(function(data) {
                    alert(__('Operation failed'));
                });
            });

            $(table).on('check.bs.table', function (e, row, element){// 选中加深颜色方法
                $(element).parent().parent().addClass('success');
            });
            $(table).on('uncheck.bs.table', function (e, row, element){// 不选去除加深样式
                $(element).parent().parent().removeClass('success');
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});