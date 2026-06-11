<template>
  <div class="form-config-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>表单配置</span>
        </div>
      </template>
      <el-alert title="为四级页面配置动态填报字段。字段将按排序顺序在移动端表单中显示。" type="info" :closable="false" style="margin-bottom: 16px;" />
      
      <div class="toolbar">
        <el-select v-model="selectedCategory" placeholder="选择分类" @change="loadFields">
          <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
        </el-select>
        <el-button type="primary" @click="addField" :disabled="!selectedCategory">添加字段</el-button>
      </div>

      <el-table :data="fieldsList" border v-if="selectedCategory">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="field_name" label="字段名称" />
        <el-table-column prop="field_type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag>{{ getTypeName(row.field_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_required" label="必填" width="60">
          <template #default="{ row }">
            {{ row.is_required ? '是' : '否' }}
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{ row }">
            <el-button size="small" @click="editField(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="deleteField(row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-else description="请先选择分类" />
    </el-card>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑字段' : '添加字段'" width="500px">
      <el-form :model="fieldForm" label-width="100px">
        <el-form-item label="字段名称" required>
          <el-input v-model="fieldForm.field_name" placeholder="如：患者姓名" />
        </el-form-item>
        <el-form-item label="字段类型" required>
          <el-select v-model="fieldForm.field_type" placeholder="选择类型">
            <el-option label="文本" value="text" />
            <el-option label="多行文本" value="textarea" />
            <el-option label="日期" value="date" />
            <el-option label="单选" value="radio" />
            <el-option label="图片上传" value="image" />
            <el-option label="地区选择" value="region" />
          </el-select>
        </el-form-item>
        <el-form-item label="选项配置" v-if="fieldForm.field_type === 'radio'">
          <el-input v-model="fieldOptionsText" placeholder="选项用逗号分隔，如：男,女" />
        </el-form-item>
        <el-form-item label="占位符" v-if="['text','textarea'].includes(fieldForm.field_type)">
          <el-input v-model="fieldForm.field_options.placeholder" placeholder="输入提示文字" />
        </el-form-item>
        <el-form-item label="文本框行数" v-if="fieldForm.field_type === 'textarea'">
          <el-input-number v-model="fieldForm.field_options.rows" :min="2" :max="12" />
        </el-form-item>
        <el-form-item label="上传数量" v-if="fieldForm.field_type === 'image'">
          <el-input-number v-model="fieldForm.field_options.max_count" :min="1" :max="9" />
        </el-form-item>
        <el-form-item label="必填">
          <el-switch v-model="fieldForm.is_required" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="fieldForm.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="fieldForm.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveField" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'

const API_BASE = '/api'
const selectedCategory = ref(null)
const categories = ref([])
const fieldsList = ref([])
const dialogVisible = ref(false)
const editing = ref(false)
const saving = ref(false)
const fieldOptionsText = ref('')

const fieldForm = reactive({
  id: null,
  field_name: '',
  field_type: 'text',
  field_options: {
    placeholder: '',
    rows: 3,
    max_count: 3
  },
  is_required: 0,
  sort_order: 0,
  status: 1,
  category_id: null
})

const typeMap = {
  text: '文本',
  textarea: '多行文本',
  date: '日期',
  radio: '单选',
  image: '图片',
  region: '地区'
}

function getToken() {
  return localStorage.getItem('admin_token')
}

function getTypeName(type) {
  return typeMap[type] || type
}

async function loadCategories() {
  try {
    const res = await fetch(`${API_BASE}/categories`)
    const data = await res.json()
    if (data.success) {
      categories.value = data.data || []
      if (categories.value.length > 0) {
        selectedCategory.value = categories.value[0].id
      }
    }
  } catch (e) {
    console.error('加载分类失败:', e)
  }
}

async function loadFields() {
  if (!selectedCategory.value) return
  try {
    const res = await fetch(`${API_BASE}/admin/form-fields?category_id=${selectedCategory.value}`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      fieldsList.value = data.data || []
    }
  } catch (e) {
    console.error('加载字段失败:', e)
  }
}

function addField() {
  Object.assign(fieldForm, {
    id: null,
    field_name: '',
    field_type: 'text',
    field_options: { placeholder: '', rows: 3, max_count: 3 },
    is_required: 0,
    sort_order: fieldsList.value.length,
    status: 1,
    category_id: selectedCategory.value
  })
  fieldOptionsText.value = ''
  editing.value = false
  dialogVisible.value = true
}

function editField(row) {
  Object.assign(fieldForm, { ...row })
  if (typeof fieldForm.field_options === 'string') {
    fieldForm.field_options = JSON.parse(fieldForm.field_options)
  }
  if (!fieldForm.field_options) {
    fieldForm.field_options = { placeholder: '', rows: 3, max_count: 3 }
  }
  if (fieldForm.field_type === 'radio' && fieldForm.field_options.options) {
    fieldOptionsText.value = fieldForm.field_options.options.join(',')
  } else {
    fieldOptionsText.value = ''
  }
  editing.value = true
  dialogVisible.value = true
}

async function saveField() {
  if (!fieldForm.field_name || !fieldForm.field_type) {
    ElMessage.warning('请填写完整信息')
    return
  }
  
  const options = { ...fieldForm.field_options }
  if (fieldForm.field_type === 'radio' && fieldOptionsText.value) {
    options.options = fieldOptionsText.value.split(',').map(s => s.trim()).filter(s => s)
  }
  fieldForm.field_options = options
  fieldForm.category_id = selectedCategory.value
  
  saving.value = true
  try {
    const method = editing.value ? 'PUT' : 'POST'
    const res = await fetch(`${API_BASE}/admin/form-fields`, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(fieldForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadFields()
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

async function deleteField(id) {
  try {
    await fetch(`${API_BASE}/admin/form-fields`, {
      method: 'DELETE',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify({ id })
    })
    ElMessage.success('删除成功')
    loadFields()
  } catch (e) {
    ElMessage.error('删除失败')
  }
}

onMounted(async () => {
  await loadCategories()
  if (selectedCategory.value) {
    await loadFields()
  }
})
</script>

<style scoped>
.form-config-page {
  padding: 20px;
}
.card-header {
  font-size: 18px;
  font-weight: 600;
}
.toolbar {
  margin-bottom: 16px;
  display: flex;
  gap: 12px;
}
</style>
