/*
 * 
 * @(#)helper.js
 * Author: Zachary.Ma
 * Date: 29 Nov 2017
 * Discription: common method define required jquery and bootstrip-dialog js libs.
 *
 */

/**
 * Common dialog define
 **/
function showMessage(str, funcclose){
    showDialog('MESSAGE', str, funcclose);
}
function showError(str, funcclose){
    showDialog('ERROR', str, funcclose);
}
function showWarning(str, funcclose){
    showDialog('WARNING', str, funcclose);
}
function showSuccess(str, funcclose){
    showDialog('SUCCESS', str, funcclose);
}

function showConfirm(str, funcOK, funcClose){
    BootstrapDialog.confirm({
        type: BootstrapDialog.TYPE_WARNING,
        size: BootstrapDialog.SIZE_NORMAL,
        closable: false,
        title: Lang.confirm,
        message: str,
        btnCancelLabel: Lang.btn_cancel,
        btnOKLabel: Lang.submit,
        btnOkClass: 'btn-warning',
        callback: function(result){
            if(result){
                if(funcOK){
                    funcOK.call();
                }
            } else {
                if(funcClose) {
                    funcClose.call();
                }
            }
        },
        onHide:function(){
            if(funcClose){
                funcClose();
            }
        }
    });
}
/** 选择操作框 */
function showSelectOperate(obj, funcUpdate, funcCreate){
    BootstrapDialog.show({
        type: BootstrapDialog.TYPE_PRIMARY,
        size: BootstrapDialog.SIZE_NORMAL,
        closable: true,
        title: obj.title,
        message: obj.msg,
        buttons:[{
            label: obj.btnOne,
            cssClass: 'btn-primary',
            action: function(dialogSelf){
                funcUpdate.call()
                dialogSelf.close();
            }
        },
        {
            label: obj.btnTwo,
            cssClass: 'btn-primary',
            action: function(dialogSelf){
                funcCreate.call()
                dialogSelf.close();
            }
        }]
    });
}
function showForm(title, message, funcOK){
    BootstrapDialog.show({
        type: BootstrapDialog.TYPE_PRIMARY,
        size: BootstrapDialog.SIZE_NORMAL,
        closable: false,
        title: title,
        message: message,
        buttons: [{
            label: Lang.btn_cancel,
            action: function(dialogRef){
                dialogRef.close();
            }
        },{
            icon: 'glyphicon',
            label: Lang.submit,
            cssClass: 'btn-primary',
            autospin: false,
            action: function(dialogRef){
                dialogRef.enableButtons(false);
                if(funcOK){
                    var bnt_spin = this;
                    bnt_spin.spin();
                    if(!funcOK(dialogRef, bnt_spin)){
                        bnt_spin.stopSpin();
                        dialogRef.enableButtons(true);
                    }
                }
            }
        }]
    });
}

/** 展示详情信息，如查看应用授权设备型号 */
function showDetail(title, message, funcclose){
    BootstrapDialog.show({
        type: BootstrapDialog.TYPE_PRIMARY,
        size: BootstrapDialog.SIZE_NORMAL,
        closable: true,
        title: title,
        message: message,
        onhide: function(){
            if(funcclose){
                funcclose();
            }
        }
    });
}

/** 预览与展示壁纸 */
function showPreview(title, message, funcclose){
    BootstrapDialog.show({
        type: BootstrapDialog.TYPE_DEFAULT,
        size: BootstrapDialog.SIZE_SMALL,
        closable: true,
        title: title,
        message: message,
        onhide: function(){
            if(funcclose){
                funcclose();
            }
        }
    });
}

function showDialog(type, str, funcclose){

    BootstrapDialog.show({
        type: function(){
            if(type == 'ERROR'){
                return BootstrapDialog.TYPE_DANGER;
            }if(type == 'WARNING'){
                return BootstrapDialog.TYPE_WARNING;
            }else if(type == 'SUCCESS'){
                return BootstrapDialog.TYPE_PRIMARY;
            }else{
                return BootstrapDialog.TYPE_PRIMARY;
            }
        },
        title: function(){
            if(type == 'ERROR'){
                return Lang.error;
            }if(type == 'WARNING'){
                return Lang.warning;
            }else if(type == 'SUCCESS'){
                return Lang.success;
            }else{
                return Lang.message;
            }
        },
        size: BootstrapDialog.SIZE_NORMAL,
        closable: false,
        message: str,
        buttons:[{
            label: Lang.submit,
            cssClass: 'btn-primary',
            action: function(dialogSelf){
                dialogSelf.close();
            }
        }],
        onhide: function(){
            if(funcclose){
                funcclose();
            }
        }
    });
}

/**
 * ajax request helper
 **/
