<template>
  <div class="report-entry-page">
    <!-- 平台标题 -->
    <div class="header">
      <h1 class="title">{{ platformTitle }}</h1>
    </div>

    <!-- 广告图片 -->
    <div class="banner">
      <SafeBannerImage
        :src="bannerUrl"
        fallback-key="level2"
        alt="广告图片"
        class="banner-img"
      />
    </div>

    <!-- 标签菜单: 药品/医疗器械/化妆品 -->
    <van-tabs v-model:active="activeTab" sticky>
      <van-tab v-for="cat in categories" :key="cat.id" :title="cat.name">
        <!-- 标签下广告图 -->
        <div class="tab-banner">
          <van-loading v-if="loading" size="32px" />
          <SafeBannerImage
            v-else
            :src="getCategoryBanner(cat.id)"
            :fallback-key="getCategoryFallbackKey(cat.id)"
            alt="分类广告"
            class="banner-img"
          />
        </div>

        <!-- 知识信息列表 -->
        <div class="knowledge-list">
          <van-loading v-if="loading" size="24px" class="list-loading" />
          <template v-else>
            <div v-for="item in getCategoryKnowledge(cat.id)" :key="item.id" class="knowledge-item">
              <h4>{{ item.title }}</h4>
              <p>{{ item.summary }}</p>
            </div>
            <van-empty v-if="!getCategoryKnowledge(cat.id).length" description="暂无知识信息" />
          </template>
        </div>

        <!-- 立即报告按钮 -->
        <div class="action-area">
          <van-button type="primary" block round size="large" @click="goReport(cat.id)">
            {{ reportNowLabel }}
          </van-button>
        </div>
      </van-tab>
    </van-tabs>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import SafeBannerImage from '@/components/SafeBannerImage.vue'

const router = useRouter()
const platformTitle = ref('药品医疗器械化妆品不良反应/事件填报平台')
const bannerUrl = ref('')
const activeTab = ref(0)
const categories = ref([])
const allKnowledge = ref([])
const loading = ref(false)
const reportNowLabel = ref('立即报告')

const API_BASE = '/api'

const currentCategoryId = computed(() => {
  return categories.value[activeTab.value]?.id || 1
})

const currentBanner = computed(() => {
  const map = {
    1: 'level2_banner_cat1',
    2: 'level2_banner_cat2',
    3: 'level2_banner_cat3'
  }
  return banners.value[map[currentCategoryId.value]] || banners.value['level2_banner'] || ''
})

const banners = ref({})

async function loadConfig() {
  try {
    const res = await fetch(`${API_BASE}/config`)
    const data = await res.json()
    if (data.success && data.data) {
      if (data.data.platform_title) {
        platformTitle.value = data.data.platform_title.config_value
      }
      if (data.data.level2_banner) {
        bannerUrl.value = data.data.level2_banner.config_value
      }
      // Load category-specific banners
      for (const key of ['level2_banner_cat1', 'level2_banner_cat2', 'level2_banner_cat3']) {
        if (data.data[key]) {
          banners.value[key] = data.data[key].config_value
        }
      }
      if (data.data.level2_banner) {
        banners.value['level2_banner'] = data.data.level2_banner.config_value
      }
      reportNowLabel.value = data.data.btn_report_now?.config_value || '立即报告'
    }
  } catch (e) {
    console.error('加载配置失败:', e)
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
    showToast('加载分类失败')
  }
}

async function loadKnowledge() {
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/knowledge?page_level=2`)
    const data = await res.json()
    if (data.success) {
      allKnowledge.value = data.data || []
    }
  } catch (e) {
    console.error('加载知识失败:', e)
    showToast('加载知识失败')
  } finally {
    loading.value = false
  }
}

function getCategoryKnowledge(categoryId) {
  return allKnowledge.value.filter(k => k.category_id === categoryId)
}

function getCategoryBanner(categoryId) {
  const map = {
    1: 'level2_banner_cat1',
    2: 'level2_banner_cat2',
    3: 'level2_banner_cat3'
  }
  return banners.value[map[categoryId]] || ''
}


function getCategoryFallbackKey(categoryId) {
  const map = {
    1: 'level2_cat1',
    2: 'level2_cat2',
    3: 'level2_cat3'
  }
  return map[categoryId] || 'level2'
}
function goReport(categoryId) {
  router.push(`/report/${categoryId}`)
}

onMounted(async () => {
  await Promise.all([loadConfig(), loadCategories(), loadKnowledge()])
})
</script>

<style scoped>
.report-entry-page { padding-bottom: 20px; }
.header { background: linear-gradient(135deg, #1989fa, #07c160); padding: 20px 16px; text-align: center; }
.title { color: #fff; font-size: 18px; font-weight: 600; }
.banner { padding: 12px; }
.banner-img { width: 100%; border-radius: 8px; min-height: 100px; background: #e8e8e8; }
.banner-placeholder { width: 100%; height: 100px; border-radius: 8px; background: linear-gradient(135deg, #e8f4ff, #e8fff4); display: flex; align-items: center; justify-content: center; color: #999; font-size: 14px; }
.tab-banner { padding: 12px; min-height: 100px; }
.list-loading { text-align: center; padding: 20px 0; }
.knowledge-list { padding: 0 16px; }
.knowledge-item { background: #fff; border-radius: 8px; padding: 14px; margin-bottom: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.knowledge-item h4 { font-size: 15px; color: #333; margin-bottom: 6px; }
.knowledge-item p { font-size: 13px; color: #999; }
.action-area { padding: 20px 16px; }
</style>
