<template>
  <div class="mobile-layout">
    <router-view />
    <van-tabbar v-model="activeTab" route>
      <van-tabbar-item to="/" icon="home-o">{{ tabLabels.home }}</van-tabbar-item>
      <van-tabbar-item to="/report" icon="edit">{{ tabLabels.report }}</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
const activeTab = ref(0)
const tabLabels = ref({ home: '首页', report: '我要报告' })

onMounted(async () => {
  try {
    const res = await fetch('/api/config')
    const data = await res.json()
    if (data.success && data.data) {
      tabLabels.value.home = data.data.btn_home?.config_value || '首页'
      tabLabels.value.report = data.data.btn_report?.config_value || '我要报告'
    }
  } catch {
    // keep defaults
  }
})
</script>

<style scoped>
.mobile-layout {
  min-height: 100vh;
  padding-bottom: 50px;
  background: #f5f7fa;
}
</style>