function generate_data(method, params){
    var method_list = [];
    method_list["update_password"] = "user.updatepassword";
    method_list["get_area_by_role"] = "tree.getarea";
    method_list["get_all_area_address"] = "tree.getallareaaddress";
    method_list["get_area_address_by_role"] = "tree.getarea_address";
    method_list["createAppGroup"] = "appgroup.createAppGroup";
    method_list["editAppGroup"] = "appgroup.editAppGroup";
    method_list["process"] = "application.process";
    method_list["createUserGroup"] = "usergroup.createUserGroup";
    method_list["updateUserGroup"] = "usergroup.updateUserGroup";
    method_list["processImportUser"] = "user.processImportUser";
    method_list["push"] = "push.pushinfo";
    method_list["batchPushBySwdids"] = "push.batchPushBySwdids";
    method_list["oneClassBatchPush"] = "push.oneClassBatchPush";
    method_list["createTactic"] = "tactic.create";
    method_list["updateTactic"] = "tactic.update";
    method_list["tacticGrantGroup"] = "tactic.tacticGrantGroup";
    method_list["updateLinkTacOrSingleGroupTac"] = "tactic.updateLinkTacOrSingleGroupTac";
    method_list["unBindDevice"] = "device.unBindDevice";
    method_list["dayLocation"] = "device.daylocation";
    method_list["monthWeekends"] = "device.monthweekends";
    method_list["createAdvertise"] = "advertise.create";
    method_list["updateAdvertise"] = "advertise.update";
    method_list["createWallpaper"] = "wallpaper.create";
    method_list["availableDeviceModelList"] = "wallpaper.availableDeviceModelList";
    method_list["getDeviceListArr"] = "wallpaper.getDeviceListArr";
    method_list["updateWallpaper"] = "wallpaper.update";
    method_list["deleteWhitelist"] = "whitelist.delete";
    method_list["deleteAppWhitelist"] = "appwhitelist.delete";
    method_list["hasFreeAreaSchool"] = "tree.getFreeArea";
    method_list["createAppTimeLimit"] = "apptimelimit.create";
    method_list["updateAppTimeLimit"] = "apptimelimit.update";
    method_list["appTimeLimitGrantGroup"] = "apptimelimit.apptimelimitgrantgroup";
    method_list["appTimeLimitGrantVgroup"] = "apptimelimit.apptimelimitgrantvgroup";
    method_list["releaseControl"] = "user.releaseControl";
    method_list["freeControl"] = "user.freeControl";
    method_list["oneClassReleaseControl"] = "user.oneClassReleaseControl";
    method_list["updateAdminPass"] = "setting.updatePassword";
    method_list["createPVID"] = "setting.createPVID";
    method_list["deletePVID"] = "setting.deletePVID";
    method_list["availableDeviceModel"] = "devicemodel.availableDeviceModel";
    method_list["batchDeleteUser"] = "user.batchDeleteUser";
    method_list["oneClassDeleteUser"] = "user.oneClassDeleteUser";
    method_list["updateVirtualGroupApp"] = "virtualgroup.updateVirtualGroupApp";
    method_list["updateVirtualGroup"] = "virtualgroup.updateVirtualGroup";
    method_list["createVirtualGroup"] = "virtualgroup.createVirtualGroup";
    method_list["batchRemoveUserFromVgroup"] = "virtualgroup.batchRemoveUserFromVgroup";
    method_list["settingScreen"] = "setting.setScreen";
    method_list["setScreenLogo"] = "setting.setScreenLogo";
    method_list["setConsoleSetting"] = "setting.setConsoleSetting";
    //method_list["sendMsgCode"] = "user.sendmsgcode";
    method_list["sendMsgCodeForMsgLogin"] = "user.sendmsgcodeformsglogin";
    method_list["sendMsgCodeForFirstBind"] = "user.sendmsgcodeforfirstbind";
    method_list["sendMsgCodeForReplacePhone"] = "user.sendmsgcodeforreplacephone";
    method_list["sendMsgCodeForNewEdit"] = "user.sendmsgcodefornewedit";
    method_list["verifyEmailPassword"] = "user.verifyemailpassword";
    method_list["firstBindPhoneForLogin"] = "user.firstbindphoneforlogin";
    method_list["replacePhone"] = "user.replacephone";
    method_list["verifyPhoneVerifyCode"] = "user.verifyphoneverifycode";
    method_list["getProgress"] = "user.getProgress";
    method_list["batchDisableUser"] = "user.batchDisableUser";
    method_list["oneClassDisableUser"] = "user.oneClassDisableUser";
    method_list["batchEnableUser"] = "user.batchEnableUser";
    method_list["oneClassEnableUser"] = "user.oneClassEnableUser";
    method_list["batchSetLegal"] = "device.batchSetLegal";
    method_list["markDeviceNormal"] = "device.markDeviceNormal";
    method_list["oneClassRecoverControl"] = "user.oneClassRecoverControl";
    method_list["batchRecoverControl"] = "user.batchRecoverControl";
    method_list["createDisableEnableApp"] = "disenableapp.createDisableEnableApp";
    method_list["editDisableEnableAppGroup"] = "disenableapp.editDisableEnableAppGroup";
    method_list["deleteAppList"] = "disenableapp.deleteAppList";
    return {
        "id": 1,
        "!version": 1,
        "method": "com.linspirer." + method_list[method],
        "params": params,
        "jsonrpc": "2.0"
    };
}

/** ajax 异步请求 async = true */
function ajax_post_json_request(service_url, method, data,
                                success_function_handle,
                                error_function_handle, custom_error){
    common_ajax_post_json_request(service_url, method, data,
        success_function_handle,
        error_function_handle, custom_error, true)
}

