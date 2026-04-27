// script.js - 将所有需要被HTML调用的函数设为全局

// 全局函数定义
function addNewApp() {
    document.getElementById('addAppModal').classList.add('active');
}

function closeModal() {
    document.getElementById('addAppModal').classList.remove('active');
    clearForm();
}

function clearForm() {
    document.getElementById('appName').value = '';
    document.getElementById('appPackage').value = '';
    document.getElementById('appVersion').value = '';
    document.getElementById('appDevice').value = 'HEY-W09';
    document.getElementById('appStatus').value = '1';
    document.getElementById('appForce').value = 'true';
}

function saveNewApp() {
    const name = document.getElementById('appName').value.trim();
    const packageName = document.getElementById('appPackage').value.trim();
    const version = document.getElementById('appVersion').value.trim();
    const device = document.getElementById('appDevice').value.trim();
    const status = parseInt(document.getElementById('appStatus').value);
    const isforce = document.getElementById('appForce').value === 'true';

    if (!name || !packageName || !version) {
        alert('请填写所有必填字段！');
        return;
    }

    const newApp = {
        id: Date.now(),
        name: name,
        packagename: packageName,
        versionname: version,
        versioncode: Math.floor(Math.random() * 1000),
        sha1: null,
        target_sdk_version: 30,
        devicetype: device || 'HEY-W09',
        grant_type: 5,
        grant_to: 776883,
        groupid: 1,
        status: status,
        isnew: true,
        app_notify_status: 0,
        isforce: isforce,
        canuninstall: true,
        exception_white_url: 0,
        is_trust: true,
        hide_icon_status: 0,
        sort_weight: 0,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
    };

    if (window.configManager && window.configManager.config) {
        window.configManager.config.app_tactics.applist.push(newApp);
        window.configManager.markAsModified();
        
        // 直接保存到服务器，然后重新加载页面
        window.configManager.saveConfig();
        location.reload(true);
        
        closeModal();
    }
}

function deleteApp(index) {
    if (!confirm('确定要删除这个应用吗？')) return;

    if (window.configManager && window.configManager.config) {
        window.configManager.config.app_tactics.applist.splice(index, 1);
        window.configManager.markAsModified();
        
        // 重新渲染应用列表
        renderAppsList();
        if (window.configManager.showNotification) {
            window.configManager.showNotification('应用已删除', 'success');
            window.configManager.saveConfig();
        location.reload(true);
        }
    }
}

