// js/main.js

// 页面加载完成后执行
$(document).ready(function() {
    console.log('设备管理系统已加载');
    
    // 初始化标签页切换
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        console.log('切换到标签页: ' + $(e.target).text());
    });
    
    // 自动保存设备管理设置
    $('#deviceManageForm input[type="checkbox"]').change(function() {
        saveDeviceSettings();
    });
});

// 保存设备管理设置
function saveDeviceSettings() {
    const form = document.getElementById('deviceManageForm');
    const formData = new FormData(form);
    const deviceManage = {};
    
    for (let [key, value] of formData.entries()) {
        deviceManage[key] = value === 'on';
    }
    
    // 更新内存中的数据
    if (window.deviceData) {
        window.deviceData.device_tactics.deviceManage = deviceManage;
        window.deviceData.updated_at = new Date().toISOString().replace('T', ' ').substr(0, 19);
    }
    
    // 显示保存提示
    showToast('设备设置已更新', 'info');
}

// 显示Toast提示
function showToast(message, type = 'info') {
    const toast = $(`
        <div class="alert alert-${type} alert-dismissible fade in" role="alert" 
             style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            ${message}
        </div>
    `);
    
    $('body').append(toast);
    
    // 自动消失
    setTimeout(function() {
        toast.alert('close');
    }, 3000);
}

// 表单验证
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    return true;
}

// 生成唯一的ID
function generateId() {
    return Date.now() + Math.floor(Math.random() * 1000);
}

// 格式化日期
function formatDate(date) {
    const d = new Date(date);
    return d.getFullYear() + '-' + 
           String(d.getMonth() + 1).padStart(2, '0') + '-' + 
           String(d.getDate()).padStart(2, '0') + ' ' + 
           String(d.getHours()).padStart(2, '0') + ':' + 
           String(d.getMinutes()).padStart(2, '0') + ':' + 
           String(d.getSeconds()).padStart(2, '0');
}

// 文件大小格式化
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}