/** ajax 同步请求 async = false */
function sync_ajax_post_json_request(service_url, method, data,
                                success_function_handle,
                                error_function_handle, custom_error){
    common_ajax_post_json_request(service_url, method, data,
        success_function_handle,
        error_function_handle, custom_error, false)
}

function common_ajax_post_json_request(service_url, method, data,
                                       success_function_handle,
                                       error_function_handle, custom_error, is_async= true) {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async: is_async,
        timeout: 0,
        type: "POST",
        url: service_url,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        data: JSON.stringify(generate_data(method, data)),
        error: function(response_obj) {
            if (error_function_handle) {
                error_function_handle(response_obj);
            } else {
                showError(Lang.request_error);
            }
        },
        success: function(jsonResult) {
            if(jsonResult.code == -21600){
                showError(Lang.user_not_login_or_login_timeout,function () {
                    window.location.reload();
                });
            }else if(jsonResult.code == -22800){
                showError(Lang.hasNoPermission,function () {
                    window.location.reload();
                });
            }else if(jsonResult.code == -21200){
                if (custom_error) {
                    showWarning(jsonResult.data);
                } else {
                    showError(Lang.invalidRequestParameter,function () {
                        window.location.reload();
                    });
                }
            } else{
                if (success_function_handle) {
                    success_function_handle(jsonResult);
                }
            }
        }
    });
}

/** ajax的post请求, 未做返回code统一处理 */
function ajax_post_json_request_no_unified(service_url, method, data,
                                success_function_handle,
                                error_function_handle){
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        async:true,
        timeout: 0,
        type: "POST",
        url: service_url,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        data: JSON.stringify(generate_data(method, data)),
        error: function(response_obj) {
            if (error_function_handle) {
                error_function_handle(response_obj);
            } else {
                showError(Lang.request_error);
            }
        },
        success: function(jsonResult) {
            success_function_handle(jsonResult);
        }
    });
}

/** ajax等待效果 */
function ajax_wait_post_json_request(service_url, method, data,
                                success_function_handle,
                                error_function_handle){
    var mask = $("<div class='mask'><i></i></div>");
    $.ajax({
        async: true,
        beforeSend: function () {

            $("body").append(mask);
          },
        complete: function () {
            mask.remove();
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "POST",
        url: service_url,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        data: JSON.stringify(generate_data(method, data)),
        error: function(response_obj) {
            if (error_function_handle) {
                error_function_handle(response_obj);
            } else {
                showError(Lang.request_error);
            }
        },
        success: function(jsonResult) {
            if(jsonResult.code == -21600){
                showError(Lang.user_not_login_or_login_timeout,function () {
                    window.location.reload()
                });
            }else if(jsonResult.code == -22800){
                showError(Lang.hasNoPermission,function () {
                    window.location.reload()
                });
            } else{
                success_function_handle(jsonResult);
            }
        }
    });
}


function ajax_get_json_request(service_url,
                               success_function_handle,
                               error_function_handle){
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "GET",
        url: service_url,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        error: function(response_obj) {
            if (error_function_handle) {
                error_function_handle(response_obj);
            } else {
                showError(Lang.request_error);
            }
        },
        success: function(jsonResult) {
            if(jsonResult.code == -21600){
                showError(Lang.user_not_login_or_login_timeout,function () {
                    window.location.reload()
                });
            }else{
                success_function_handle(jsonResult);
            }
        }
    });
}


/**
 *
 * */
function get_html_from_server(request_url,success_function_handle,
                              error_function_handle){
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "get",
        url: request_url,
        contentType: "application/json; charset=utf-8",
        dataType: "html",
        error: function(response_obj) {
            if (error_function_handle) {
                error_function_handle(response_obj);
            } else {
                showError(Lang.request_error);
            }
        },
        success: function(response_data) {
            if(response_data.indexOf('{"code":-21600') == -1){
                success_function_handle(response_data);
            }else{
                showError(Lang.user_not_login_or_login_timeout,function () {
                    window.location.reload()
                });
            }
        }
    });
}

/**
 * Laravel pagination ajax 
 **/

