<template>
  <div class="report-form-page">
    <!-- 平台标题 -->
    <div class="header">
      <h1 class="title">{{ platformTitle }}</h1>
    </div>

    <!-- 提交成功弹窗 -->
    <van-dialog v-model:show="showSuccessDialog" :show-confirm-button="false" :show-cancel-button="false" title="提交成功">
      <div class="success-content">
        <van-icon name="checked" size="60" color="#07c160" />
        <p class="success-tip">您的举报已提交，请妥善保存以下查询码以跟踪处理进度</p>
        <div class="query-code-box">
          <div class="query-code-label">查询码</div>
          <div class="query-code-value">{{ lastQueryCode }}</div>
          <van-button size="small" plain type="primary" @click="copyQueryCode">复制查询码</van-button>
        </div>
        <div class="success-actions">
          <van-button plain type="default" block round @click="goHome">返回首页</van-button>
          <van-button type="primary" block round style="margin-top: 10px" @click="goToMyReports">查看我的举报</van-button>
        </div>
      </div>
    </van-dialog>

    <!-- 动态表单区 -->
    <div class="form-section">
      <van-loading v-if="loading" size="32px" class="form-loading" />
      <van-form v-else @submit="onSubmit" ref="formRef">
        <div v-for="field in formFields" :key="field.id" class="form-field">
          <!-- 文本输入 -->
          <van-field
            v-if="field.field_type === 'text'"
            v-model="formData[field.field_name]"
            :label="field.field_name"
            :placeholder="field.field_options?.placeholder || '请输入'"
            :required="!!field.is_required"
          />
          <!-- 多行文本 -->
          <van-field
            v-else-if="field.field_type === 'textarea'"
            v-model="formData[field.field_name]"
            :label="field.field_name"
            type="textarea"
            :rows="field.field_options?.rows || 3"
            :placeholder="field.field_options?.placeholder || '请输入'"
            :required="!!field.is_required"
          />
          <!-- 日期选择 -->
          <van-field
            v-else-if="field.field_type === 'date'"
            v-model="formData[field.field_name]"
            :label="field.field_name"
            placeholder="请选择日期"
            readonly
            is-link
            :required="!!field.is_required"
            @click="openDatePicker(field.field_name)"
          />
          <!-- 单选 -->
          <van-field v-else-if="field.field_type === 'radio'" :label="field.field_name" :required="!!field.is_required">
            <template #input>
              <van-radio-group v-model="formData[field.field_name]" direction="horizontal">
                <van-radio
                  v-for="opt in (field.field_options?.options || [])"
                  :key="opt"
                  :name="opt"
                >{{ opt }}</van-radio>
              </van-radio-group>
            </template>
          </van-field>
          <!-- 图片上传 -->
          <van-field v-else-if="field.field_type === 'image'" :label="field.field_name">
            <template #input>
              <van-uploader
                :max-count="field.field_options?.max_count || 3"
                :after-read="(file) => handleImageUpload(file, field.field_name)"
              >
                <van-button icon="plus" type="primary" size="small">上传图片</van-button>
              </van-uploader>
            </template>
          </van-field>
          <!-- 地区选择 -->
          <van-field
            v-else-if="field.field_type === 'region'"
            v-model="formData[field.field_name]"
            :label="field.field_name"
            placeholder="请选择地区"
            readonly
            is-link
            :required="!!field.is_required"
            @click="openRegionPicker(field.field_name)"
          />
        </div>

        <van-empty v-if="!formFields.length" description="暂无表单字段" />
      </van-form>
    </div>

    <!-- 日期选择器 -->
    <van-popup v-model:show="showDatePicker" position="bottom">
      <van-date-picker
        v-model="currentDateValues"
        :min-date="minDate"
        :max-date="maxDate"
        title="选择日期"
        @confirm="onDateConfirm"
        @cancel="showDatePicker = false"
      />
    </van-popup>

    <!-- 地区选择器 -->
    <van-popup v-model:show="showRegionPicker" position="bottom">
      <van-picker
        :columns="regionColumns"
        title="选择地区"
        @confirm="onRegionConfirm"
        @cancel="showRegionPicker = false"
      />
    </van-popup>

    <!-- 操作按钮 -->
    <div class="action-area">
      <van-button plain type="default" block round @click="goBack">{{ backLabel }}</van-button>
      <van-button type="primary" block round @click="onSubmit" style="margin-top: 12px" :loading="submitting">{{ submitLabel }}</van-button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showSuccessToast, showFailToast } from 'vant'

const route = useRoute()
const router = useRouter()
const categoryId = computed(() => parseInt(route.params.categoryId) || 1)
const platformTitle = ref('药品医疗器械化妆品不良反应/事件填报平台')
const formFields = ref([])
const formData = reactive({})
const submitting = ref(false)
const loading = ref(false)
const backLabel = ref('返回')
const submitLabel = ref('提交')

const showSuccessDialog = ref(false)
const lastQueryCode = ref('')

function saveQueryCode(code) {
  if (!code) return
  const codes = JSON.parse(localStorage.getItem('my_report_codes') || '[]')
  if (!codes.includes(code)) {
    codes.unshift(code)
    localStorage.setItem('my_report_codes', JSON.stringify(codes))
  }
}

function copyQueryCode() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(lastQueryCode.value).then(() => {
      showToast('已复制')
    }).catch(() => {
      showToast('复制失败，请手动复制')
    })
  } else {
    const textarea = document.createElement('textarea')
    textarea.value = lastQueryCode.value
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    showToast('已复制')
  }
}

function goHome() {
  showSuccessDialog.value = false
  router.push('/')
}

