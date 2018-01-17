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
                        {field: 'iTV_option', title: __('Itv_option')},
                        {field: 'eTV_license_count', title: __('Etv_license_count')},
                        {field: 'iTV_count', title: __('Itv_count')},
                        {field: 'reply_status', title: __('Reply_status'), formatter: Table.api.formatter.replystatus, searchList: {'0': __('Noreceipt'), '1':__('Hadreceipt'), '2':__('Receipterror')}},
                        {field: 'complete_time', title: __('Complete_time'), operate: 'RANGE', addclass: 'datetimerange', operate: false},
                        
                        {field: 'hashcode', title: __('Hashcode')},
                        {field: 'query_status', title: __('Query_status'), formatter: Table.api.formatter.status},
                        {field: 'setup_person_name', title: __('Setup_person_name')},
                        {field: 'setup_person_phone', title: __('Setup_person_phone')},
                        {field: 'callback_time', title: __('Callback_time')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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