function laravel_pagination_ajax(page_container_id, parent_container_class = ''){
    if (parent_container_class.length > 0) {
        parent_container_class = "." + parent_container_class + " ";
    }
    $(parent_container_class + '#' + page_container_id + ' .pagination a.btn').prop("onclick",null).off("click");
    $(document).on('click', parent_container_class + '#' + page_container_id + ' .pagination a', function(event){
        event.preventDefault();
        var request_url = null;
        if($(this).hasClass('btn')){
            request_url =  $(this).attr("onclick").split("='")[1].replace("'","");
            request_url += "=" + $(parent_container_class + "#" + page_container_id).find('#pagination-input-go-to').val();
        }else{
            $(parent_container_class + '#' + page_container_id + ' .pagination').find('li').removeClass('active');
            $(this).parent("li").addClass('active');
            request_url = $(this).attr("href");
        }
        get_data_from_server(request_url);
        return false;
    });

    $(document).on('click', parent_container_class + '#' + page_container_id + ' .form-inline a.btn', function(event){
        event.preventDefault();
        search_schools();
    });
    $(document).on('keydown', parent_container_class + '#' + page_container_id + ' .form-inline input', function(event){
        if(event.keyCode === 13){
            event.preventDefault();
            search_schools();
        }
    });

    function search_schools(){
        let search_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_status').val();
        let search_last_hour = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_last_hour').val();
        let search_focus_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_focus_status').val();
        let search_online_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_online_status').val();
        let search_user_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_user_status').val();
        let search_release_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_release_status').val();
        let search_free_status = $(parent_container_class + '#' + page_container_id + ' .form-inline select#search_free_status').val();
        var start_time = $('#' + page_container_id + ' .form-inline input#search_start_time').val();
        var end_time = $('#' + page_container_id + ' .form-inline input#search_end_time').val();
        let search_key = $(parent_container_class + '#' + page_container_id + ' .form-inline input[name="search_key"]').val();
        let request_url = $(parent_container_class + '#' + page_container_id + ' .form-inline a.btn').attr("search_url");
        //搜索关键字中不允许有 '/'
        if (search_key.lastIndexOf("/") >= 0 || search_key.lastIndexOf("\\") >= 0 || search_key.lastIndexOf("..") >= 0) {
            showWarning(Lang.search_key_has_slashes,function () {
                window.location.reload()
            });
            return false;
        }
        if (search_user_status && search_release_status && search_free_status) {
            request_url += "?focusStatus=" + search_focus_status
            + "&userStatus=" + search_user_status
            + "&releaseStatus=" + search_release_status
            + "&freeStatus=" + search_free_status
            + "&searchKey=" + search_key
            return get_data_from_server(request_url);
        }
        if(search_status && search_last_hour){
            request_url += "?search_status=" + search_status
                + "&search_last_hour=" + encodeURI(search_last_hour)
                + "&search_key=" + encodeURI(search_key)
            return get_data_from_server(request_url);
        }
        if(search_focus_status){
            request_url += "/" + encodeURI(search_focus_status);
        }
        if(search_online_status){
            request_url += "/" + encodeURI(search_online_status);
        }
        if(start_time){
            request_url += "/" + encodeURI(start_time);
        }
        if(end_time){
            request_url += "/" + encodeURI(end_time);
        }
        if(search_key){
            request_url += "/" + encodeURI(search_key);
        }
        get_data_from_server(request_url);
    }

    function get_data_from_server(request_url){
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "get",
            url: request_url,
            contentType: "application/json; charset=utf-8",
            dataType: "html",
            error: function(response_obj) {
                showError(Lang.request_error);
            },
            success: function(response_data) {
                if (response_data.indexOf('{"code":-21600') >= 0) {

                    showError(Lang.user_not_login_or_login_timeout,function () {
                        window.location.reload()
                    });
                } else if (response_data.indexOf('{"code":-22400') >= 0) {
                    showWarning(Lang.current_page_has_expired,function () {
                        window.location.reload()
                    });
                } else {
                    $(parent_container_class + '#' + page_container_id).empty().html(response_data)
                        .find('.pagination a.btn')
                        .prop("onclick",null).off("click");
                }
            }
        });
    }
}
/**
 * End laravel pagination ajax 
 **/


/**
 * 数组中查找元素
 * */
function in_array(element, arr) {
    for (var i = 0; i < arr.length; i++) {
        if (element.toLowerCase() == arr[i].toLowerCase())
            return true;
    }
    return false;
}

/**
 * 获取文件后缀名
 * */
function getFileSuffix(fileName) {
    var arr = fileName.split('.') ;
    return arr[arr.length-1]
}


/**
 * for用户组管理页面的显示 */
