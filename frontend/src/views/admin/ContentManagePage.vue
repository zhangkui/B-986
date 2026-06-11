<template>
  <div class="content-manage-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>内容管理</span>
        </div>
      </template>
      <el-tabs v-model="activeTab">
        <el-tab-pane label="平台配置" name="config">
          <el-form :model="configForm" label-width="120px">
            <el-form-item label="平台标题">
              <el-input v-model="configForm.platform_title" placeholder="请输入平台标题" />
            </el-form-item>
            <el-form-item label="须知内容">
              <el-input v-model="configForm.notice_content" type="textarea" :rows="6" placeholder="请输入须知内容" />
            </el-form-item>
            <el-divider content-position="left">广告图片配置</el-divider>
            <el-form-item label="首页广告图">
              <el-input v-model="configForm.home_banner" placeholder="请输入首页广告图片 URL" />
            </el-form-item>
            <el-form-item label="二级页广告图">
              <el-input v-model="configForm.level2_banner" placeholder="请输入二级页广告图片 URL" />
            </el-form-item>
            <el-form-item label="药品二级广告">
              <el-input v-model="configForm.level2_banner_cat1" placeholder="药品二级广告 URL" />
            </el-form-item>
            <el-form-item label="器械二级广告">
              <el-input v-model="configForm.level2_banner_cat2" placeholder="医疗器械二级广告 URL" />
            </el-form-item>
            <el-form-item label="化妆品二级广告">
              <el-input v-model="configForm.level2_banner_cat3" placeholder="化妆品二级广告 URL" />
            </el-form-item>
            <el-form-item label="药品三级广告">
              <el-input v-model="configForm.level3_banner_cat1" placeholder="药品三级广告 URL" />
            </el-form-item>
            <el-form-item label="器械三级广告">
              <el-input v-model="configForm.level3_banner_cat2" placeholder="医疗器械三级广告 URL" />
            </el-form-item>
            <el-form-item label="化妆品三级广告">
              <el-input v-model="configForm.level3_banner_cat3" placeholder="化妆品三级广告 URL" />
            </el-form-item>
            <el-divider content-position="left">按钮文案配置</el-divider>
            <el-form-item label="首页按钮文案">
              <el-input v-model="configForm.btn_home" placeholder="默认：首页" />
            </el-form-item>
            <el-form-item label="我要报告文案">
              <el-input v-model="configForm.btn_report" placeholder="默认：我要报告" />
            </el-form-item>
            <el-form-item label="立即报告文案">
              <el-input v-model="configForm.btn_report_now" placeholder="默认：立即报告" />
            </el-form-item>
            <el-form-item label="返回按钮文案">
              <el-input v-model="configForm.btn_back" placeholder="默认：返回" />
            </el-form-item>
            <el-form-item label="填写报告文案">
              <el-input v-model="configForm.btn_fill_report" placeholder="默认：填写报告" />
            </el-form-item>
            <el-form-item label="提交按钮文案">
              <el-input v-model="configForm.btn_submit" placeholder="默认：提交" />
            </el-form-item>
            <el-form-item label="同意按钮文案">
              <el-input v-model="configForm.btn_agree" placeholder="默认：同意" />
            </el-form-item>
            <el-form-item label="拒绝按钮文案">
              <el-input v-model="configForm.btn_reject" placeholder="默认：拒绝" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="saveConfig" :loading="saving">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
        
        <el-tab-pane label="知识信息" name="knowledge">
          <div class="toolbar">
            <el-button type="primary" @click="addKnowledge">添加知识</el-button>
          </div>
          <el-table :data="knowledgeList" border>
            <el-table-column prop="id" label="ID" width="60" />
            <el-table-column prop="title" label="标题" />
            <el-table-column prop="summary" label="摘要" show-overflow-tooltip />
            <el-table-column prop="category_id" label="分类" width="100">
              <template #default="{ row }">
                {{ getCategoryName(row.category_id) }}
              </template>
            </el-table-column>
            <el-table-column prop="page_level" label="页面" width="80" />
            <el-table-column prop="status" label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150">
              <template #default="{ row }">
                <el-button size="small" @click="editKnowledge(row)">编辑</el-button>
                <el-button size="small" type="danger" @click="deleteKnowledge(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
        
        <el-tab-pane label="注意事项" name="notices">
          <div class="toolbar">
            <el-button type="primary" @click="addNotice">添加注意事项</el-button>
          </div>
          <el-table :data="noticesList" border>
            <el-table-column prop="id" label="ID" width="60" />
            <el-table-column prop="title" label="标题" />
            <el-table-column prop="category_id" label="分类" width="100">
              <template #default="{ row }">
                {{ getCategoryName(row.category_id) || '通用' }}
              </template>
            </el-table-column>
            <el-table-column prop="content" label="内容" show-overflow-tooltip />
            <el-table-column label="操作" width="150">
              <template #default="{ row }">
                <el-button size="small" @click="editNotice(row)">编辑</el-button>
                <el-button size="small" type="danger" @click="deleteNotice(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
        
        <el-tab-pane label="分类管理" name="categories">
          <div class="toolbar">
            <el-button type="primary" @click="addCategory">添加分类</el-button>
          </div>
          <el-table :data="categoriesList" border>
            <el-table-column prop="id" label="ID" width="60" />
            <el-table-column prop="name" label="名称" />
            <el-table-column prop="sort_order" label="排序" width="80" />
            <el-table-column prop="status" label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150">
              <template #default="{ row }">
                <el-button size="small" @click="editCategory(row)">编辑</el-button>
                <el-button size="small" type="danger" @click="deleteCategory(row.id)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- Knowledge Dialog -->
    <el-dialog v-model="knowledgeDialogVisible" :title="knowledgeEditing ? '编辑知识' : '添加知识'" width="600px">
      <el-form :model="knowledgeForm" label-width="100px">
        <el-form-item label="标题" required>
          <el-input v-model="knowledgeForm.title" />
        </el-form-item>
        <el-form-item label="摘要">
          <el-input v-model="knowledgeForm.summary" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="内容">
          <el-input v-model="knowledgeForm.content" type="textarea" :rows="4" />
        </el-form-item>
        <el-form-item label="所属分类">
          <el-select v-model="knowledgeForm.category_id" placeholder="选择分类">
            <el-option label="通用" :value="null" />
            <el-option v-for="cat in categoriesList" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="页面级别">
          <el-select v-model="knowledgeForm.page_level">
            <el-option label="首页" :value="1" />
            <el-option label="二级页" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="knowledgeForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="knowledgeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveKnowledge" :loading="saving">保存</el-button>
      </template>
    </el-dialog>

    <!-- Notice Dialog -->
    <el-dialog v-model="noticeDialogVisible" :title="noticeEditing ? '编辑注意事项' : '添加注意事项'" width="600px">
      <el-form :model="noticeForm" label-width="100px">
        <el-form-item label="标题" required>
          <el-input v-model="noticeForm.title" />
        </el-form-item>
        <el-form-item label="所属分类">
          <el-select v-model="noticeForm.category_id" placeholder="选择分类">
            <el-option v-for="cat in categoriesList" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="内容" required>
          <el-input v-model="noticeForm.content" type="textarea" :rows="6" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="noticeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveNotice" :loading="saving">保存</el-button>
      </template>
    </el-dialog>

    <!-- Category Dialog -->
    <el-dialog v-model="categoryDialogVisible" :title="categoryEditing ? '编辑分类' : '添加分类'" width="400px">
      <el-form :model="categoryForm" label-width="80px">
        <el-form-item label="名称" required>
          <el-input v-model="categoryForm.name" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="categoryForm.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="categoryForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="categoryDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveCategory" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const API_BASE = '/api'
