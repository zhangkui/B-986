<template>
  <div class="home-page">
    <!-- 平台标题 -->
    <div class="header">
      <h1 class="title">{{ platformTitle }}</h1>
    </div>

    <!-- 广告图片 -->
    <div class="banner">
      <van-loading v-if="loading" size="32px" class="banner-loading" />
      <SafeBannerImage
        v-else
        :src="bannerUrl"
        fallback-key="home"
        alt="首页广告"
        class="banner-img"
      />
    </div>

    <!-- 知识信息列表 -->
    <div class="knowledge-section">
      <h3 class="section-title">知识资讯</h3>
      <van-loading v-if="loading" size="24px" class="list-loading" />
      <div v-else class="knowledge-list">
        <div v-for="item in knowledgeList" :key="item.id" class="knowledge-item">
          <h4>{{ item.title }}</h4>
          <p>{{ item.summary }}</p>
        </div>
        <van-empty v-if="!knowledgeList.length" description="暂无知识信息" />
      </div>
    </div>

    <!-- 须知弹窗 (首次打开) -->
    <NoticeModal
      v-model:show="showNotice"
      :content="noticeContent"
      :reject-text="noticeRejectText"
      :agree-text="noticeAgreeText"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { showToast } from 'vant'
import NoticeModal from '@/components/NoticeModal.vue'
import SafeBannerImage from '@/components/SafeBannerImage.vue'

const platformTitle = ref('药品医疗器械化妆品不良反应/事件填报平台')
const bannerUrl = ref('')
const knowledgeList = ref([])
const showNotice = ref(false)
const noticeContent = ref('')
const noticeRejectText = ref('拒绝')
const noticeAgreeText = ref('同意')
const loading = ref(false)

const API_BASE = '/api'

async function loadConfig() {
  try {
    const res = await fetch(`${API_BASE}/config`)
    const data = await res.json()
    if (data.success && data.data) {
      if (data.data.platform_title) {
        platformTitle.value = data.data.platform_title.config_value
      }
      if (data.data.home_banner) {
        bannerUrl.value = data.data.home_banner.config_value
      }
      if (data.data.notice_content) {
        noticeContent.value = data.data.notice_content.config_value
      }
      noticeRejectText.value = data.data.btn_reject?.config_value || '拒绝'
      noticeAgreeText.value = data.data.btn_agree?.config_value || '同意'
    }
  } catch (e) {
    console.error('加载配置失败:', e)
  }
}

async function loadKnowledge() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/knowledge?page_level=1`)
    const data = await res.json()
    if (data.success) {
      knowledgeList.value = data.data || []
    }
  } catch (e) {
    console.error('加载知识失败:', e)
    showToast('加载知识失败')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadConfig(), loadKnowledge()])

  // 首次打开弹出须知
  const agreed = localStorage.getItem('notice_agreed')
  if (!agreed) {
    showNotice.value = true
  }
})
</script>

<style scoped>
.home-page { padding: 0 0 20px; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; text-align: center; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.banner { padding: 12px; position: relative; min-height: 120px; }
.banner-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
.banner-img { width: 100%; border-radius: 8px; min-height: 120px; background: #e8e8e8; }
.banner-placeholder { width: 100%; height: 120px; border-radius: 8px; background: linear-gradient(135deg, #e8f4ff, #e8fff4); display: flex; align-items: center; justify-content: center; color: #999; font-size: 14px; }
.knowledge-section { padding: 0 16px; }
.section-title { font-size: 16px; margin-bottom: 12px; color: #333; }
.list-loading { text-align: center; padding: 20px 0; }
.knowledge-item { background: #fff; border-radius: 8px; padding: 14px; margin-bottom: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.knowledge-item h4 { font-size: 15px; color: #333; margin-bottom: 6px; }
.knowledge-item p { font-size: 13px; color: #999; line-height: 1.5; }
</style>