$.fn.extend({
    treed: function (o) {

        var openedClass = 'glyphicon-minus-sign';
        var closedClass = 'glyphicon-plus-sign';

        if (typeof o != 'undefined'){
            if (typeof o.openedClass != 'undefined'){
                openedClass = o.openedClass;
            }
            if (typeof o.closedClass != 'undefined'){
                closedClass = o.closedClass;
            }
        };

        //initialize each of the top levels
        var tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
            var branch = $(this); //li with children ul
            branch.prepend("<i class='indicator glyphicon " + closedClass + "'></i>");
            branch.addClass('branch');
            branch.find(".indicator").on('click', function (e) {
                e.stopPropagation();
                if (this == e.target) {
                    var icon = $(this);
                    icon.toggleClass(openedClass + " " + closedClass);
                    branch.children("ul").toggle();
                }
            });
        });

        //fire event from the dynamically added icon
        /*tree.find('.branch .indicator').each(function(){
            $(this).on('click', function () {
                $(this).closest('li').click();
            });
        });*/
        //fire event to open branch if the li contains an anchor instead of text
        tree.find('.branch>a').each(function () {
            $(this).on('click', function (e) {
                e.stopPropagation();
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
        //fire event to open branch if the li contains a button instead of text
        tree.find('.branch>button').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
    }
});

/** 构建用户分组树 */
function printGroupTree(data) {
    var html = "<ul>";
    $.each(data,function (i,val) {
        var linkRelateId = 0;
        var gpaperclip = '';
        if (val.singleGroupTactic && val.singleGroupTactic > 0) { //有单组策略生效
            linkRelateId =  + val.singleGroupTactic; //添加链接策略ltactic属性
            gpaperclip = '<span class="glyphicon glyphicon-pushpin"></span>'; //添加链接标签
        } else if (val.hasLink) {
            linkRelateId =  + val.linkRelateId; //添加链接策略ltactic属性
            gpaperclip = '<span class="glyphicon glyphicon-link"></span>'; //添加链接标签
        }
        html += "<li group-id=" + val.id + " group-name='" + val.name + "'><span>";
        html += "<span class='branch-title' linkRelateId=" + linkRelateId + " title='" + val.name + "'>" + val.name +"</span>";
        if (val.parent === 0) {
            html += "<span class='glyphicon glyphicon-pencil' style='float: right;margin:5px 2px 0 0;'></span>";
        }
        html += gpaperclip + "</span>";

        if (val.node) {
            html += printGroupTree(val.node);
        }
        html += "</li>";
    });
    return html + "</ul>";
}

/** 初始化所属组的树 */
function initial_in_group_tree(group_container,data, tree_group) {
    tree_group = tree_group || 'group';
    var in_group_select = group_container.find("#in-" + tree_group + "-select");
    in_group_select.empty();
    in_group_select.append(printGroupTree(data));
    in_group_select.find('span.glyphicon-pencil').remove();
    var tree1 = in_group_select.find("ul:first");
    tree1.css("border",'1px solid #428bca');
    tree1.css("position",'absolute');
    tree1.css("z-index",'999');
    tree1.css("top",'-1px');
    tree1.css("left",'15px');
    tree1.css("background",'#fff');
    if (tree_group == 'vgroup') {
        tree1.css("top",'-12px');
        tree1.css("left",'98px');
    }
    tree1.treed({openedClass:'glyphicon glyphicon-user', closedClass:'glyphicon glyphicon-user'});
}

/** 给所属群组添加点击选中内容属性事件 */
function select_user_group(group_container, tree_group) {
    tree_group = tree_group || 'group';
    group_container.find('#in_' + tree_group).click(function () {
        var in_group_select = group_container.find('#in-' + tree_group + '-select');
        if (in_group_select.is(":visible")) {
            in_group_select.hide();
        } else {
            in_group_select.show();
        }

        var in_group = $(this);
        in_group_select.find("li").click( function (e) {
            e.stopPropagation(); //阻止事件冒泡
            var group_id = $(this).attr('group-id');
            var group_name = $(this).attr('group-name');
            in_group.val(group_name);
            group_container.find("#usergroup").val(group_id);
            in_group_select.hide();
        });
    });
}

/** 新增用户、编辑用户 组下拉选择框，新增组、编辑组 父级选择下拉框，初始化用户组树 */
function initialSelectTree(group_container, url) {

    ajax_get_json_request(url, function(response_obj){
        if(response_obj.code == 0) {
            var data = response_obj.data;
            initial_in_group_tree(group_container,data);
            group_container.find("ul:first").css("width",'93%');
            /** 点击选中值 */
            select_user_group(group_container);
        } else{
            showError(Lang.request_error);
        }
    });
}

/*
* 剩余字数统计
* 注意 最大字数只需要在放数字的节点哪里直接写好即可 如：<var class="word">200</var>
*/
function statInputNum(textArea,numItem) {
    var max = numItem.text(),
        curLength;
    textArea[0].setAttribute("maxlength", max);
    curLength = textArea.val().length;
    numItem.text(max - curLength);
    textArea.on('input propertychange', function () {
        numItem.text(max - $(this).val().length);
    });
}

/** 格式化时间
 * fmt 为要格式话的时间格式如:YY-mm-dd
 * */
function dateFormat(fmt, date) {
    var ret;
    var opt = {
        "Y+": date.getFullYear().toString(),        // 年
        "m+": (date.getMonth() + 1).toString(),     // 月
        "d+": date.getDate().toString(),            // 日
        "H+": date.getHours().toString(),           // 时
        "M+": date.getMinutes().toString(),         // 分
        "S+": date.getSeconds().toString()          // 秒
        // 有其他格式化字符需求可以继续添加，必须转化成字符串
    };
    for (var k in opt) {
        ret = new RegExp("(" + k + ")").exec(fmt);
        if (ret) {
            fmt = fmt.replace(ret[1], (ret[1].length == 1) ? (opt[k]) : (opt[k].padStart(ret[1].length, "0")))
        }
    }
    return fmt;
}

/** 给所属群组添加点击选中内容属性事件 */
function select_virtual_group(group_container, vroot_group) {
    group_container.find("#in_group").click(function () {
        var in_group_select = group_container.find("#in-group-select");
        if (in_group_select.is(":visible")) {
            in_group_select.hide();
        } else {
            in_group_select.show();
        }

        var in_group = $(this);

        in_group_select.find("li").click( function (e) {
            e.stopPropagation(); //阻止事件冒泡
            var group_id = $(this).attr('group-id');
            var group_name = $(this).attr('group-name');
            in_group.val(group_name);
            group_container.find("#usergroup").val(group_id);
            in_group_select.hide();

            if (group_id != vroot_group) {
                group_container.find("#uv_group_container").removeClass("has-error");
                group_container.find("#vgroup_select_error_tip").hide();
            }
        });
    });
}

/**
 * Laravel pagination ajax from dialog
 **/
function laravel_pagination_ajax_from_dialog(outermost_container, page_container_id, vGHasUserIdEmailArr){

    //处理虚拟组选择用户信息
    virtualSelectedUserList(outermost_container, vGHasUserIdEmailArr);

    outermost_container.find('#' + page_container_id).find('.pagination').find("a.btn").prop("onclick",null).off("click");
    outermost_container.find('#' + page_container_id).find('.pagination').find("a").bind("click", function(event){
        event.preventDefault();
        var request_url = null;
        if($(this).hasClass('btn')){
            request_url = $(this).attr("onclick").split("=")[1].replace("'","");
            request_url += "=" + outermost_container.find("#" + page_container_id).find('#pagination-input-go-to').val();
        }else{
            outermost_container.find('#' + page_container_id).find('.pagination').find('li').removeClass('active');
            $(this).parent("li").addClass('active');
            request_url = $(this).attr("href");
        }

        get_data_from_server(request_url);

        return false;
    });

    outermost_container.find('#' + page_container_id).find('.form-inline').find('a.btn').bind("click", function(event){
        event.preventDefault();
        search_schools();
    });

    outermost_container.find('#' + page_container_id).find('.form-inline').find('input').bind("click", function(event){
        if(event.keyCode == "13"){
            event.preventDefault();
            search_schools();
        }
    });

    function search_schools(){
        var search_key = outermost_container.find('#' + page_container_id).find('.form-inline').find('input').val();
        var request_url = outermost_container.find('#' + page_container_id).find('.form-inline').find('a.btn').attr("search_url");
        //搜索关键字中不允许有 '/'
        if (search_key.lastIndexOf("/") >= 0 || search_key.lastIndexOf("\\") >= 0 || search_key.lastIndexOf("..") >= 0) {
            showWarning(Lang.search_key_has_slashes,function () {
                window.location.reload()
            });
            return false;
        }
        if(search_key){
            request_url += "/" + encodeURI(search_key);
        }
        get_data_from_server(request_url);
    }

    function get_data_from_server(request_url){
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "get",
            url: request_url,
            contentType: "application/json; charset=utf-8",
            dataType: "html",
            error: function(response_obj) {
                showError(Lang.request_error);
            },
            success: function(response_data) {
                if (response_data.indexOf('{"code":-21600') >= 0) {

                    showError(Lang.user_not_login_or_login_timeout,function () {
                        window.location.reload()
                    });
                } else if (response_data.indexOf('{"code":-22400') >= 0) {
                    showWarning(Lang.current_page_has_expired,function () {
                        window.location.reload()
                    });
                } else {
                    outermost_container.find('#' + page_container_id).empty().html(response_data)
                        .find('.pagination a.btn')
                        .prop("onclick",null).off("click");
                    laravel_pagination_ajax_from_dialog(outermost_container, page_container_id, vGHasUserIdEmailArr);
                }
            }
        });
    }
}
/**
 * End laravel pagination ajax from dialog
 **/