const activeTab = ref('config')
const saving = ref(false)

// Config
const configForm = reactive({
  platform_title: '',
  notice_content: '',
  home_banner: '',
  level2_banner: '',
  level2_banner_cat1: '',
  level2_banner_cat2: '',
  level2_banner_cat3: '',
  level3_banner_cat1: '',
  level3_banner_cat2: '',
  level3_banner_cat3: '',
  btn_home: '',
  btn_report: '',
  btn_report_now: '',
  btn_back: '',
  btn_fill_report: '',
  btn_submit: '',
  btn_agree: '',
  btn_reject: ''
})

// Knowledge
const knowledgeList = ref([])
const knowledgeDialogVisible = ref(false)
const knowledgeEditing = ref(false)
const knowledgeForm = reactive({
  id: null,
  title: '',
  summary: '',
  content: '',
  category_id: null,
  page_level: 1,
  status: 1
})

// Notices
const noticesList = ref([])
const noticeDialogVisible = ref(false)
const noticeEditing = ref(false)
const noticeForm = reactive({
  id: null,
  title: '',
  category_id: 1,
  content: ''
})

// Categories
const categoriesList = ref([])
const categoryDialogVisible = ref(false)
const categoryEditing = ref(false)
const categoryForm = reactive({
  id: null,
  name: '',
  sort_order: 0,
  status: 1
})

function getToken() {
  return localStorage.getItem('admin_token')
}

