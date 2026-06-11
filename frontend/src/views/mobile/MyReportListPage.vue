<template>
  <div class="my-report-page">
    <div class="header">
      <h1 class="title">我的举报</h1>
    </div>

    <div class="content">
      <div class="search-bar">
        <van-field
          v-model="searchCode"
          placeholder="输入查询码查询举报"
          clearable
          @click-right-icon="addByCode"
        >
          <template #right-icon>
            <van-icon name="plus" size="18" color="#1989fa" />
          </template>
        </van-field>
      </div>

      <div class="status-tabs">
        <van-tabs v-model:active="activeStatus" sticky swipeable line-width="24px">
          <van-tab title="全部" name="all" />
          <van-tab title="待受理" name="pending" />
          <van-tab title="处理中" name="processing" />
          <van-tab title="待补充" name="supplement" />
          <van-tab title="已办结" name="completed" />
          <van-tab title="已驳回" name="rejected" />
        </van-tabs>
      </div>

      <van-loading v-if="loading" size="32px" class="list-loading" />
      <div v-else class="report-list">
        <div v-for="item in filteredReports" :key="item.query_code" class="report-card" @click="goDetail(item.query_code)">
          <div class="card-header">
            <span class="category">{{ item.category_name }}</span>
            <van-tag :type="getStatusType(item.status)" size="medium">{{ item.status_text }}</van-tag>
          </div>
          <div class="card-body">
            <div class="code-row">
              <span class="label">查询码</span>
              <span class="code">{{ item.query_code }}</span>
            </div>
            <div class="info-row" v-if="item.region_name">
              <span class="label">地区</span>
              <span class="value">{{ item.region_name }}</span>
            </div>
            <div class="info-row">
              <span class="label">提交时间</span>
              <span class="value">{{ item.submitted_at }}</span>
            </div>
            <div class="info-row supplement" v-if="item.status === 'supplement' && supplementRequestMap[item.query_code]">
              <van-icon name="info-o" color="#ff976a" />
              <span class="supplement-text">{{ supplementRequestMap[item.query_code] }}</span>
            </div>
          </div>
          <div class="card-footer">
            <van-button size="small" type="primary" plain @click.stop="goDetail(item.query_code)">查看详情</van-button>
            <van-button v-if="item.status === 'supplement'" size="small" type="warning" @click.stop="goSupplement(item.query_code)">补充材料</van-button>
          </div>
        </div>
        <van-empty v-if="!filteredReports.length && !loading" description="暂无举报记录" />
      </div>
    </div>

    <van-dialog v-model:show="showAddDialog" title="添加查询码" show-cancel-button>
      <van-field
        v-model="newCode"
        placeholder="请输入查询码"
        style="padding: 0 16px"
      />
    </van-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'

const API_BASE = '/api'
const router = useRouter()

const loading = ref(false)
const searchCode = ref('')
const newCode = ref('')
const showAddDialog = ref(false)
const reports = ref([])
const activeStatus = ref('all')
const supplementRequestMap = ref({})

const statusTypeMap = {
  pending: 'warning',
  processing: 'primary',
  supplement: 'danger',
  completed: 'success',
  rejected: 'default'
}

function getStatusType(status) {
  return statusTypeMap[status] || 'default'
}

const filteredReports = computed(() => {
  if (activeStatus.value === 'all') return reports.value
  return reports.value.filter(r => r.status === activeStatus.value)
})

function getStoredCodes() {
  return JSON.parse(localStorage.getItem('my_report_codes') || '[]')
}

function saveStoredCodes(codes) {
  localStorage.setItem('my_report_codes', JSON.stringify(codes))
}

async function loadReports() {
  const codes = getStoredCodes()
  if (!codes.length) {
    reports.value = []
    return
  }
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/reports/mine?codes=${encodeURIComponent(codes.join(','))}`)
    const data = await res.json()
    if (data.success) {
      reports.value = data.data || []
      for (const r of reports.value) {
        if (r.status === 'supplement') {
          loadSupplementRequest(r.query_code)
        }
      }
    }
  } catch (e) {
    console.error('加载举报列表失败:', e)
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

async function loadSupplementRequest(code) {
  try {
    const res = await fetch(`${API_BASE}/reports/${code}`)
    const data = await res.json()
    if (data.success && data.data?.supplement_request) {
      supplementRequestMap.value[code] = data.data.supplement_request
    }
  } catch (e) {
    // ignore
  }
}

function addByCode() {
  if (searchCode.value.trim()) {
    doAddCode(searchCode.value.trim())
  } else {
    newCode.value = ''
    showAddDialog.value = true
  }
}

function doAddCode(code) {
  if (!code) {
    showToast('请输入查询码')
    return
  }
  code = code.trim().toUpperCase()
  const codes = getStoredCodes()
  if (codes.includes(code)) {
    showToast('该查询码已存在')
    return
  }
  codes.unshift(code)
  saveStoredCodes(codes)
  searchCode.value = ''
  showAddDialog.value = false
  loadReports()
  showToast('添加成功')
}

function goDetail(code) {
  router.push(`/my-reports/${code}`)
}

function goSupplement(code) {
  router.push(`/my-reports/${code}/supplement`)
}

onMounted(() => {
  loadReports()
})
</script>

<style scoped>
.my-report-page { min-height: 100vh; background: #f5f7fa; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.content { padding: 12px; }
.search-bar { background: #fff; border-radius: 10px; padding: 4px 12px; margin-bottom: 12px; }
.status-tabs { background: #fff; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
.list-loading { text-align: center; padding: 40px 0; }

.report-card {
  background: #fff;
  border-radius: 12px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  overflow: hidden;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
}
.category { font-size: 15px; font-weight: 600; color: #333; }
.card-body { padding: 12px 16px; }
.code-row, .info-row {
  display: flex;
  align-items: center;
  font-size: 13px;
  margin-bottom: 6px;
}
.code-row .label, .info-row .label {
  color: #999;
  min-width: 70px;
  flex-shrink: 0;
}
.code-row .code {
  color: #1989fa;
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 1px;
}
.info-row .value { color: #333; }
.info-row.supplement {
  background: #fff7e6;
  padding: 8px 10px;
  border-radius: 6px;
  margin-top: 8px;
}
.supplement-text {
  color: #fa8c16;
  font-size: 12px;
  margin-left: 4px;
  flex: 1;
}
.card-footer {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding: 10px 16px;
  border-top: 1px solid #f0f0f0;
}
</style>