/** 新建编辑虚拟组，同时在用户树上选择多个用户 */
var row_select_userid_list = []; //记录选中的用户userid信息
function virtualSelectedUserList(outermost_container, vGHasUserIdEmailArr) {

    fillVGroupSelectedUserForEdit(outermost_container, vGHasUserIdEmailArr);

    fillVGroupSelectedUserByBeforePage(outermost_container);

    //添加选中和取消用户事件
    outermost_container.find(".ui-checkbox").click(function(){
        var opt_div = outermost_container.find(".ui-checkbox");
        if ($(this).hasClass('all')) { //全选
            if($(this).hasClass('selected')) {
                outermost_container.find('.user-checkbox.selected').each(function () {
                    var userid = $(this).attr("userid");
                    if (userid) {
                        outermost_container.find("#" + userid).remove();
                        $(this).removeClass("selected");
                        row_select_userid_list.splice($.inArray(userid, row_select_userid_list),1);
                    }
                });
                opt_div.removeClass('selected');
            } else {
                opt_div.removeClass('selected').addClass('selected');
                outermost_container.find('.user-checkbox').each(function () {
                    $(this).removeClass('selected').addClass("selected");
                    var userid = $(this).attr("userid");
                    var username = $(this).attr("username");
                    if (userid && username && ($.inArray(userid, row_select_userid_list) < 0)) {
                        outermost_container.find("#selected-user-list").append(oneRowUserHtml(userid, username));
                        row_select_userid_list.push(userid);
                    }
                });
            }
        } else { //单个选择
            var userid = $(this).attr("userid");
            var username = $(this).attr("username");
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                if (userid && username) {
                    outermost_container.find("#" + userid).remove();
                    row_select_userid_list.splice($.inArray(userid, row_select_userid_list),1);
                }
            } else {
                $(this).removeClass('selected').addClass('selected');
                if (userid && username && ($.inArray(userid, row_select_userid_list) < 0)) {
                    outermost_container.find("#selected-user-list").append(oneRowUserHtml(userid, username));
                    row_select_userid_list.push(userid);
                }
            }
            labelSelectedAll(outermost_container);
        }
    });
}

