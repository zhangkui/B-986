<template>
  <div class="report-detail-page">
    <div class="header">
      <h1 class="title">举报详情</h1>
    </div>

    <van-loading v-if="loading" size="32px" class="page-loading" />

    <div v-else-if="report" class="content">
      <div class="status-card">
        <div class="status-header">
          <div class="status-badge" :class="report.status">
            <van-icon :name="statusIcon" size="20" />
            <span>{{ report.status_text }}</span>
          </div>
          <div class="query-code">
            <span class="label">查询码</span>
            <span class="code" @click="copyCode">{{ report.query_code }} <van-icon name="copy" size="14" /></span>
          </div>
        </div>
        <div class="status-meta">
          <span>提交时间：{{ report.submitted_at }}</span>
          <span v-if="report.accepted_at">受理时间：{{ report.accepted_at }}</span>
          <span v-if="report.handled_at">办结时间：{{ report.handled_at }}</span>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title">基本信息</div>
        <div class="info-item">
          <span class="label">分类</span>
          <span class="value">{{ report.category_name }}</span>
        </div>
        <div class="info-item" v-if="report.region_name">
          <span class="label">地区</span>
          <span class="value">{{ report.region_name }}</span>
        </div>
        <div class="info-item" v-if="report.handler_name">
          <span class="label">处理人</span>
          <span class="value">{{ report.handler_name }}</span>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title">填报内容</div>
        <div class="info-item" v-for="(value, key) in report.form_data" :key="key">
          <span class="label">{{ key }}</span>
          <span class="value" v-if="!isArray(value)">{{ value }}</span>
          <div class="image-list" v-else-if="isArray(value) && value.length">
            <van-image
              v-for="(img, idx) in value"
              :key="idx"
              :src="img"
              width="80"
              height="80"
              fit="cover"
              round
              @click="previewImage(value, idx)"
            />
          </div>
          <span class="value" v-else>-</span>
        </div>
      </div>

      <div class="section-card" v-if="report.supplement_request">
        <div class="section-title supplement">
          <van-icon name="info-o" /> 补充材料要求
        </div>
        <div class="supplement-content">{{ report.supplement_request }}</div>
      </div>

      <div class="section-card" v-if="report.supplement_data">
        <div class="section-title">补充材料</div>
        <div class="info-item" v-for="(value, key) in report.supplement_data" :key="'sup-' + key">
          <span class="label">{{ key }}</span>
          <span class="value" v-if="!isArray(value)">{{ value }}</span>
          <div class="image-list" v-else-if="isArray(value) && value.length">
            <van-image
              v-for="(img, idx) in value"
              :key="'sup-img-' + idx"
              :src="img"
              width="80"
              height="80"
              fit="cover"
              round
              @click="previewImage(value, idx)"
            />
          </div>
          <span class="value" v-else>-</span>
        </div>
      </div>

      <div class="section-card" v-if="report.handle_opinion">
        <div class="section-title">处理意见</div>
        <div class="content-text">{{ report.handle_opinion }}</div>
      </div>

      <div class="section-card" v-if="report.handle_result">
        <div class="section-title success">
          <van-icon name="passed" /> 办结结果
        </div>
        <div class="content-text">{{ report.handle_result }}</div>
      </div>

      <div class="section-card" v-if="report.handle_attachments && report.handle_attachments.length">
        <div class="section-title">处理附件</div>
        <div class="attach-list">
          <a v-for="(att, idx) in report.handle_attachments" :key="idx" :href="att.url" target="_blank" class="attach-item">
            <van-icon name="description" />
            <span>{{ att.name || '附件' + (idx + 1) }}</span>
          </a>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title">处理进度</div>
        <van-steps :active="currentStep" direction="vertical">
          <van-step v-for="(log, idx) in displayLogs" :key="idx">
            <div class="log-title">{{ getLogTitle(log) }}</div>
            <div class="log-time">{{ log.created_at }}</div>
            <div v-if="log.remark" class="log-remark">{{ log.remark }}</div>
          </van-step>
        </van-steps>
      </div>

      <div class="action-bar">
        <van-button v-if="report.status === 'supplement'" type="warning" block round @click="goSupplement">补充材料</van-button>
      </div>
    </div>

    <van-empty v-else-if="!loading" description="未找到举报记录" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showImagePreview } from 'vant'