function addAppToUI(app, index) {
    const appsGrid = document.querySelector('.apps-grid');
    if (!appsGrid) return;

    const appCard = document.createElement('div');
    appCard.className = 'app-card';
    appCard.dataset.index = index;
    appCard.innerHTML = `
        <div class="app-header">
            <span class="app-name">${escapeHtml(app.name)}</span>
            <span class="app-version">v${escapeHtml(app.versionname)}</span>
        </div>
        <div class="app-info">
            <div class="info-row">
                <span class="info-label">包名:</span>
                <span class="info-value">${escapeHtml(app.packagename)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">设备:</span>
                <span class="info-value">${escapeHtml(app.devicetype)}</span>
            </div>
        </div>
        <div class="app-controls">
            <label class="switch">
                <input type="checkbox" class="status-toggle" 
                       data-path="app_tactics.applist.${index}.status"
                       ${app.status == 1 ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">启用</span>
            </label>
            
            <label class="switch">
                <input type="checkbox" class="force-toggle" 
                       data-path="app_tactics.applist.${index}.isforce"
                       ${app.isforce ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">强制</span>
            </label>
            
            <label class="switch">
                <input type="checkbox" class="hide-toggle" 
                       data-path="app_tactics.applist.${index}.hide_icon_status"
                       ${app.hide_icon_status == 1 ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">隐藏图标</span>
            </label>
            
            <button class="delete-btn" onclick="deleteApp(${index})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    appsGrid.appendChild(appCard);
}

function renderAppsList() {
    if (!window.configManager || !window.configManager.config) return;

    const appsGrid = document.querySelector('.apps-grid');
    if (!appsGrid) return;

    appsGrid.innerHTML = '';
    window.configManager.config.app_tactics.applist.forEach((app, index) => {
        addAppToUI(app, index);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ConfigManager类定义
class ConfigManager {
    constructor() {
        this.config = null;
        this.loadConfig();
        this.initializeEventListeners();
    }

    async loadConfig() {
        try {
            const response = await fetch('deictv.json');
            this.config = await response.json();
            this.updateTimestamp();
        } catch (error) {
            console.error('加载配置失败:', error);
            alert('加载配置失败，请刷新页面重试');
        }
    }
    
    // 在 ConfigManager 类的 loadConfig 方法后添加
async loadStoreData() {
    try {
        const response = await fetch('app.json');
        window.storeData = await response.json();
        this.initializeStoreEventListeners();
    } catch (error) {
        console.error('加载商店数据失败:', error);
    }
}

async loadUserData() {
    try {
        const response = await fetch('user.json');
        window.userData = await response.json();
    } catch (error) {
        console.error('加载用户数据失败:', error);
    }
}

initializeStoreEventListeners() {
    // 商店搜索功能
    const storeSearch = document.getElementById('storeSearch');
    if (storeSearch) {
        storeSearch.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.app-card[data-index]').forEach(card => {
                const appName = card.querySelector('.app-name').textContent.toLowerCase();
                const packageName = card.querySelectorAll('.info-value')[0].textContent.toLowerCase();
                
                if (appName.includes(searchTerm) || packageName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
}

    updateTimestamp() {
        const timestamp = document.querySelector('.status-value');
        if (timestamp && this.config.updated_at) {
            timestamp.textContent = this.config.updated_at;
        }
    }

    getValueByPath(path) {
        return path.split('.').reduce((obj, key) => obj && obj[key], this.config);
    }

    setValueByPath(path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((obj, key) => obj[key] = obj[key] || {}, this.config);
        target[lastKey] = value;
    }

    initializeEventListeners() {
        // 标签页切换
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                const tabId = btn.dataset.tab;
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
        // 监听功能控制开关
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('setting-toggle')) {
            const path = e.target.dataset.path;
            const value = e.target.checked ? 1 : 0;
            this.setValueByPath(path, value);
            this.markAsModified();
        }
    });
    
    // 监听功能控制下拉框
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('setting-select')) {
            const path = e.target.dataset.path;
            const value = parseInt(e.target.value);
            this.setValueByPath(path, value);
            this.markAsModified();
        }
    });

        // 开关切换事件
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('status-toggle') || 
                e.target.classList.contains('force-toggle') ||
                e.target.classList.contains('hide-toggle') ||
                e.target.classList.contains('device-toggle') ||
                e.target.classList.contains('setting-toggle') ||
                e.target.classList.contains('policy-toggle') ||
                e.target.classList.contains('policy-option')) {
                
                const path = e.target.dataset.path;
                let value;
                
                if (e.target.type === 'checkbox') {
                    value = e.target.checked;
                    if (path.includes('.status') || path.includes('.hide_icon_status')) {
                        value = value ? 1 : 0;
                    }
                } else if (e.target.type === 'select-one') {
                    value = e.target.value;
                }
                
                this.setValueByPath(path, value);
                this.markAsModified();
            }
        });

        // 下拉选择框事件
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('setting-select')) {
                const path = e.target.dataset.path;
                const value = e.target.value === '0' ? 0 : 1;
                this.setValueByPath(path, value);
                this.markAsModified();
            }
        });

        // 启动设置
        const launchPackageInput = document.getElementById('launchPackage');
        const launchModeSelect = document.getElementById('launchMode');
        
        if (launchPackageInput) {
            launchPackageInput.addEventListener('input', () => {
                this.setValueByPath('device_setting.launch_app.launch_package', 
                    launchPackageInput.value.trim() || null);
                this.markAsModified();
            });
        }
        
        if (launchModeSelect) {
            launchModeSelect.addEventListener('change', () => {
                this.setValueByPath('device_setting.launch_app.launch_mode', 
                    parseInt(launchModeSelect.value));
                this.markAsModified();
            });
        }

        // 建议的应用按钮
        document.querySelectorAll('.suggestion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const packageName = btn.dataset.package;
                document.getElementById('launchPackage').value = packageName;
                this.setValueByPath('device_setting.launch_app.launch_package', packageName);
                this.markAsModified();
            });
        });

        // 保存按钮
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveConfig();
            });
            
        }

        // 应用搜索
        const appSearch = document.getElementById('appSearch');
        if (appSearch) {
            appSearch.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.app-card').forEach(card => {
                    const appName = card.querySelector('.app-name').textContent.toLowerCase();
                    const packageName = card.querySelector('.info-value').textContent.toLowerCase();
                    
                    if (appName.includes(searchTerm) || packageName.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // 自动保存标记
        this.isModified = false;
        setInterval(() => {
            if (this.isModified) {
                this.autoSave();
            }
        }, 30000);
    }

    markAsModified() {
        this.isModified = true;
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> 有未保存的更改';
            saveBtn.style.background = 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
        }
    }

    async saveConfig() {
        try {
            const now = new Date();
            const timestamp = now.toISOString().replace('T', ' ').substring(0, 19);
            this.config.updated_at = timestamp;
            
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.config)
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.updateTimestamp();
                    this.isModified = false;
                    
                    const saveBtn = document.getElementById('saveBtn');
                    if (saveBtn) {
                        saveBtn.innerHTML = '<i class="fas fa-check"></i> 保存成功';
                        saveBtn.style.background = 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)';
                        
                        setTimeout(() => {
                            saveBtn.innerHTML = '<i class="fas fa-save"></i> 保存更改';
                            saveBtn.style.background = 'linear-gradient(135deg, #4299e1 0%, #3182ce 100%)';
                        }, 2000);
                    }
                    
                    this.showNotification('配置保存成功！', 'success');
                } else {
                    throw new Error(result.message || '保存失败');
                }
            } else {
                throw new Error('服务器响应错误');
            }
        } catch (error) {
            console.error('保存配置失败:', error);
            this.showNotification('保存失败: ' + error.message, 'error');
        }
    }

    async autoSave() {
        if (!this.isModified) return;
        
        console.log('自动保存配置...');
        await this.saveConfig();
    }

    showNotification(message, type = 'info') {
        const existing = document.querySelector('.notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    window.configManager = new ConfigManager();
    // 加载商店数据
    window.configManager.loadStoreData();
    
    // 加载用户数据
    window.configManager.loadUserData();
    
    // 添加通知样式
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 10000;
            border-left: 4px solid #4299e1;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            border-left-color: #48bb78;
        }
        
        .notification.success i {
            color: #48bb78;
        }
        
        .notification.error {
            border-left-color: #f56565;
        }
        
        .notification.error i {
            color: #f56565;
        }
        
        .notification i {
            font-size: 20px;
        }
        
        .notification span {
            font-size: 15px;
            color: #2d3748;
        }
    `;
    document.head.appendChild(style);
});
function editApp(index) {
    if (!window.configManager || !window.configManager.config) return;
    
    const app = window.configManager.config.app_tactics.applist[index];
    if (!app) return;
    
    // 填充表单数据
    document.getElementById('editAppIndex').value = index;
    document.getElementById('editAppId').value = app.id;
    document.getElementById('editAppName').value = app.name;
    document.getElementById('editAppPackage').value = app.packagename;
    document.getElementById('editAppVersion').value = app.versionname;
    document.getElementById('editVersionCode').value = app.versioncode;
    document.getElementById('editAppDevice').value = app.devicetype;
    document.getElementById('editAppStatus').value = app.status.toString();
    document.getElementById('editAppForce').value = app.isforce.toString();
    document.getElementById('editHideIcon').value = app.hide_icon_status.toString();
    document.getElementById('editCanUninstall').value = app.canuninstall.toString();
    document.getElementById('editSha1').value = app.sha1 || '';
    document.getElementById('editTargetSdk').value = app.target_sdk_version;
    
    // 显示模态框
    document.getElementById('editAppModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editAppModal').classList.remove('active');
    clearEditForm();
}