/** 标识树节点全选状态 */
function labelSelectedAll(outermost_container) {
    var select_all = true;
    outermost_container.find(".ui-checkbox").filter(":not('.all')").filter(":not('.selected')").each(function (e) {
        select_all = false;
        return false;
    });
    if (select_all && outermost_container.find(".ui-checkbox").filter(":not('.all')").filter(".selected").length > 0) {
        outermost_container.find(".ui-checkbox.all").removeClass("selected").addClass("selected");
    } else {
        outermost_container.find(".ui-checkbox.all").removeClass("selected");
    }
}

/** 编辑虚拟组信息时，虚拟组已选用户信息回填 */
function fillVGroupSelectedUserForEdit(outermost_container, vGHasUserIdEmailArr) {
    //编辑虚拟用户组信息时，将虚拟组之前的用户信息回填
    $.each(vGHasUserIdEmailArr, function(index, value) {
        var userid = index;
        var opt_div = outermost_container.find('.user-checkbox[userid="' + userid +'"]');
        if (($.inArray(userid, row_select_userid_list) >= 0)) {
            opt_div.addClass("selected");
        }
    });

    labelSelectedAll(outermost_container);
}

/** 填充翻页前选择的用户信息 */
function fillVGroupSelectedUserByBeforePage(outermost_container) {
    //翻页回填勾选前一个页面的选择的用户
    outermost_container.find('.user-checkbox').each(function () {
        var userid = $(this).attr("userid");
        if (userid && ($.inArray(userid, row_select_userid_list) >= 0)) {
            $(this).addClass("selected");
        }
    });

    labelSelectedAll(outermost_container);
}
function oneRowUserHtml(userid, username) {
    return '<div class="row" id="' + userid +'">' +
        '<span class="label label-default">' +
        '<span class="glyphicon glyphicon-remove" onclick="removeSelectedItem(this);"></span>&nbsp;' + username +
        '</span>' +
        '</div>';
}

/** 删除虚拟组中选中的用户项 */
function removeSelectedItem(d) {
    var opt_div = $(d).parent().parent();
    var userid = opt_div.attr("id");
    if (userid) {
        row_select_userid_list.splice($.inArray(userid, row_select_userid_list),1);
    }
    $(".ui-checkbox.vck.user-checkbox.selected").each(function () {
        if ($(this).attr("userid") == userid) {
            $(this).removeClass("selected");
        }
    });
    $(".ui-checkbox.vck.all").removeClass("selected");
    opt_div.remove();
}

/**
 * [getFileName 获取文件的名(去除后缀)]
 * @return {[type]} [description]
 */
function getFileName(fileName){
    var arr = fileName.split('\\');
    file =  arr[arr.length-1];
    return file;
    ext = file.split('.');
    // 如果无后缀的文件。全部返回
    if (ext.length==1) {
        return ext;
    }
    ext.pop();
    return ext;
}
function setFileN(fileName){
    fileName = getFileName(fileName);
    newf = fileName;
    // 后缀
    hz = "";
    ext = fileName.split('.');
    if (ext.length !=1) {
        // 最后一个
        hz = ext.pop();
    }

    if (fileName.length > 20) {
        newf = fileName.slice(0,20) + "." +  hz;
    }
    return newf;
}
/** 名称中过滤掉: \,/,'," */
function validate_name(_this_div)
{
    let pattern = /[\u4E00-\u9FA5.\w\s()_\-@·]+/ig;
    let _new_val = '';
    let _this_val = $(_this_div).val();
    let res = _this_val.match(pattern);
    if (res) {
        _new_val = res.join("");
    }
    $(_this_div).val(_new_val);
}

function vailPhone(cur_this, allow_empty)
{
    let phoneContainer = $(cur_this).parent().parent();
    phoneContainer.removeClass('has-error');
    let verifyCodeContainer = phoneContainer.next();
    verifyCodeContainer.removeClass('has-error');
    verifyCodeContainer.find(".get-active-code").removeClass("allow-active-code");
    verifyCodeContainer.find(".help-block").empty();
    let phone = $.trim($(cur_this).val());
    if (allow_empty && phone.length === 0) {
        phoneContainer.find(".help-block").empty().text(Lang.phoneTip);
        return false;
    }
    let reg = /^1([38][0-9]|4[014-9]|5[0-35-9]|6[25-7]|7[0-8]|9[0-35-9])\d{8}$/
    if((!reg.test(phone))){
        phoneContainer.addClass('has-error');
        phoneContainer.find(".help-block").empty().text(Lang.please_enter_valid_phone_number);
        return false;
    }
    if (allow_empty) {
        phoneContainer.find(".help-block").empty().text(Lang.phoneTip);
    }
    verifyCodeContainer.find(".get-active-code").addClass("allow-active-code");
    return true;
}

/** 获取手机验证码 */
function getVerifyCode(cur_this, serviceApi)
{
    processVerifyCode(cur_this, serviceApi, "sendMsgCodeForMsgLogin", {});
}

function processVerifyCode(cur_this, serviceApi, method, data)
{
    let phone_div = $(cur_this).parent().parent().prev().find(".phone");
    if (vailPhone(phone_div) === false) { return false; }
    data.phone = phone_div.val();
    ajax_post_json_request(serviceApi, method,data,function(response_obj){
        $(cur_this).parent().parent().removeClass('has-error');
        $(cur_this).parent().next(".help-block").empty();
        if(response_obj.code == 0){
            resetCode(cur_this);
        } else {
            let resError = response_obj.data;
            if (response_obj.data.phone) {
                resError = response_obj.data.phone['0'];
            }
            if (response_obj.data.sms_verify_code) {
                resError = response_obj.data.sms_verify_code['0'];
            }
            $(cur_this).parent().parent().addClass('has-error');
            $(cur_this).parent().next(".help-block").text(resError);
        }
    });
}

