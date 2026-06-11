<template>
  <div class="report-manage-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>举报工单管理</span>
        </div>
      </template>

      <el-form :inline="true" :model="filterForm" class="filter-form">
        <el-form-item label="状态">
          <el-select v-model="filterForm.status" placeholder="全部状态" clearable style="width: 140px">
            <el-option label="待受理" value="pending" />
            <el-option label="处理中" value="processing" />
            <el-option label="待补充" value="supplement" />
            <el-option label="已办结" value="completed" />
            <el-option label="已驳回" value="rejected" />
          </el-select>
        </el-form-item>
        <el-form-item label="地区">
          <el-select v-model="filterForm.region_id" placeholder="全部地区" clearable style="width: 140px">
            <el-option v-for="r in regions" :key="r.id" :label="r.name" :value="r.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="filterForm.category_id" placeholder="全部分类" clearable style="width: 140px">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="处理人">
          <el-select v-model="filterForm.handler_id" placeholder="全部处理人" clearable style="width: 140px">
            <el-option v-for="a in admins" :key="a.id" :label="a.real_name || a.username" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间">
          <el-date-picker
            v-model="filterForm.date_range"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadReports">
            <el-icon><Search /></el-icon> 查询
          </el-button>
          <el-button @click="resetFilter">重置</el-button>
          <el-button type="success" @click="exportReports">
            <el-icon><Download /></el-icon> 导出 CSV
          </el-button>
          <el-button type="warning" @click="batchExport" :disabled="selectedIds.length === 0">
            <el-icon><Document /></el-icon> 批量导出 ({{ selectedIds.length }})
          </el-button>
        </el-form-item>
      </el-form>

      <el-table
        :data="reportList"
        border
        v-loading="loading"
        @selection-change="onSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="query_code" label="查询码" width="150">
          <template #default="{ row }">
            <el-tag type="info" effect="plain">{{ row.query_code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="category_name" label="分类" width="90" />
        <el-table-column prop="region_name" label="地区" width="90" />
        <el-table-column prop="form_data" label="填报内容" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            {{ formatFormData(row.form_data) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="handler_name" label="处理人" width="100">
          <template #default="{ row }">
            {{ row.handler_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="submitted_at" label="提交时间" width="160" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openDetail(row)">详情</el-button>
            <el-button
              v-if="row.status === 'pending'"
              type="success"
              link
              size="small"
              @click="handleAccept(row)"
            >受理</el-button>
            <el-button
              v-if="row.status === 'pending' || row.status === 'processing'"
              type="warning"
              link
              size="small"
              @click="openAssign(row)"
            >转派</el-button>
            <el-button
              v-if="row.status === 'processing'"
              type="danger"
              link
              size="small"
              @click="openSupplementRequest(row)"
            >要求补充</el-button>
            <el-button
              v-if="row.status === 'processing' || row.status === 'supplement'"
              type="success"
              link
              size="small"
              @click="openComplete(row)"
            >办结</el-button>
            <el-button
              v-if="row.status === 'pending' || row.status === 'processing' || row.status === 'supplement'"
              type="info"
              link
              size="small"
              @click="openReject(row)"
            >驳回</el-button>
          </template>
        </el-table-column>
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

    <!-- 工单详情弹窗 -->
    <el-dialog v-model="detailVisible" title="工单详情" width="800px" top="5vh">
      <div v-if="currentReport">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="ID">{{ currentReport.id }}</el-descriptions-item>
          <el-descriptions-item label="查询码">
            <el-tag type="info" effect="plain">{{ currentReport.query_code }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="分类">{{ currentReport.category_name }}</el-descriptions-item>
          <el-descriptions-item label="地区">{{ currentReport.region_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentReport.status)">{{ currentReport.status_text }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="处理人">{{ currentReport.handler_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ currentReport.submitted_at }}</el-descriptions-item>
          <el-descriptions-item label="受理时间">{{ currentReport.accepted_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-divider content-position="left">填报内容</el-divider>
        <el-descriptions :column="1" border size="small">
          <el-descriptions-item v-for="(value, key) in parseFormData(currentReport.form_data)" :key="key" :label="key">
            <span v-if="!isImageField(value)">{{ formatValue(value) }}</span>
            <div v-if="isImageField(value)" class="detail-images">
              <el-image
                v-for="(img, idx) in value"
                :key="idx"
                :src="img"
                :preview-src-list="value"
                :initial-index="idx"
                fit="cover"
                style="width: 80px; height: 80px; margin-right: 8px; border-radius: 4px;"
              />
            </div>
          </el-descriptions-item>
        </el-descriptions>

        <el-divider v-if="currentReport.supplement_request" content-position="left">补充要求</el-divider>
        <div v-if="currentReport.supplement_request" class="supplement-box">
          {{ currentReport.supplement_request }}
        </div>

        <el-divider v-if="currentReport.supplement_data" content-position="left">补充材料</el-divider>
        <el-descriptions v-if="currentReport.supplement_data" :column="1" border size="small">
          <el-descriptions-item v-for="(value, key) in parseFormData(currentReport.supplement_data)" :key="'sup-'+key" :label="key">
            {{ formatValue(value) }}
          </el-descriptions-item>
        </el-descriptions>

        <el-divider v-if="currentReport.handle_opinion" content-position="left">处理意见</el-divider>
        <div v-if="currentReport.handle_opinion" class="content-box">
          {{ currentReport.handle_opinion }}
        </div>

        <el-divider v-if="currentReport.handle_result" content-position="left">办结结果</el-divider>
        <div v-if="currentReport.handle_result" class="content-box success">
          {{ currentReport.handle_result }}
        </div>

        <el-divider v-if="currentReport.handle_attachments?.length" content-position="left">处理附件</el-divider>
        <div v-if="currentReport.handle_attachments?.length" class="attach-list">
          <a v-for="(att, idx) in currentReport.handle_attachments" :key="idx" :href="att.url" target="_blank" class="attach-item">
            <el-icon><Paperclip /></el-icon>
            {{ att.name || '附件' + (idx + 1) }}
          </a>
        </div>

        <el-divider v-if="currentReport.admin_remark" content-position="left">后台备注</el-divider>
        <div v-if="currentReport.admin_remark" class="content-box remark">
          <pre style="margin:0; white-space: pre-wrap; font-family: inherit;">{{ currentReport.admin_remark }}</pre>
        </div>

        <el-divider content-position="left">流转日志</el-divider>
        <el-timeline>
          <el-timeline-item
            v-for="(log, idx) in currentReport.logs"
            :key="idx"
            :timestamp="log.created_at"
            :type="getLogType(log.action)"
          >
            <div class="log-title">
              <strong>{{ getLogTitle(log) }}</strong>
              <span v-if="log.operator_name" class="log-op"> - {{ log.operator_name || log.admin_real_name }}</span>
            </div>
            <div v-if="log.remark" class="log-remark">{{ log.remark }}</div>
          </el-timeline-item>
        </el-timeline>
      </div>

      <template #footer>
        <template v-if="currentReport">
          <el-button v-if="currentReport.status === 'pending'" type="success" @click="handleAccept(currentReport)">受理</el-button>
          <el-button v-if="currentReport.status === 'pending' || currentReport.status === 'processing'" type="warning" @click="openAssign(currentReport)">转派</el-button>
          <el-button v-if="currentReport.status === 'processing'" type="danger" @click="openSupplementRequest(currentReport)">要求补充</el-button>
          <el-button v-if="currentReport.status === 'processing' || currentReport.status === 'supplement'" type="success" @click="openComplete(currentReport)">办结</el-button>
          <el-button v-if="currentReport.status === 'pending' || currentReport.status === 'processing' || currentReport.status === 'supplement'" type="info" @click="openReject(currentReport)">驳回</el-button>
          <el-button type="primary" plain @click="openRemark(currentReport)">添加备注</el-button>
        </template>
      </template>
    </el-dialog>

    <!-- 受理弹窗 -->
    <el-dialog v-model="acceptVisible" title="受理工单" width="480px">
      <el-form :model="acceptForm" label-width="80px">
        <el-form-item label="受理备注">
          <el-input v-model="acceptForm.remark" type="textarea" :rows="3" placeholder="选填" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="acceptVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAccept" :loading="actionLoading">确认受理</el-button>
      </template>
    </el-dialog>

    <!-- 转派弹窗 -->
    <el-dialog v-model="assignVisible" title="转派工单" width="480px">
      <el-form :model="assignForm" label-width="80px">
        <el-form-item label="转派给" required>
          <el-select v-model="assignForm.handler_id" placeholder="请选择处理人" style="width: 100%">
            <el-option v-for="a in admins" :key="a.id" :label="a.real_name || a.username" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="转派备注">
          <el-input v-model="assignForm.remark" type="textarea" :rows="3" placeholder="选填" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="assignVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAssign" :loading="actionLoading">确认转派</el-button>
      </template>
    </el-dialog>

    <!-- 要求补充弹窗 -->
    <el-dialog v-model="supplementRequestVisible" title="要求补充材料" width="520px">
      <el-form :model="supplementRequestForm" label-width="80px">
        <el-form-item label="补充说明" required>
          <el-input v-model="supplementRequestForm.supplement_request" type="textarea" :rows="4" placeholder="请详细说明需要用户补充的材料" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="supplementRequestVisible = false">取消</el-button>
        <el-button type="primary" @click="submitSupplementRequest" :loading="actionLoading">确认通知</el-button>
      </template>
    </el-dialog>

    <!-- 办结弹窗 -->
    <el-dialog v-model="completeVisible" title="办结工单" width="560px">
      <el-form :model="completeForm" label-width="80px">
        <el-form-item label="处理意见">
          <el-input v-model="completeForm.handle_opinion" type="textarea" :rows="3" placeholder="选填" />
        </el-form-item>
        <el-form-item label="办结结果" required>
          <el-input v-model="completeForm.handle_result" type="textarea" :rows="4" placeholder="请填写办结结果" />
        </el-form-item>
        <el-form-item label="处理附件">
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleAttachChange"
            multiple
          >
            <el-button type="primary" link>选择文件</el-button>
          </el-upload>
          <div v-if="completeForm.handle_attachments?.length" class="upload-list">
            <div v-for="(att, idx) in completeForm.handle_attachments" :key="idx" class="upload-item">
              <el-icon><Paperclip /></el-icon>
              <span>{{ att.name }}</span>
              <el-icon class="remove" @click="removeAttach(idx)"><Close /></el-icon>
            </div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="completeVisible = false">取消</el-button>
        <el-button type="success" @click="submitComplete" :loading="actionLoading">确认办结</el-button>
      </template>
    </el-dialog>

    <!-- 驳回弹窗 -->
    <el-dialog v-model="rejectVisible" title="驳回工单" width="480px">
      <el-form :model="rejectForm" label-width="80px">
        <el-form-item label="驳回理由" required>
          <el-input v-model="rejectForm.handle_opinion" type="textarea" :rows="4" placeholder="请填写驳回理由" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" @click="submitReject" :loading="actionLoading">确认驳回</el-button>
      </template>
    </el-dialog>

    <!-- 备注弹窗 -->
    <el-dialog v-model="remarkVisible" title="添加备注" width="480px">
      <el-form :model="remarkForm" label-width="80px">
        <el-form-item label="备注内容" required>
          <el-input v-model="remarkForm.remark" type="textarea" :rows="4" placeholder="请输入备注内容" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="remarkVisible = false">取消</el-button>
        <el-button type="primary" @click="submitRemark" :loading="actionLoading">确认提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Download, Document, Paperclip, Close } from '@element-plus/icons-vue'

const API_BASE = '/api'
const loading = ref(false)
const actionLoading = ref(false)
const reportList = ref([])
const regions = ref([])
const categories = ref([])
const admins = ref([])
const selectedIds = ref([])

const filterForm = reactive({
  status: null,
  region_id: null,
  category_id: null,
  handler_id: null,
  date_range: null
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const statusTypeMap = {
  pending: 'warning',
  processing: 'primary',
  supplement: 'danger',
  completed: 'success',
  rejected: 'info'
}

function getStatusType(status) {
  return statusTypeMap[status] || 'info'
}

function getLogType(action) {
  const map = {
    submit: 'primary',
    accept: 'success',
    assign: 'warning',
    supplement_request: 'danger',
    supplement_submit: 'warning',
    complete: 'success',
    reject: 'info',
    remark: '',
    attach: ''
  }
  return map[action] || ''
}

const actionTitleMap = {
  submit: '用户提交举报',
  accept: '工单已受理',
  assign: '工单转派',
  supplement_request: '要求补充材料',
  supplement_submit: '用户提交补充材料',
  complete: '工单已办结',
  reject: '工单已驳回',
  remark: '添加备注',
  attach: '上传附件'
}

function getLogTitle(log) {
  return actionTitleMap[log.action] || log.action
}

function isArray(v) { return Array.isArray(v) }

function formatValue(v) {
  if (v === null || v === undefined) return '-'
  if (Array.isArray(v)) return v.join(', ')
  if (typeof v === 'object') return JSON.stringify(v)
  return String(v)
}

function parseFormData(data) {
  if (!data) return {}
  if (typeof data === 'string') {
    try { return JSON.parse(data) } catch { return { '内容': data } }
  }
  if (typeof data === 'object') return data
  return { '内容': String(data) }
}

function isImageField(v) {
  if (!Array.isArray(v)) return false
  return v.length > 0 && typeof v[0] === 'string' && (v[0].startsWith('/uploads') || v[0].startsWith('http'))
}

function getToken() {
  return localStorage.getItem('admin_token')
}

function formatFormData(data) {
  if (!data) return ''
  if (typeof data === 'string') {
    try { data = JSON.parse(data) } catch { return data }
  }
  return Object.entries(data).map(([k, v]) => {
    if (Array.isArray(v)) return `${k}: [${v.length}张图片]`
    return `${k}: ${v}`
  }).join('; ')
}

async function loadRegions() {
  try {
    const res = await fetch(`${API_BASE}/regions`)
    const data = await res.json()
    if (data.success) {
      regions.value = data.data.flatMap(p => p.children?.length ? p.children : [p]) || []
    }
  } catch (e) { console.error('加载地区失败:', e) }
}

async function loadCategories() {
  try {
    const res = await fetch(`${API_BASE}/categories`)
    const data = await res.json()
    if (data.success) categories.value = data.data || []
  } catch (e) { console.error('加载分类失败:', e) }
}

async function loadAdmins() {
  try {
    const res = await fetch(`${API_BASE}/admin/admins`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) admins.value = data.data || []
  } catch (e) { console.error('加载管理员失败:', e) }
}

async function loadReports() {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.page,
      page_size: pagination.pageSize
    })
    if (filterForm.status) params.append('status', filterForm.status)
    if (filterForm.region_id) params.append('region_id', filterForm.region_id)
    if (filterForm.category_id) params.append('category_id', filterForm.category_id)
    if (filterForm.handler_id) params.append('handler_id', filterForm.handler_id)
    if (filterForm.date_range?.[0]) params.append('start_date', filterForm.date_range[0])
    if (filterForm.date_range?.[1]) params.append('end_date', filterForm.date_range[1])

    const res = await fetch(`${API_BASE}/admin/reports?${params}`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      reportList.value = data.data || []
      pagination.total = data.pagination?.total || 0
    }
  } catch (e) {
    console.error('加载工单失败:', e)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

function resetFilter() {
  filterForm.status = null
  filterForm.region_id = null
  filterForm.category_id = null
  filterForm.handler_id = null
  filterForm.date_range = null
  pagination.page = 1
  loadReports()
}

function onSelectionChange(rows) {
  selectedIds.value = rows.map(r => r.id)
}

async function exportReports() {
  try {
    const params = new URLSearchParams({ action: 'export' })
    if (filterForm.status) params.append('status', filterForm.status)
    if (filterForm.region_id) params.append('region_id', filterForm.region_id)
    if (filterForm.category_id) params.append('category_id', filterForm.category_id)
    if (filterForm.handler_id) params.append('handler_id', filterForm.handler_id)
    if (filterForm.date_range?.[0]) params.append('start_date', filterForm.date_range[0])
    if (filterForm.date_range?.[1]) params.append('end_date', filterForm.date_range[1])

    const response = await fetch(`${API_BASE}/admin/reports?${params}`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    if (!response.ok) throw new Error('导出失败')
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `reports_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
    ElMessage.success('导出成功')
  } catch (e) {
    console.error('导出失败:', e)
    ElMessage.error('导出失败')
  }
}

async function batchExport() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择要导出的工单')
    return
  }
  try {
    const response = await fetch(`${API_BASE}/admin/reports/batch-export`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ ids: selectedIds.value })
    })
    if (!response.ok) throw new Error('导出失败')
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `reports_batch_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
    ElMessage.success('导出成功')
  } catch (e) {
    console.error('批量导出失败:', e)
    ElMessage.error('批量导出失败')
  }
}

// ---------- 详情 ----------
const detailVisible = ref(false)
const currentReport = ref(null)

async function openDetail(row) {
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${row.id}`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      currentReport.value = data.data
      detailVisible.value = true
    } else {
      ElMessage.error(data.message || '加载详情失败')
    }
  } catch (e) {
    console.error('加载详情失败:', e)
    ElMessage.error('加载详情失败')
  }
}

// ---------- 受理 ----------
const acceptVisible = ref(false)
const acceptForm = reactive({ remark: '' })

function handleAccept(row) {
  acceptForm.remark = ''
  currentReport.value = row
  acceptVisible.value = true
}

async function submitAccept() {
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/accept`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ remark: acceptForm.remark })
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('受理成功')
      acceptVisible.value = false
      detailVisible.value = false
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

// ---------- 转派 ----------
const assignVisible = ref(false)
const assignForm = reactive({ handler_id: null, remark: '' })

function openAssign(row) {
  assignForm.handler_id = null
  assignForm.remark = ''
  currentReport.value = row
  assignVisible.value = true
}

async function submitAssign() {
  if (!assignForm.handler_id) {
    ElMessage.warning('请选择处理人')
    return
  }
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/assign`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(assignForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('转派成功')
      assignVisible.value = false
      detailVisible.value = false
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

// ---------- 要求补充 ----------
const supplementRequestVisible = ref(false)
const supplementRequestForm = reactive({ supplement_request: '' })

function openSupplementRequest(row) {
  supplementRequestForm.supplement_request = ''
  currentReport.value = row
  supplementRequestVisible.value = true
}

async function submitSupplementRequest() {
  if (!supplementRequestForm.supplement_request.trim()) {
    ElMessage.warning('请填写补充说明')
    return
  }
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/supplement-request`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(supplementRequestForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('已通知用户补充材料')
      supplementRequestVisible.value = false
      detailVisible.value = false
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

// ---------- 办结 ----------
const completeVisible = ref(false)
const completeForm = reactive({ handle_opinion: '', handle_result: '', handle_attachments: [] })
const pendingAttachFiles = ref([])

function openComplete(row) {
  completeForm.handle_opinion = ''
  completeForm.handle_result = ''
  completeForm.handle_attachments = []
  pendingAttachFiles.value = []
  currentReport.value = row
  completeVisible.value = true
}

async function handleAttachChange(file) {
  actionLoading.value = true
  try {
    const formData = new FormData()
    formData.append('file', file.raw)
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/attach`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${getToken()}` },
      body: formData
    })
    const data = await res.json()
    if (data.success) {
      completeForm.handle_attachments.push(data.data)
      ElMessage.success('附件上传成功')
    } else {
      ElMessage.error(data.message || '上传失败')
    }
  } catch (e) {
    ElMessage.error('上传失败')
  } finally {
    actionLoading.value = false
  }
}

function removeAttach(idx) {
  completeForm.handle_attachments.splice(idx, 1)
}

async function submitComplete() {
  if (!completeForm.handle_result.trim()) {
    ElMessage.warning('请填写办结结果')
    return
  }
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/complete`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        handle_opinion: completeForm.handle_opinion,
        handle_result: completeForm.handle_result,
        handle_attachments: completeForm.handle_attachments
      })
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('办结成功')
      completeVisible.value = false
      detailVisible.value = false
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

// ---------- 驳回 ----------
const rejectVisible = ref(false)
const rejectForm = reactive({ handle_opinion: '' })

function openReject(row) {
  rejectForm.handle_opinion = ''
  currentReport.value = row
  rejectVisible.value = true
}

async function submitReject() {
  if (!rejectForm.handle_opinion.trim()) {
    ElMessage.warning('请填写驳回理由')
    return
  }
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/reject`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(rejectForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('已驳回')
      rejectVisible.value = false
      detailVisible.value = false
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

// ---------- 备注 ----------
const remarkVisible = ref(false)
const remarkForm = reactive({ remark: '' })

function openRemark(row) {
  remarkForm.remark = ''
  currentReport.value = row
  remarkVisible.value = true
}

async function submitRemark() {
  if (!remarkForm.remark.trim()) {
    ElMessage.warning('请输入备注内容')
    return
  }
  actionLoading.value = true
  try {
    const res = await fetch(`${API_BASE}/admin/reports/${currentReport.value.id}/remark`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(remarkForm)
    })
    const data = await res.json()
    if (data.success) {
      ElMessage.success('备注已添加')
      remarkVisible.value = false
      openDetail(currentReport.value)
      loadReports()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (e) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadRegions(), loadCategories(), loadAdmins()])
  await loadReports()
})
</script>

<style scoped>
.report-manage-page { padding: 20px; }
.card-header { font-size: 18px; font-weight: 600; }
.filter-form { margin-bottom: 16px; }

.detail-images { display: flex; flex-wrap: wrap; }

.supplement-box {
  background: #fff7e6;
  border: 1px solid #ffd591;
  padding: 12px 16px;
  border-radius: 6px;
  color: #d46b08;
  line-height: 1.6;
}
.content-box {
  background: #f5f7fa;
  padding: 12px 16px;
  border-radius: 6px;
  line-height: 1.7;
  white-space: pre-wrap;
}
.content-box.success { background: #f0f9eb; color: #67c23a; }
.content-box.remark { background: #ecf5ff; color: #409eff; }

.attach-list { display: flex; flex-direction: column; gap: 6px; }
.attach-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 4px;
  color: #409eff;
  text-decoration: none;
  font-size: 13px;
}

.log-title { font-size: 14px; }
.log-op { color: #909399; font-weight: normal; font-size: 12px; }
.log-remark {
  font-size: 13px;
  color: #606266;
  margin-top: 4px;
  background: #f5f7fa;
  padding: 6px 10px;
  border-radius: 4px;
}

.upload-list { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; }
.upload-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: #f5f7fa;
  border-radius: 4px;
  font-size: 13px;
}
.upload-item .remove { margin-left: 8px; cursor: pointer; color: #f56c6c; }
</style>