function clearEditForm() {
    document.getElementById('editAppIndex').value = '';
    document.getElementById('editAppId').value = '';
    document.getElementById('editAppName').value = '';
    document.getElementById('editAppPackage').value = '';
    document.getElementById('editAppVersion').value = '';
    document.getElementById('editVersionCode').value = '';
    document.getElementById('editAppDevice').value = 'HEY-W09';
    document.getElementById('editAppStatus').value = '1';
    document.getElementById('editAppForce').value = 'true';
    document.getElementById('editHideIcon').value = '0';
    document.getElementById('editCanUninstall').value = 'true';
    document.getElementById('editSha1').value = '';
    document.getElementById('editTargetSdk').value = '30';
}

function saveEditedApp() {
    const index = parseInt(document.getElementById('editAppIndex').value);
    if (isNaN(index)) return;
    
    const id = parseInt(document.getElementById('editAppId').value);
    const name = document.getElementById('editAppName').value.trim();
    const packageName = document.getElementById('editAppPackage').value.trim();
    const version = document.getElementById('editAppVersion').value.trim();
    const versionCode = parseInt(document.getElementById('editVersionCode').value);
    const device = document.getElementById('editAppDevice').value.trim();
    const status = parseInt(document.getElementById('editAppStatus').value);
    const isforce = document.getElementById('editAppForce').value === 'true';
    const hideIcon = parseInt(document.getElementById('editHideIcon').value);
    const canUninstall = document.getElementById('editCanUninstall').value === 'true';
    const sha1 = document.getElementById('editSha1').value.trim();
    const targetSdk = parseInt(document.getElementById('editTargetSdk').value);

    if (!name || !packageName || !version || isNaN(id)) {
        alert('请填写所有必填字段！');
        return;
    }

    if (!window.configManager || !window.configManager.config) return;
    
    // 更新应用信息
    const app = window.configManager.config.app_tactics.applist[index];
    if (app) {
        app.id = id;
        app.name = name;
        app.packagename = packageName;
        app.versionname = version;
        app.versioncode = versionCode;
        app.devicetype = device || 'HEY-W09';
        app.status = status;
        app.isforce = isforce;
        app.hide_icon_status = hideIcon;
        app.canuninstall = canUninstall;
        app.sha1 = sha1 || null;
        app.target_sdk_version = targetSdk;
        app.updated_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
        
        window.configManager.markAsModified();
        
        // 更新UI
        updateAppInUI(index, app);
        // 加载商店数据
    window.configManager.loadStoreData();
    
    // 加载用户数据
    window.configManager.loadUserData();
        
        closeEditModal();
        window.configManager.showNotification('应用更新成功！', 'success');
    }
}