/** 获取手机验证码,新建和编辑管理员 */
function getVerifyCodeForNewEdit(cur_this, serviceApi, email, role, operate = 1)
{
    let data = {"email": email, "operate": operate};
    if (role) {
        data.role = role;
    }
    processVerifyCodeForNewEdit(cur_this, serviceApi, "sendMsgCodeForNewEdit", data);
}

/** 获取手机验证码, 个人中心替换手机号码时使用 */
function getVerifyCodeForChangePhone(cur_this, serviceApi, email, old_new)
{
    processVerifyCodeForNewEdit(cur_this, serviceApi, "sendMsgCodeForReplacePhone", {"email": email, "old_new": old_new});
}

function processVerifyCodeForNewEdit(cur_this, serviceApi, method, data)
{
    let verifyCodeContainer = $(cur_this).parent().parent().parent();
    let phoneContainer = verifyCodeContainer.prev();
    let phone_div = phoneContainer.find(".phone");
    if (vailPhone(phone_div) === false) { return false; }
    data.phone = phone_div.val();
    ajax_post_json_request(serviceApi, method,data,function(response_obj){
        verifyCodeContainer.removeClass('has-error');
        verifyCodeContainer.find(".help-block").empty();
        if(response_obj.code == 0){
            resetCode(cur_this);
        } else if (response_obj.code == -21201) { //自定义请求参数无效
            let resData = response_obj.data;
            let emailContainer = $("#email").parent().parent();
            emailContainer.removeClass('has-error');
            emailContainer.find(".help-block").empty();
            if (resData.email) {
                emailContainer.addClass('has-error');
                emailContainer.find(".help-block").text(resData.email['0']);
            }
            phoneContainer.removeClass('has-error');
            phoneContainer.find(".help-block").empty();
            if (resData.phone) {
                phoneContainer.addClass('has-error');
                phoneContainer.find(".help-block").text(resData.phone['0']);
            }
            let roleContainer = $("#role").parent().parent();
            roleContainer.removeClass('has-error');
            roleContainer.find(".help-block").empty();
            if (resData.role) {
                roleContainer.addClass('has-error');
                roleContainer.find(".help-block").text(resData.role['0']);
            }
        } else {
            verifyCodeContainer.addClass('has-error');
            verifyCodeContainer.find(".help-block").text(response_obj.data);
        }
    });
}

/** 倒计时60秒后再允许发送验证码请求 */
function resetCode(cur_this)
{
    let clickEvent = $(cur_this).attr("onclick")
    $(cur_this).removeAttr("onclick"); //防止多次点击
    let second = 59;
    $(cur_this).empty().text(Lang.reacquire_pre + second + Lang.reacquire_suffix);
    let timer = setInterval(function(){ //定时器，每秒执行一次
        second -= 1;
        if(second > 0 ){
            $(cur_this).empty().text(Lang.reacquire_pre + second + Lang.reacquire_suffix);
        }else{
            clearInterval(timer);
            $(cur_this).empty().text(Lang.getVerificationCode);
            $(cur_this).attr("onclick", clickEvent);
        }
    },1000);
}

/**
 * JS监听浏览器的后退按钮的事件
 * 由于安全原因javascript不允许修改history里已有的url链接，
 * 但可以使用pushState方法往history里增加url链接，
 * 并且提供popstate事件监测从history栈里弹出url
 * */
function addBrowseBackHistory(previousUrl) {
    if (!!(window.history && history.pushState)) {
        window.history.pushState("forward", null, '#');
        window.addEventListener('popstate', function(event) {
            //console.log("我监听到了浏览器的返回按钮事件啦");
            window.history.pushState("forward", null, '#');
            window.location.href = previousUrl;
        });
    }
}

/** 填充选择的时间限制数据 */
function fillSelectedTimeLimitData(worktime) {
    let timeTrArr = [];
    $.each(worktime, function (index, element) {
        timeTrArr[index] = "<td>";
        if (element.length > 0) { //element是数组
            $.each(element, function (i, v) {
                if (!$.isEmptyObject(v)) { //时间设置的时间段为空
                    timeTrArr[index] += v.start + "~" + v.end + "</br>";
                }
            })
        }
        timeTrArr[index] += "</td>";
    });
    let _trHtml = "<tr>";
    let weeks = ["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];
    $.each(weeks, function (k, day) {
        _trHtml += timeTrArr[day];
    });
    _trHtml += "</tr>";
    return _trHtml;
}

/** return example /aa/bb?q=124&s=3434*/
function getUrlPathQuery(url, params){
    let isFirst = true;
    url += "/" + params.ugId;
    delete params.ugId;
    $.each(params, function (k,v) {
        if (isFirst) {
            isFirst = false;
            url += "?"+k+"="+v;
        } else {
            url += "&"+k+"="+v;
        }
    });
    return url;
}
