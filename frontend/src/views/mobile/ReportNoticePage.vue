<template>
  <div class="report-notice-page">
    <!-- 平台标题 -->
    <div class="header">
      <h1 class="title">{{ platformTitle }}</h1>
    </div>

    <!-- 广告图片 -->
    <div class="banner">
      <van-loading v-if="loading" size="32px" class="banner-loading" />
      <template v-else>
        <SafeBannerImage
          :src="bannerUrl"
          :fallback-key="getNoticeFallbackKey(categoryId)"
          alt="广告图片"
          class="banner-img"
        />
      </template>
    </div>

    <!-- 请您注意信息 -->
    <div class="notice-section">
      <van-loading v-if="loading" size="24px" class="notice-loading" />
      <template v-else>
        <h2 class="notice-title">{{ notice.title || '请您注意' }}</h2>
        <div class="notice-content" v-html="formattedContent"></div>
      </template>
    </div>

    <!-- 操作按钮 -->
    <div class="action-area">
      <van-button plain type="default" block round @click="goBack">{{ backLabel }}</van-button>
      <van-button type="primary" block round @click="goForm" style="margin-top: 12px">{{ fillReportLabel }}</van-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import SafeBannerImage from '@/components/SafeBannerImage.vue'

const route = useRoute()
const router = useRouter()
const categoryId = computed(() => parseInt(route.params.categoryId) || 1)
const platformTitle = ref('药品医疗器械化妆品不良反应/事件填报平台')
const bannerUrl = ref('')
const notice = ref({ title: '请您注意', content: '' })
const backLabel = ref('返回')
const fillReportLabel = ref('填写报告')
const loading = ref(false)

const API_BASE = '/api'

const formattedContent = computed(() => {
  return (notice.value.content || '').replace(/\n/g, '<br/>')
})

async function loadConfig() {
  try {
    const res = await fetch(`${API_BASE}/config`)
    const data = await res.json()
    if (data.success && data.data) {
      if (data.data.platform_title) {
        platformTitle.value = data.data.platform_title.config_value
      }
      const bannerMap = {
        1: 'level3_banner_cat1',
        2: 'level3_banner_cat2',
        3: 'level3_banner_cat3'
      }
      if (data.data[bannerMap[categoryId.value]]) {
        bannerUrl.value = data.data[bannerMap[categoryId.value]].config_value
      }
      backLabel.value = data.data.btn_back?.config_value || '返回'
      fillReportLabel.value = data.data.btn_fill_report?.config_value || '填写报告'
    }
  } catch (e) {
    console.error('加载配置失败:', e)
  }
}

async function loadNotice() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/notices/${categoryId.value}`)
    const data = await res.json()
    if (data.success && data.data) {
      notice.value = data.data
    }
  } catch (e) {
    console.error('加载注意事项失败:', e)
    showToast('加载注意事项失败')
  } finally {
    loading.value = false
  }
}


function getNoticeFallbackKey(id) {
  const map = {
    1: 'level3_cat1',
    2: 'level3_cat2',
    3: 'level3_cat3'
  }
  return map[id] || 'level2'
}
function goBack() {
  router.back()
}

function goForm() {
  router.push(`/report/${categoryId.value}/form`)
}

onMounted(async () => {
  await Promise.all([loadConfig(), loadNotice()])
})
</script>

<style scoped>
.report-notice-page { min-height: 100vh; background: #f5f7fa; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; text-align: center; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.banner { padding: 12px; position: relative; min-height: 100px; }
.banner-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
.banner-img { width: 100%; border-radius: 8px; min-height: 100px; background: #e8e8e8; }
.banner-placeholder { width: 100%; height: 100px; border-radius: 8px; background: linear-gradient(135deg, #e8f4ff, #e8fff4); display: flex; align-items: center; justify-content: center; color: #999; font-size: 14px; }
.notice-section { margin: 12px 16px; background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.notice-loading { text-align: center; padding: 20px 0; }
.notice-title { font-size: 18px; color: #333; margin-bottom: 16px; text-align: center; }
.notice-content { font-size: 14px; color: #666; line-height: 1.8; }
.action-area { padding: 20px 16px; }
</style>
