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
                    detail_url: 'workinf/detail',
                    replyoper_url: 'workinf/replyoper',
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
                singleSelect    : true, 
                showExport: false, 
                exportDataType:'all',
                exportTypes:['excel'],  //导出文件类型
                exportOptions:{
                   ignoreColumn: [0,1,25,26,27],  //忽略某一列的索引  
                   fileName: '工单列表',  //文件名称设置  
                },
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
                        {field: 'cust_install_addr', cellStyle: function () {return {css: {"min-width": "250px"}}}, title: __('Cust_install_addr'), operate: 'LIKE %...%', placeholder: '模糊搜索'},
                        {field: 'cust_name', cellStyle: function () {return {css: {"min-width": "200px"}}}, title: __('Cust_name'), operate: 'LIKE %...%', placeholder: '模糊搜索'},
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
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.workinfoperate, cellStyle: function () {return {css: {"min-width": "200px"}}},
                        }
                    ]
                ]
            });

            // 回单
            $(document).on('click', '.btn-replyoper', function(event) {
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

            $(document).on('click','.btn-refresh', function(event){
                $.ajax({
                    url: 'workinf/checklogin',
                    type: 'GET',
                    dataType: 'json',
                    data: {'d': 'value1'},
                    success:function(data){
                        if(data['code'] == 0){
                            location.reload();
                        }
                    },
                    error:function(){
                        alert('刷新失败');
                    }
                })
            });

            // 自定义导出
            $(document).on('click','.btn-myexcelout', function(event){
                var data = $(':text, select').serializeArray();
                $.ajax({
                    url: 'workinf/excelout',
                    type: 'post',
                    data: data,
                    timeout:600000,
                    dataType: "json",
                    success:function(returndata){

                        if(!returndata['success']){
                            alert('导出失败1');
                        }else{
                            // 防止反复添加
                            if(document.getElementById('downexcel')){
                                document.getElementById('downexcel').setAttribute('href', returndata['url']);
                            }else{
                                var a = document.createElement('a');
                                a.setAttribute('href', returndata['url']);
                                a.setAttribute('target', '_blank');
                                a.setAttribute('id', 'downexcel');
                                document.body.appendChild(a);
                            }
                            document.getElementById('downexcel').click();
                            //window.open(returndata['url'],'_blank');
                        }
                    },
                    error:function(){
                        alert('导出失败2');
                    }
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
        detail: function() {
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