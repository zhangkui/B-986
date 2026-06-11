<template>
  <div class="dashboard-page">
    <el-row :gutter="20">
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card total">
          <div class="stat-icon"><el-icon><Document /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total }}</div>
            <div class="stat-label">举报总数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card pending">
          <div class="stat-icon"><el-icon><Clock /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.pending }}</div>
            <div class="stat-label">待受理</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card processing">
          <div class="stat-icon"><el-icon><Operation /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.processing }}</div>
            <div class="stat-label">处理中</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card supplement">
          <div class="stat-icon"><el-icon><Warning /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.supplement }}</div>
            <div class="stat-label">待补充</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card completed">
          <div class="stat-icon"><el-icon><CircleCheck /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.completed }}</div>
            <div class="stat-label">已办结</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="stat-card today">
          <div class="stat-icon"><el-icon><Calendar /></el-icon></div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.today }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="12">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">近7天举报趋势</div>
          </template>
          <div class="trend-chart">
            <div class="trend-bars">
              <div v-for="item in stats.trend" :key="item.date" class="trend-bar-item">
                <div class="bar-wrapper">
                  <div
                    class="bar"
                    :style="{ height: trendMax > 0 ? (item.count / trendMax * 120) + 'px' : '4px' }"
                  >{{ item.count > 0 ? item.count : '' }}</div>
                </div>
                <div class="bar-label">{{ formatDate(item.date) }}</div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">按分类统计</div>
          </template>
          <div class="stat-list">
            <div v-for="item in stats.by_category" :key="item.name" class="stat-list-item">
              <span class="list-label">{{ item.name }}</span>
              <el-tag type="primary" effect="plain">{{ item.count }}</el-tag>
            </div>
            <el-empty v-if="!stats.by_category?.length" description="暂无数据" :image-size="60" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">按地区统计</div>
          </template>
          <div class="stat-list">
            <div v-for="item in stats.by_region?.slice(0, 8)" :key="item.name" class="stat-list-item">
              <span class="list-label">{{ item.name }}</span>
              <el-tag type="success" effect="plain">{{ item.count }}</el-tag>
            </div>
            <el-empty v-if="!stats.by_region?.length" description="暂无数据" :image-size="60" />
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Document, Clock, Operation, Warning, CircleCheck, Calendar } from '@element-plus/icons-vue'

const API_BASE = '/api'
const stats = ref({
  total: 0,
  pending: 0,
  processing: 0,
  supplement: 0,
  completed: 0,
  rejected: 0,
  today: 0,
  this_month: 0,
  by_category: [],
  by_region: [],
  trend: []
})

const trendMax = computed(() => {
  if (!stats.value.trend?.length) return 0
  return Math.max(...stats.value.trend.map(t => t.count), 1)
})

function getToken() {
  return localStorage.getItem('admin_token')
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  return parts.slice(1).join('/')
}

async function loadStats() {
  try {
    const res = await fetch(`${API_BASE}/admin/reports/stats`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (data.success) {
      stats.value = data.data
    }
  } catch (e) {
    console.error('加载统计数据失败:', e)
  }
}

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.stat-card {
  display: flex;
  align-items: center;
  padding: 8px 0;
}
.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  width: 100%;
  gap: 16px;
}
.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #fff;
}
.stat-card.total .stat-icon { background: linear-gradient(135deg, #409eff, #66b1ff); }
.stat-card.pending .stat-icon { background: linear-gradient(135deg, #e6a23c, #f0c78a); }
.stat-card.processing .stat-icon { background: linear-gradient(135deg, #1989fa, #409eff); }
.stat-card.supplement .stat-icon { background: linear-gradient(135deg, #f56c6c, #f89898); }
.stat-card.completed .stat-icon { background: linear-gradient(135deg, #67c23a, #95d475); }
.stat-card.today .stat-icon { background: linear-gradient(135deg, #909399, #c0c4cc); }
.stat-content { flex: 1; }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.2; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }

.card-header { font-size: 16px; font-weight: 600; }

.trend-chart { padding: 20px 10px; }
.trend-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 160px;
  padding-top: 20px;
}
.trend-bar-item { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
.bar-wrapper {
  height: 130px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  width: 100%;
}
.bar {
  width: 36px;
  background: linear-gradient(180deg, #409eff, #66b1ff);
  border-radius: 6px 6px 0 0;
  min-height: 4px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  padding-top: 4px;
  transition: all 0.3s;
}
.bar-label { font-size: 12px; color: #909399; }

.stat-list { max-height: 200px; overflow-y: auto; }
.stat-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f5f7fa;
  font-size: 14px;
}
.stat-list-item:last-child { border-bottom: none; }
.list-label { color: #606266; }
</style>
