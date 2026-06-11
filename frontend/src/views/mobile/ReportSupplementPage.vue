<template>
  <div class="supplement-page">
    <div class="header">
      <h1 class="title">补充材料</h1>
    </div>

    <van-loading v-if="loading" size="32px" class="page-loading" />

    <div v-else class="content">
      <div class="notice-card" v-if="report">
        <div class="notice-title">
          <van-icon name="info-o" size="16" color="#fa541c" />
          补充要求
        </div>
        <div class="notice-content">{{ report.supplement_request || '请根据要求补充相关材料' }}</div>
      </div>

      <div class="section-card">
        <div class="section-title">举报信息</div>
        <div class="info-item">
          <span class="label">查询码</span>
          <span class="value code">{{ queryCode }}</span>
        </div>
        <div class="info-item" v-if="report?.category_name">
          <span class="label">分类</span>
          <span class="value">{{ report.category_name }}</span>
        </div>
        <div class="info-item" v-if="report?.region_name">
          <span class="label">地区</span>
          <span class="value">{{ report.region_name }}</span>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title">补充材料</div>

        <div class="form-field">
          <van-field
            v-model="supplementForm.description"
            label="补充说明"
            type="textarea"
            rows="4"
            placeholder="请详细描述补充的内容"
            autosize
          />
        </div>

        <div class="form-field">
          <div class="field-label">补充图片</div>
          <van-uploader
            v-model="fileList"
            :max-count="9"
            :after-read="afterRead"
            multiple
          />
        </div>

        <div class="form-field">
          <van-field
            v-model="supplementForm.contact"
            label="联系方式"
            placeholder="方便工作人员联系您(选填)"
          />
        </div>

        <div class="form-field">
          <van-field
            v-model="supplementForm.remark"
            label="备注"
            type="textarea"
            rows="2"
            placeholder="其他需要说明的内容(选填)"
            autosize
          />
        </div>
      </div>

      <div class="action-area">
        <van-button plain type="default" block round @click="goBack">返回</van-button>
        <van-button type="primary" block round style="margin-top: 12px" @click="onSubmit" :loading="submitting">提交补充材料</van-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showSuccessToast, showFailToast } from 'vant'

const API_BASE = '/api'
const route = useRoute()
const router = useRouter()

const queryCode = computed(() => route.params.queryCode)

const loading = ref(true)
const submitting = ref(false)
const report = ref(null)
const fileList = ref([])
const uploadUrls = ref([])

const supplementForm = reactive({
  description: '',
  contact: '',
  remark: ''
})

async function loadReport() {
  if (!queryCode.value) return
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/reports/${queryCode.value}`)
    const data = await res.json()
    if (data.success) {
      report.value = data.data
      if (data.data.status !== 'supplement') {
        showToast('当前状态无需补充材料')
        setTimeout(() => router.back(), 1500)
      }
    } else {
      showToast(data.message || '加载失败')
    }
  } catch (e) {
    console.error('加载失败:', e)
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

async function afterRead(file) {
  const files = Array.isArray(file) ? file : [file]
  for (const f of files) {
    const formData = new FormData()
    formData.append('file', f.file)
    try {
      const res = await fetch(`${API_BASE}/upload`, {
        method: 'POST',
        body: formData
      })
      const data = await res.json()
      if (data.success) {
        uploadUrls.value.push(data.data.url)
      } else {
        showToast(data.message || '上传失败')
      }
    } catch (e) {
      showToast('上传失败')
    }
  }
}

function validateForm() {
  if (!supplementForm.description.trim() && !uploadUrls.value.length) {
    showToast('请至少填写补充说明或上传图片')
    return false
  }
  return true
}

async function onSubmit() {
  if (!validateForm()) return
  submitting.value = true
  try {
    const supplementData = {
      补充说明: supplementForm.description || '',
      补充图片: uploadUrls.value.length ? uploadUrls.value : [],
    }
    if (supplementForm.contact) {
      supplementData.联系方式 = supplementForm.contact
    }
    if (supplementForm.remark) {
      supplementData.备注 = supplementForm.remark
    }

    const res = await fetch(`${API_BASE}/reports/${queryCode.value}/supplement`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        supplement_data: supplementData,
        remark: supplementForm.remark
      })
    })
    const data = await res.json()
    if (data.success) {
      showSuccessToast('提交成功')
      setTimeout(() => {
        router.replace(`/my-reports/${queryCode.value}`)
      }, 1500)
    } else {
      showFailToast(data.message || '提交失败')
    }
  } catch (e) {
    console.error('提交失败:', e)
    showFailToast('提交失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(() => {
  loadReport()
})
</script>

<style scoped>
.supplement-page { min-height: 100vh; background: #f5f7fa; padding-bottom: 40px; }
.header { background: linear-gradient(135deg, #ff976a, #ee0a24); padding: 20px 16px; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.page-loading { text-align: center; padding: 60px 0; }
.content { padding: 12px; }

.notice-card {
  background: #fff7e6;
  border: 1px solid #ffd591;
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 12px;
}
.notice-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 600;
  color: #d46b08;
  margin-bottom: 8px;
}
.notice-content {
  font-size: 13px;
  color: #d46b08;
  line-height: 1.6;
}

.section-card {
  background: #fff;
  border-radius: 12px;
  padding: 12px 0;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  padding: 0 16px 10px;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 4px;
}

.info-item {
  display: flex;
  padding: 10px 16px;
  font-size: 13px;
}
.info-item .label { color: #999; min-width: 70px; flex-shrink: 0; }
.info-item .value { color: #333; flex: 1; }
.info-item .value.code { color: #1989fa; font-weight: 600; letter-spacing: 1px; }

.form-field { padding: 0 4px; }
.field-label {
  padding: 10px 16px 6px;
  font-size: 14px;
  color: #323233;
}

.action-area { padding: 20px 16px; }
</style>