function updateAppInUI(index, app) {
    const appCard = document.querySelector(`.app-card[data-index="${index}"]`);
    if (!appCard) return;
    
    // 更新卡片内容
    appCard.querySelector('.app-name').textContent = escapeHtml(app.name);
    appCard.querySelector('.app-version').textContent = `v${escapeHtml(app.versionname)}`;
    appCard.querySelectorAll('.info-value')[0].textContent = escapeHtml(app.packagename);
    appCard.querySelectorAll('.info-value')[1].textContent = escapeHtml(app.devicetype);
    
    // 更新开关状态
    appCard.querySelector('.status-toggle').checked = app.status == 1;
    appCard.querySelector('.force-toggle').checked = app.isforce;
    appCard.querySelector('.hide-toggle').checked = app.hide_icon_status == 1;
    
    // 更新数据路径
    appCard.querySelector('.status-toggle').dataset.path = `app_tactics.applist.${index}.status`;
    appCard.querySelector('.force-toggle').dataset.path = `app_tactics.applist.${index}.isforce`;
    appCard.querySelector('.hide-toggle').dataset.path = `app_tactics.applist.${index}.hide_icon_status`;
}

// 更新 addAppToUI 函数以包含编辑按钮
function addAppToUI(app, index) {
    const appsGrid = document.querySelector('.apps-grid');
    if (!appsGrid) return;

    const appCard = document.createElement('div');
    appCard.className = 'app-card';
    appCard.dataset.index = index;
    appCard.innerHTML = `
        <div class="app-header">
            <span class="app-name">${escapeHtml(app.name)}</span>
            <span class="app-version">v${escapeHtml(app.versionname)}</span>
        </div>
        <div class="app-info">
            <div class="info-row">
                <span class="info-label">包名:</span>
                <span class="info-value">${escapeHtml(app.packagename)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">设备:</span>
                <span class="info-value">${escapeHtml(app.devicetype)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID:</span>
                <span class="info-value">${app.id}</span>
            </div>
            <div class="info-row">
                <span class="info-label">版本代码:</span>
                <span class="info-value">${app.versioncode}</span>
            </div>
        </div>
        <div class="app-controls">
            <label class="switch">
                <input type="checkbox" class="status-toggle" 
                       data-path="app_tactics.applist.${index}.status"
                       ${app.status == 1 ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">启用</span>
            </label>
            
            <label class="switch">
                <input type="checkbox" class="force-toggle" 
                       data-path="app_tactics.applist.${index}.isforce"
                       ${app.isforce ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">强制</span>
            </label>
            
            <label class="switch">
                <input type="checkbox" class="hide-toggle" 
                       data-path="app_tactics.applist.${index}.hide_icon_status"
                       ${app.hide_icon_status == 1 ? 'checked' : ''}>
                <span class="slider"></span>
                <span class="switch-label">隐藏图标</span>
            </label>
            
            <button class="edit-btn" onclick="editApp(${index})">
                <i class="fas fa-edit"></i>
            </button>
            
            <button class="delete-btn" onclick="deleteApp(${index})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    appsGrid.appendChild(appCard);
}
// 在 script.js 中添加以下函数

// 商店管理相关函数
function addStoreApp() {
    document.getElementById('addStoreAppModal').classList.add('active');
}

function closeAddStoreModal() {
    document.getElementById('addStoreAppModal').classList.remove('active');
    clearAddStoreForm();
}

function clearAddStoreForm() {
    document.getElementById('addStoreAppId').value = '';
    document.getElementById('addStoreAppName').value = '';
    document.getElementById('addStoreAppPackage').value = '';
    document.getElementById('addStoreAppVersion').value = '';
    document.getElementById('addStoreVersionCode').value = '';
    document.getElementById('addShortDescript').value = '';
    document.getElementById('addStoreCanUninstall').value = 'true';
    document.getElementById('addStoreIsForce').value = 'false';
}

function editStoreApp(index) {
    // 从全局变量获取商店数据
    if (!window.storeData) {
        console.error('商店数据未加载');
        return;
    }
    
    const app = window.storeData[index];
    if (!app) return;
    
    // 填充表单数据
    document.getElementById('editStoreAppIndex').value = index;
    document.getElementById('editStoreAppId').value = app.id;
    document.getElementById('editStoreAppName').value = app.name;
    document.getElementById('editStoreAppPackage').value = app.packagename;
    document.getElementById('editStoreAppVersion').value = app.versionname;
    document.getElementById('editStoreVersionCode').value = app.versioncode;
    document.getElementById('editAppSize').value = app.size || 0;
    document.getElementById('editStoreTargetSdk').value = app.target_sdk_version || 28;
    document.getElementById('editIconPath').value = app.iconpath || '';
    document.getElementById('editAppPath').value = app.path || '';
    document.getElementById('editShortDescript').value = app.shortdescript || '';
    document.getElementById('editLongDescript').value = app.longdescript || '';
    document.getElementById('editMd5Sum').value = app.md5sum || '';
    document.getElementById('editStoreHideIcon').value = app.hide_icon_status || '0';
    document.getElementById('editStoreCanUninstall').value = app.canuninstall ? 'true' : 'false';
    document.getElementById('editStoreIsForce').value = app.isforce ? 'true' : 'false';
    document.getElementById('editClearCache').value = app.clear_app_cache_status || '1';
    document.getElementById('editSortWeight').value = app.sortweight || 0;
    document.getElementById('editRating').value = app.rating || '5.0';
    
    // 处理截图URL数组
    const screenshotsText = (app.screenshot && Array.isArray(app.screenshot)) 
        ? app.screenshot.join('\n') 
        : '';
    document.getElementById('editScreenshots').value = screenshotsText;
    
    // 显示模态框
    document.getElementById('editStoreAppModal').classList.add('active');
}

function closeEditStoreModal() {
    document.getElementById('editStoreAppModal').classList.remove('active');
    clearEditStoreForm();
}

function clearEditStoreForm() {
    document.getElementById('editStoreAppIndex').value = '';
    document.getElementById('editStoreAppId').value = '';
    document.getElementById('editStoreAppName').value = '';
    document.getElementById('editStoreAppPackage').value = '';
    document.getElementById('editStoreAppVersion').value = '';
    document.getElementById('editStoreVersionCode').value = '';
    document.getElementById('editAppSize').value = '';
    document.getElementById('editStoreTargetSdk').value = '28';
    document.getElementById('editIconPath').value = '';
    document.getElementById('editAppPath').value = '';
    document.getElementById('editShortDescript').value = '';
    document.getElementById('editLongDescript').value = '';
    document.getElementById('editScreenshots').value = '';
    document.getElementById('editMd5Sum').value = '';
    document.getElementById('editStoreHideIcon').value = '0';
    document.getElementById('editStoreCanUninstall').value = 'true';
    document.getElementById('editStoreIsForce').value = 'false';
    document.getElementById('editClearCache').value = '1';
    document.getElementById('editSortWeight').value = '0';
    document.getElementById('editRating').value = '5.0';
}

async function saveNewStoreApp() {
    const id = parseInt(document.getElementById('addStoreAppId').value);
    const name = document.getElementById('addStoreAppName').value.trim();
    const packageName = document.getElementById('addStoreAppPackage').value.trim();
    const version = document.getElementById('addStoreAppVersion').value.trim();
    const versionCode = parseInt(document.getElementById('addStoreVersionCode').value) || 1;
    const shortDescript = document.getElementById('addShortDescript').value.trim();
    const canUninstall = document.getElementById('addStoreCanUninstall').value === 'true';
    const isForce = document.getElementById('addStoreIsForce').value === 'true';

    if (!id || !name || !packageName || !version) {
        alert('请填写所有必填字段！');
        return;
    }

    const newApp = {
        id: id,
        name: name,
        packagename: packageName,
        versionname: version,
        versioncode: versionCode,
        shortdescript: shortDescript || null,
        longdescript: null,
        devicetype: "HEY-W09",
        grant_type: 5,
        grant_to: 776883,
        hide_icon_status: 0,
        clear_app_cache_status: 1,
        groupid: 1,
        author: null,
        type: 1,
        status: 1,
        appcatalog: false,
        isnew: true,
        creator: 1,
        isforce: isForce,
        canuninstall: canUninstall,
        exception_white_url: 0,
        is_trust: true,
        downloadcount: 0,
        size: 0,
        iconpath: null,
        path: null,
        md5sum: null,
        sha1: null,
        target_sdk_version: 28,
        rating: "5.0",
        star_percent_1: 0,
        star_percent_2: 0,
        star_percent_3: 0,
        star_percent_4: 0,
        star_percent_5: 100,
        forcetime: null,
        sortweight: 0,
        screenshot: [],
        totalcomment: 0,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
    };

    try {
        // 发送到后端保存
        const response = await fetch('save_store.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify([...window.storeData, newApp])
        });

        const result = await response.json();
        if (result.success) {
            // 重新加载页面或更新UI
            location.reload();
        } else {
            alert('保存失败: ' + result.message);
        }
    } catch (error) {
        console.error('保存商店应用失败:', error);
        alert('保存失败，请检查网络连接');
    }
}

async function saveEditedStoreApp() {
    const index = parseInt(document.getElementById('editStoreAppIndex').value);
    if (isNaN(index)) return;
    
    const id = parseInt(document.getElementById('editStoreAppId').value);
    const name = document.getElementById('editStoreAppName').value.trim();
    const packageName = document.getElementById('editStoreAppPackage').value.trim();
    const version = document.getElementById('editStoreAppVersion').value.trim();
    const versionCode = parseInt(document.getElementById('editStoreVersionCode').value);
    const size = parseInt(document.getElementById('editAppSize').value) || 0;
    const targetSdk = parseInt(document.getElementById('editStoreTargetSdk').value) || 28;
    const iconPath = document.getElementById('editIconPath').value.trim();
    const appPath = document.getElementById('editAppPath').value.trim();
    const shortDescript = document.getElementById('editShortDescript').value.trim();
    const longDescript = document.getElementById('editLongDescript').value.trim();
    const md5Sum = document.getElementById('editMd5Sum').value.trim();
    const hideIcon = parseInt(document.getElementById('editStoreHideIcon').value) || 0;
    const canUninstall = document.getElementById('editStoreCanUninstall').value === 'true';
    const isForce = document.getElementById('editStoreIsForce').value === 'true';
    const clearCache = parseInt(document.getElementById('editClearCache').value) || 1;
    const sortWeight = parseInt(document.getElementById('editSortWeight').value) || 0;
    const rating = document.getElementById('editRating').value.trim();
    
    // 处理截图
    const screenshotsText = document.getElementById('editScreenshots').value.trim();
    const screenshot = screenshotsText ? screenshotsText.split('\n').filter(url => url.trim()) : [];

    if (!id || !name || !packageName || !version) {
        alert('请填写所有必填字段！');
        return;
    }

    if (!window.storeData) {
        alert('商店数据未加载');
        return;
    }
    
    // 更新应用信息
    window.storeData[index] = {
        ...window.storeData[index],
        id: id,
        name: name,
        packagename: packageName,
        versionname: version,
        versioncode: versionCode,
        size: size,
        target_sdk_version: targetSdk,
        iconpath: iconPath || null,
        path: appPath || null,
        shortdescript: shortDescript || null,
        longdescript: longDescript || null,
        md5sum: md5Sum || null,
        hide_icon_status: hideIcon,
        canuninstall: canUninstall,
        isforce: isForce,
        clear_app_cache_status: clearCache,
        sortweight: sortWeight,
        rating: rating || "5.0",
        screenshot: screenshot,
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
    };

    try {
        // 发送到后端保存
        const response = await fetch('save_store.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(window.storeData)
        });

        const result = await response.json();
        if (result.success) {
            closeEditStoreModal();
            location.reload();
        } else {
            alert('保存失败: ' + result.message);
        }
    } catch (error) {
        console.error('保存商店应用失败:', error);
        alert('保存失败，请检查网络连接');
    }
}

async function deleteStoreApp(index) {
    if (!confirm('确定要删除这个商店应用吗？')) return;

    if (!window.storeData) return;
    
    // 从数组中删除
    window.storeData.splice(index, 1);
    
    try {
        // 发送到后端保存
        const response = await fetch('save_store.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(window.storeData)
        });

        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('删除失败: ' + result.message);
        }
    } catch (error) {
        console.error('删除商店应用失败:', error);
        alert('删除失败，请检查网络连接');
    }
}

// 用户管理相关函数
// function addUser() {
//     alert('添加用户功能待实现');
//     // 这里可以打开添加用户的模态框
//     // document.getElementById('addUserModal').classList.add('active');
// }

function editUser() {
    document.getElementById('editUserModal').classList.add('active');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

async function saveEditedUser() {
    const id = parseInt(document.getElementById('editUserId').value);
    const name = document.getElementById('editUserName').value.trim();
    const email = document.getElementById('editUserEmail').value.trim();
    const school = parseInt(document.getElementById('editUserSchool').value);
    const usergroup = parseInt(document.getElementById('editUserGroup').value);
    const status = parseInt(document.getElementById('editUserStatus').value);
    const freeControl = parseInt(document.getElementById('editUserFreeControl').value);
    const focus = parseInt(document.getElementById('editUserFocus').value);
    const schoolName = document.getElementById('editSchoolName').value.trim();
    const schoolAbbr = document.getElementById('editSchoolAbbr').value.trim();
    const schoolUuid = document.getElementById('editSchoolUuid').value.trim();

    if (!id || !name || !email || isNaN(school) || isNaN(usergroup)) {
        alert('请填写所有必填字段！');
        return;
    }

    const userData = {
        id: id,
        name: name,
        email: email,
        school: school,
        usergroup: usergroup,
        status: status,
        free_control: freeControl,
        focus: focus,
        schoolinfo: {
            id: school,
            school_id: schoolUuid,
            name: schoolName,
            abbr: schoolAbbr
        },
        groupinfo: window.userData ? (window.userData.groupinfo || []) : []
    };

    try {
        // 发送到后端保存
        const response = await fetch('save_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userData)
        });

        const result = await response.json();
        if (result.success) {
            closeEditUserModal();
            location.reload();
        } else {
            alert('保存失败: ' + result.message);
        }
    } catch (error) {
        console.error('保存用户数据失败:', error);
        alert('保存失败，请检查网络连接');
    }
}

// 监听商店应用的开关变化
document.addEventListener('change', (e) => {
    if (e.target.classList.contains('store-status-toggle') || 
        e.target.classList.contains('store-hide-toggle')) {
        
        const path = e.target.dataset.path;
        const [store, index, field] = path.split('.');
        
        if (window.storeData && window.storeData[parseInt(index)]) {
            const value = e.target.checked ? 1 : 0;
            window.storeData[parseInt(index)][field] = value;
            
            // 保存更改
            saveStoreChanges();
        }
    }
});

// 保存商店更改的函数
async function saveStoreChanges() {
    if (!window.storeData) return;
    
    try {
        const response = await fetch('save_store.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(window.storeData)
        });

        const result = await response.json();
        if (!result.success) {
            console.error('保存商店数据失败:', result.message);
        }
    } catch (error) {
        console.error('保存商店数据失败:', error);
    }
}

// 用户管理相关函数
function addUser() {
    // 清空表单
    clearAddUserForm();
    // 显示添加用户模态框
    document.getElementById('addUserModal').classList.add('active');
}

function clearAddUserForm() {
    document.getElementById('addUserId').value = '';
    document.getElementById('addUserName').value = '';
    document.getElementById('addUserEmail').value = '';
    document.getElementById('addUserSchool').value = '776883';
    document.getElementById('addUserGroup').value = '1128989';
    document.getElementById('addUserStatus').value = '1';
    document.getElementById('addUserFreeControl').value = '1';
    document.getElementById('addUserFocus').value = '1';
    document.getElementById('addSchoolName').value = '成都市泡桐树中学机盟';
    document.getElementById('addSchoolAbbr').value = '机盟';
    document.getElementById('addSchoolUuid').value = 'ae164c7d-bf69-46fb-93e2-f19172b1bc61';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
    clearAddUserForm();
}

async function saveNewUser() {
    const id = parseInt(document.getElementById('addUserId').value);
    const name = document.getElementById('addUserName').value.trim();
    const email = document.getElementById('addUserEmail').value.trim();
    const school = parseInt(document.getElementById('addUserSchool').value);
    const usergroup = parseInt(document.getElementById('addUserGroup').value);
    const status = parseInt(document.getElementById('addUserStatus').value);
    const freeControl = parseInt(document.getElementById('addUserFreeControl').value);
    const focus = parseInt(document.getElementById('addUserFocus').value);
    const schoolName = document.getElementById('addSchoolName').value.trim();
    const schoolAbbr = document.getElementById('addSchoolAbbr').value.trim();
    const schoolUuid = document.getElementById('addSchoolUuid').value.trim();

    if (!id || !name || !email || isNaN(school) || isNaN(usergroup)) {
        alert('请填写所有必填字段！');
        return;
    }

    const userData = {
        id: id,
        name: name,
        email: email,
        school: school,
        usergroup: usergroup,
        status: status,
        free_control: freeControl,
        focus: focus,
        schoolinfo: {
            id: school,
            school_id: schoolUuid,
            name: schoolName,
            abbr: schoolAbbr
        },
        groupinfo: [{
            id: usergroup,
            school: school,
            name: "(初三)2026学部/2026学部导师02班",
            description: null,
            created_at: "2023-08-28 10:44:48",
            updated_at: "2023-08-28 10:44:48"
        }]
    };

    try {
        // 发送到后端保存
        const response = await fetch('save_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userData)
        });

        const result = await response.json();
        if (result.success) {
            closeAddUserModal();
            location.reload();
        } else {
            alert('保存失败: ' + result.message);
        }
    } catch (error) {
        console.error('保存用户数据失败:', error);
        alert('保存失败，请检查网络连接');
    }
}