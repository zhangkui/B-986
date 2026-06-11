<template>
  <div class="report-manage-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>报表管理</span>
        </div>
      </template>
      
      <el-form :inline="true" :model="filterForm" class="filter-form">
        <el-form-item label="地区">
          <el-select v-model="filterForm.region_id" placeholder="全部地区" clearable>
            <el-option v-for="r in regions" :key="r.id" :label="r.name" :value="r.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="filterForm.category_id" placeholder="全部分类" clearable>
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadReports">查询</el-button>
          <el-button type="success" @click="exportReports">导出 CSV</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="reportList" border v-loading="loading">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="category_name" label="分类" width="100" />
        <el-table-column prop="region_name" label="地区" width="100" />
        <el-table-column prop="form_data" label="填报内容" show-overflow-tooltip>
          <template #default="{ row }">
            {{ formatFormData(row.form_data) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 'submitted' ? 'success' : 'info'">
              {{ row.status === 'submitted' ? '已提交' : row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="170" />
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        @size-change="loadReports"
        @current-change="loadReports"
        style="margin-top: 16px; justify-content: center;"
      />
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const API_BASE = '/api'
const loading = ref(false)
const reportList = ref([])
const regions = ref([])
const categories = ref([])

const filterForm = reactive({
  region_id: null,
  category_id: null
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

function getToken() {
  return localStorage.getItem('admin_token')
}

function formatFormData(data) {
  if (!data) return ''
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data)
    } catch {
      return data
    }
  }
  return Object.entries(data).map(([k, v]) => `${k}: ${v}`).join('; ')
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

async function loadCategories() {
  try {
    const res = await fetch(`${API_BASE}/categories`)
    const data = await res.json()
    if (data.success) {
      categories.value = data.data || []
    }
  } catch (e) {
    console.error('加载分类失败:', e)
  }
}

async function loadReports() {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.page,
      page_size: pagination.pageSize
    })
    if (filterForm.region_id) params.append('region_id', filterForm.region_id)
    if (filterForm.category_id) params.append('category_id', filterForm.category_id)
    
    const res = await fetch(`${API_BASE}/admin/reports?${params}`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      reportList.value = data.data || []
      pagination.total = data.pagination?.total || 0
    }
  } catch (e) {
    console.error('加载报表失败:', e)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

async function exportReports() {
  try {
    const params = new URLSearchParams()
    if (filterForm.region_id) params.append('region_id', filterForm.region_id)
    if (filterForm.category_id) params.append('category_id', filterForm.category_id)
    
    const url = `${API_BASE}/admin/reports?action=export&${params}`
    
    const response = await fetch(url, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    
    if (!response.ok) {
      throw new Error('导出失败')
    }
    
    const blob = await response.blob()
    const downloadUrl = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = downloadUrl
    a.download = `reports_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(downloadUrl)
    
    ElMessage.success('导出成功')
  } catch (e) {
    console.error('导出失败:', e)
    ElMessage.error('导出失败')
  }
}

onMounted(async () => {
  await Promise.all([loadRegions(), loadCategories()])
  await loadReports()
})
</script>

<style scoped>
.report-manage-page {
  padding: 20px;
}
.card-header {
  font-size: 18px;
  font-weight: 600;
}
.filter-form {
  margin-bottom: 16px;
}
</style>