const API_BASE = '/api'
const route = useRoute()
const router = useRouter()

const queryCode = computed(() => route.params.queryCode)
const loading = ref(true)
const report = ref(null)

const actionTitleMap = {
  submit: '用户提交举报',
  accept: '已受理',
  assign: '工单转派',
  supplement_request: '要求补充材料',
  supplement_submit: '用户提交补充材料',
  complete: '已办结',
  reject: '已驳回',
  remark: '添加备注',
  attach: '上传附件'
}

const statusIconMap = {
  pending: 'clock-o',
  processing: 'logistics',
  supplement: 'exclamation-circle',
  completed: 'checked',
  rejected: 'close-circle'
}

const statusIcon = computed(() => statusIconMap[report.value?.status] || 'info-o')

const displayLogs = computed(() => report.value?.logs || [])

const currentStep = computed(() => {
  if (!report.value?.logs?.length) return 0
  return report.value.logs.length - 1
})

function isArray(v) {
  return Array.isArray(v)
}

function getLogTitle(log) {
  let title = actionTitleMap[log.action] || log.action
  if (log.operator_name) {
    title += ` - ${log.operator_name}`
  }
  return title
}

async function loadDetail() {
  if (!queryCode.value) return
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/reports/${queryCode.value}`)
    const data = await res.json()
    if (data.success) {
      report.value = data.data
    } else {
      showToast(data.message || '加载失败')
    }
  } catch (e) {
    console.error('加载详情失败:', e)
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

function copyCode() {
  if (!report.value?.query_code) return
  const code = report.value.query_code
  if (navigator.clipboard) {
    navigator.clipboard.writeText(code).then(() => showToast('已复制'))
  } else {
    showToast('查询码: ' + code)
  }
}

function previewImage(images, idx) {
  showImagePreview({ images, startPosition: idx })
}

function goSupplement() {
  router.push(`/my-reports/${queryCode.value}/supplement`)
}

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
.report-detail-page { min-height: 100vh; background: #f5f7fa; padding-bottom: 80px; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.page-loading { text-align: center; padding: 60px 0; }
.content { padding: 12px; }

.status-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.status-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
}
.status-badge.pending { background: #fff7e6; color: #fa8c16; }
.status-badge.processing { background: #e6f7ff; color: #1890ff; }
.status-badge.supplement { background: #fff2e8; color: #fa541c; }
.status-badge.completed { background: #f6ffed; color: #52c41a; }
.status-badge.rejected { background: #f5f5f5; color: #8c8c8c; }

.query-code .label { color: #999; font-size: 12px; display: block; margin-bottom: 2px; text-align: right; }
.query-code .code { color: #1989fa; font-size: 14px; font-weight: 600; letter-spacing: 1px; }

.status-meta { font-size: 12px; color: #999; display: flex; flex-direction: column; gap: 4px; }

.section-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 6px;
}
.section-title.supplement { color: #fa541c; }
.section-title.success { color: #52c41a; }

.info-item {
  display: flex;
  padding: 6px 0;
  font-size: 13px;
}
.info-item .label {
  color: #999;
  min-width: 90px;
  flex-shrink: 0;
}
.info-item .value { color: #333; flex: 1; word-break: break-all; }

.image-list { display: flex; flex-wrap: wrap; gap: 8px; flex: 1; }

.supplement-content {
  background: #fff7e6;
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  color: #fa8c16;
  line-height: 1.6;
}

.content-text {
  font-size: 13px;
  color: #333;
  line-height: 1.7;
  white-space: pre-wrap;
}

.attach-list { display: flex; flex-direction: column; gap: 8px; }
.attach-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #f5f7fa;
  border-radius: 6px;
  font-size: 13px;
  color: #1989fa;
  text-decoration: none;
}

.log-title { font-size: 13px; font-weight: 600; color: #333; }
.log-time { font-size: 12px; color: #999; margin-top: 2px; }
.log-remark {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
  background: #f5f7fa;
  padding: 6px 8px;
  border-radius: 4px;
  line-height: 1.5;
}

.action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 12px 16px;
  background: #fff;
  box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
}
</style>
