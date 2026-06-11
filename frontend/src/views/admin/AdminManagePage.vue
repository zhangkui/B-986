<template>
  <div class="admin-manage-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>管理员管理</span>
        </div>
      </template>
      <el-alert title="创建管理员并分配功能权限和地区权限。" type="info" :closable="false" style="margin-bottom: 16px;" />
      
      <div class="toolbar">
        <el-button type="primary" @click="addAdmin">添加管理员</el-button>
      </div>

      <el-table :data="adminList" border>
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="username" label="用户名" />
        <el-table-column prop="real_name" label="真实姓名" />
        <el-table-column prop="role" label="角色" width="100">
          <template #default="{ row }">
            <el-tag :type="row.role === 'super_admin' ? 'danger' : 'primary'">
              {{ row.role === 'super_admin' ? '超级管理员' : '管理员' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="permissions" label="功能权限" show-overflow-tooltip>
          <template #default="{ row }">
            {{ getPermissionNames(row.permissions) }}
          </template>
        </el-table-column>
        <el-table-column prop="region_ids" label="地区权限" show-overflow-tooltip>
          <template #default="{ row }">
            {{ getRegionNames(row.region_ids) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{ row }">
            <el-button size="small" @click="editAdmin(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="deleteAdmin(row.id)" :disabled="row.role === 'super_admin'">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑管理员' : '添加管理员'" width="600px">
      <el-form :model="adminForm" label-width="100px">
        <el-form-item label="用户名" required>
          <el-input v-model="adminForm.username" :disabled="editing" />
        </el-form-item>
        <el-form-item label="密码" v-if="!editing" required>
          <el-input v-model="adminForm.password" type="password" show-password />
        </el-form-item>
        <el-form-item label="新密码" v-else>
          <el-input v-model="adminForm.password" type="password" show-password placeholder="留空则不修改" />
        </el-form-item>
        <el-form-item label="真实姓名">
          <el-input v-model="adminForm.real_name" />
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="adminForm.role">
            <el-option label="管理员" value="admin" />
            <el-option label="超级管理员" value="super_admin" />
          </el-select>
        </el-form-item>
        <el-form-item label="功能权限">
          <el-checkbox-group v-model="selectedPermissions">
            <el-checkbox label="content">内容管理</el-checkbox>
            <el-checkbox label="form">表单配置</el-checkbox>
            <el-checkbox label="admin">管理员管理</el-checkbox>
            <el-checkbox label="report">报表管理</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="地区权限">
          <el-select v-model="selectedRegions" multiple placeholder="选择可管理的地区">
            <el-option v-for="r in regions" :key="r.id" :label="r.name" :value="r.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="adminForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveAdmin" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const API_BASE = '/api'
const adminList = ref([])
const regions = ref([])
const dialogVisible = ref(false)
const editing = ref(false)
const saving = ref(false)
const selectedPermissions = ref([])
const selectedRegions = ref([])

const adminForm = reactive({
  id: null,
  username: '',
  password: '',
  real_name: '',
  role: 'admin',
  permissions: [],
  region_ids: [],
  status: 1
})

const permissionMap = {
  content: '内容管理',
  form: '表单配置',
  admin: '管理员管理',
  report: '报表管理'
}

function getToken() {
  return localStorage.getItem('admin_token')
}

function getPermissionNames(permissions) {
  if (!permissions || !Array.isArray(permissions)) return ''
  return permissions.map(p => permissionMap[p] || p).join(', ')
}

function getRegionNames(regionIds) {
  if (!regionIds || !Array.isArray(regionIds)) return ''
  return regionIds.map(id => {
    const r = regions.value.find(reg => reg.id === id)
    return r ? r.name : id
  }).join(', ')
}

async function loadAdmins() {
  try {
    const res = await fetch(`${API_BASE}/admin/admins`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      adminList.value = data.data || []
    }
  } catch (e) {
    console.error('加载管理员失败:', e)
  }
}

async function loadRegions() {
  try {
    const res = await fetch(`${API_BASE}/regions`)
    const data = await res.json()
    if (data.success) {
      regions.value = data.data.flatMap(p => p.children?.length ? p.children : [p]) || []
    }
  } catch (e) {
    console.error('加载地区失败:', e)
  }
}

function addAdmin() {
  Object.assign(adminForm, {
    id: null,
    username: '',
    password: '',
    real_name: '',
    role: 'admin',
    permissions: [],
    region_ids: [],
    status: 1
  })
  selectedPermissions.value = []
  selectedRegions.value = []
  editing.value = false
  dialogVisible.value = true
}

function editAdmin(row) {
  Object.assign(adminForm, { ...row })
  if (typeof adminForm.permissions === 'string') {
    adminForm.permissions = JSON.parse(adminForm.permissions)
  }
  if (typeof adminForm.region_ids === 'string') {
    adminForm.region_ids = JSON.parse(adminForm.region_ids)
  }
  selectedPermissions.value = adminForm.permissions || []
  selectedRegions.value = adminForm.region_ids || []
  editing.value = true
  dialogVisible.value = true
}

async function saveAdmin() {
  if (!editing.value && (!adminForm.username || !adminForm.password)) {
    ElMessage.warning('请填写用户名和密码')
    return
  }
  adminForm.permissions = selectedPermissions.value
  adminForm.region_ids = selectedRegions.value
  
  saving.value = true
  try {
    const method = editing.value ? 'PUT' : 'POST'
    const res = await fetch(`${API_BASE}/admin/admins`, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(adminForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadAdmins()
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deleteAdmin(id) {
  try {
    await ElMessageBox.confirm('确定要删除该管理员吗？', '提示', { type: 'warning' })
    await fetch(`${API_BASE}/admin/admins`, {
      method: 'DELETE',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ id })
    })
    ElMessage.success('删除成功')
    loadAdmins()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

onMounted(async () => {
  await loadRegions()
  await loadAdmins()
})
</script>

<style scoped>
.admin-manage-page {
  padding: 20px;
}
.card-header {
  font-size: 18px;
  font-weight: 600;
}
.toolbar {
  margin-bottom: 16px;
}
</style>
