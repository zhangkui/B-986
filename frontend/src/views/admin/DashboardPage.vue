<template>
  <div class="dashboard-page">
    <el-row :gutter="20">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">0</div>
            <div class="stat-label">填报总数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">3</div>
            <div class="stat-label">分类数量</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">13</div>
            <div class="stat-label">地区数量</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-value">3</div>
            <div class="stat-label">管理员数量</div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-card style="margin-top: 20px">
      <template #header>最近填报</template>
      <el-empty description="暂无数据" />
    </el-card>
  </div>
</template>

<script setup>
// TODO: Kimi 实现统计数据 API 加载
</script>

<style scoped>
.stat-card { text-align: center; padding: 10px 0; }
.stat-value { font-size: 32px; font-weight: 700; color: #1989fa; }
.stat-label { font-size: 14px; color: #999; margin-top: 8px; }
</style>