function goToMyReports() {
  showSuccessDialog.value = false
  router.push('/my-reports')
}

// Date picker
const showDatePicker = ref(false)
const currentDateField = ref('')
const currentDateValues = ref(['2026', '01', '01'])
const minDate = new Date(2020, 0, 1)
const maxDate = new Date()

// Region picker
const showRegionPicker = ref(false)
const currentRegionField = ref('')
const regionColumns = ref([])

// Image upload
const uploadUrls = ref({})

const API_BASE = '/api'

async function loadConfig() {
  try {
    const res = await fetch(`${API_BASE}/config`)
    const data = await res.json()
    if (data.success && data.data && data.data.platform_title) {
      platformTitle.value = data.data.platform_title.config_value
      backLabel.value = data.data.btn_back?.config_value || '返回'
      submitLabel.value = data.data.btn_submit?.config_value || '提交'
    }
  } catch (e) {
    console.error('加载配置失败:', e)
  }
}

async function loadFormFields() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/form-fields/${categoryId.value}`)
    const data = await res.json()
    if (data.success) {
      formFields.value = data.data || []
    }
  } catch (e) {
    console.error('加载表单字段失败:', e)
    showToast('加载表单失败')
  } finally {
    loading.value = false
  }
}

async function loadRegions() {
  try {
    const res = await fetch(`${API_BASE}/regions`)
    const data = await res.json()
    if (data.success && data.data.length) {
      const province = data.data[0]
      const cities = province?.children || []
      regionColumns.value = [
        [{ text: province?.name || '合肥市', value: province?.id || 1 }],
        cities.map(c => ({ text: c.name, value: c.id }))
      ]
    } else {
      regionColumns.value = [
        [{ text: '合肥市', value: 1 }],
        [{ text: '瑶海区', value: 2 }, { text: '庐阳区', value: 3 }, { text: '蜀山区', value: 4 }, { text: '包河区', value: 5 }]
      ]
    }
  } catch (e) {
    console.error('加载地区失败:', e)
    regionColumns.value = [
      [{ text: '合肥市', value: 1 }],
      [{ text: '瑶海区', value: 2 }, { text: '庐阳区', value: 3 }, { text: '蜀山区', value: 4 }, { text: '包河区', value: 5 }]
    ]
  }
}

function onDateConfirm({ selectedValues }) {
  const dateStr = selectedValues.join('-')
  formData[currentDateField.value] = dateStr
  showDatePicker.value = false
}

function openDatePicker(fieldName) {
  currentDateField.value = fieldName
  showDatePicker.value = true
}

function onRegionConfirm({ selectedOptions }) {
  const regionStr = selectedOptions.map(r => r.text).join('/')
  formData[currentRegionField.value] = regionStr
  formData[currentRegionField.value + '_id'] = selectedOptions[1]?.value
  showRegionPicker.value = false
}

function openRegionPicker(fieldName) {
  currentRegionField.value = fieldName
  showRegionPicker.value = true
}

async function handleImageUpload(event, fieldName) {
  const file = event.file
  const formDataObj = new FormData()
  formDataObj.append('file', file.file)
  
  try {
    const res = await fetch(`${API_BASE}/upload`, {
      method: 'POST',
      body: formDataObj
    })
    const data = await res.json()
    if (data.success) {
      if (!uploadUrls.value[fieldName]) {
        uploadUrls.value[fieldName] = []
      }
      uploadUrls.value[fieldName].push(data.data.url)
      showToast('上传成功')
    } else {
      showToast(data.message || '上传失败')
    }
  } catch (e) {
    console.error('上传失败:', e)
    showToast('上传失败')
  }
}

function validateForm() {
  for (const field of formFields.value) {
    if (field.is_required && !formData[field.field_name]) {
      showToast(`请填写${field.field_name}`)
      return false
    }
  }
  return true
}

async function onSubmit() {
  if (!validateForm()) return
  
  submitting.value = true
  try {
    // Prepare form data with image URLs
    const submitData = {
      category_id: categoryId.value,
      region_id: (() => {
        const regionKey = Object.keys(formData).find((k) => k.endsWith('_id'))
        return regionKey ? formData[regionKey] : null
      })(),
      form_data: {}
    }
    
    for (const field of formFields.value) {
      if (field.field_type === 'image') {
        submitData.form_data[field.field_name] = uploadUrls.value[field.field_name] || []
      } else {
        submitData.form_data[field.field_name] = formData[field.field_name]
      }
    }
    
    const res = await fetch(`${API_BASE}/reports`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(submitData)
    })
    const data = await res.json()
    
    if (data.success) {
      const queryCode = data.data?.query_code
      lastQueryCode.value = queryCode || ''
      if (queryCode) {
        saveQueryCode(queryCode)
      }
      showSuccessDialog.value = true
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

onMounted(async () => {
  await Promise.all([loadConfig(), loadFormFields(), loadRegions()])
})
</script>

<style scoped>
.report-form-page { min-height: 100vh; background: #f5f7fa; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; text-align: center; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.form-section { margin: 12px 16px; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.action-area { padding: 20px 16px; }

.success-content { text-align: center; padding: 10px 0; }
.success-tip { color: #666; font-size: 14px; margin: 16px 0 20px; line-height: 1.6; }
.query-code-box { background: #f5f7fa; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
.query-code-label { font-size: 12px; color: #999; margin-bottom: 6px; }
.query-code-value { font-size: 22px; font-weight: 700; color: #1989fa; letter-spacing: 2px; margin-bottom: 10px; }
.success-actions { padding: 0 10px; }
</style>