function getCategoryName(id) {
  if (!id) return '通用'
  const cat = categoriesList.value.find(c => c.id === id)
  return cat ? cat.name : ''
}

async function loadConfig() {
  try {
    const res = await fetch(`${API_BASE}/admin/config`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      const config = {}
      data.data.forEach(item => {
        config[item.config_key] = item.config_value
      })
      Object.keys(configForm).forEach((key) => {
        configForm[key] = config[key] || ''
      })
    }
  } catch (e) {
    console.error('加载配置失败:', e)
  }
}

async function saveConfig() {
  saving.value = true
  try {
    for (const [configKey, configValue] of Object.entries(configForm)) {
      const configType = configKey === 'notice_content'
        ? 'html'
        : (configKey.includes('banner') ? 'image' : 'text')
      await fetch(`${API_BASE}/admin/config`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify({ config_key: configKey, config_value: configValue || '', config_type: configType })
      })
    }
    ElMessage.success('保存成功')
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function loadKnowledge() {
  try {
    const res = await fetch(`${API_BASE}/admin/knowledge`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      knowledgeList.value = data.data || []
    }
  } catch (e) {
    console.error('加载知识失败:', e)
  }
}

function addKnowledge() {
  Object.assign(knowledgeForm, { id: null, title: '', summary: '', content: '', category_id: null, page_level: 1, status: 1 })
  knowledgeEditing.value = false
  knowledgeDialogVisible.value = true
}

function editKnowledge(row) {
  Object.assign(knowledgeForm, { ...row })
  knowledgeEditing.value = true
  knowledgeDialogVisible.value = true
}

async function saveKnowledge() {
  if (!knowledgeForm.title) {
    ElMessage.warning('请输入标题')
    return
  }
  saving.value = true
  try {
    const method = knowledgeEditing.value ? 'PUT' : 'POST'
    const res = await fetch(`${API_BASE}/admin/knowledge`, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(knowledgeForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('保存成功')
      knowledgeDialogVisible.value = false
      loadKnowledge()
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deleteKnowledge(id) {
  try {
    await fetch(`${API_BASE}/admin/knowledge`, {
      method: 'DELETE',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ id })
    })
    ElMessage.success('删除成功')
    loadKnowledge()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

async function loadNotices() {
  try {
    const res = await fetch(`${API_BASE}/admin/notices`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      noticesList.value = data.data || []
    }
  } catch (e) {
    console.error('加载注意事项失败:', e)
  }
}

function addNotice() {
  Object.assign(noticeForm, { id: null, title: '', category_id: 1, content: '' })
  noticeEditing.value = false
  noticeDialogVisible.value = true
}

function editNotice(row) {
  Object.assign(noticeForm, { ...row })
  noticeEditing.value = true
  noticeDialogVisible.value = true
}

async function saveNotice() {
  if (!noticeForm.title || !noticeForm.content) {
    ElMessage.warning('请填写完整信息')
    return
  }
  saving.value = true
  try {
    const method = noticeEditing.value ? 'PUT' : 'POST'
    const res = await fetch(`${API_BASE}/admin/notices`, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(noticeForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('保存成功')
      noticeDialogVisible.value = false
      loadNotices()
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deleteNotice(id) {
  try {
    await fetch(`${API_BASE}/admin/notices`, {
      method: 'DELETE',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ id })
    })
    ElMessage.success('删除成功')
    loadNotices()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

async function loadCategories() {
  try {
    const res = await fetch(`${API_BASE}/admin/categories`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      categoriesList.value = data.data || []
    }
  } catch (e) {
    console.error('加载分类失败:', e)
  }
}

function addCategory() {
  Object.assign(categoryForm, { id: null, name: '', sort_order: 0, status: 1 })
  categoryEditing.value = false
  categoryDialogVisible.value = true
}

function editCategory(row) {
  Object.assign(categoryForm, { ...row })
  categoryEditing.value = true
  categoryDialogVisible.value = true
}

async function saveCategory() {
  if (!categoryForm.name) {
    ElMessage.warning('请输入名称')
    return
  }
  saving.value = true
  try {
    const method = categoryEditing.value ? 'PUT' : 'POST'
    const res = await fetch(`${API_BASE}/admin/categories`, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(categoryForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('保存成功')
      categoryDialogVisible.value = false
      loadCategories()
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deleteCategory(id) {
  try {
    await fetch(`${API_BASE}/admin/categories`, {
      method: 'DELETE',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ id })
    })
    ElMessage.success('删除成功')
    loadCategories()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

onMounted(async () => {
  await loadCategories()
  await loadConfig()
  await loadKnowledge()
  await loadNotices()
})
</script>

<style scoped>
.content-manage-page {
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
